<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private CodeGeneratorService $codes,
    ) {}

    public function processPayment(
        User $collector,
        Customer $customer,
        array $invoiceIds,
        int $amountReceived,
        string $method = 'cash',
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): Payment {
        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $existing;
        }

        $methodEnum = PaymentMethod::from($method);

        $invoices = Invoice::whereIn('id', $invoiceIds)
            ->where('customer_id', $customer->id)
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])
            ->orderBy('billing_period')
            ->lockForUpdate()
            ->get();

        if ($invoices->isEmpty()) {
            throw new RuntimeException('Tidak ada tagihan aktif yang dapat dibayar untuk pelanggan ini.');
        }

        $totalBalance = (int) $invoices->sum(fn (Invoice $i) => $i->balance());

        if ($amountReceived <= 0) {
            throw new RuntimeException('Nominal yang diterima harus lebih besar dari nol.');
        }

        if ($amountReceived > $totalBalance) {
            throw new RuntimeException(
                'Nominal yang diterima ('.format_rupiah($amountReceived).
                ') melebihi total sisa tagihan ('.format_rupiah($totalBalance).'). '.
                'Kelebihan bayar belum diizinkan.'
            );
        }

        return DB::transaction(function () use (
            $collector, $customer, $invoices, $amountReceived, $methodEnum, $notes, $idempotencyKey
        ) {
            $transactionNumber = $this->codes->generateTransactionNumber();

            $payment = Payment::create([
                'transaction_number' => $transactionNumber,
                'idempotency_key' => $idempotencyKey,
                'customer_id' => $customer->id,
                'collector_id' => $collector->id,
                'paid_at' => now(),
                'amount_received' => $amountReceived,
                'payment_method' => $methodEnum,
                'status' => PaymentStatus::Success,
                'notes' => $notes,
            ]);

            $remaining = $amountReceived;

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }
                $balance = $invoice->balance();
                $allocation = min($balance, $remaining);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount_allocated' => $allocation,
                ]);

                $newPaid = $invoice->paid_amount + $allocation;
                $newStatus = $newPaid >= $invoice->amount ? InvoiceStatus::Paid : InvoiceStatus::Partial;

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'status' => $newStatus,
                ]);

                $remaining -= $allocation;
            }

            AuditLog::record(
                userId: $collector->id,
                action: 'payment.created',
                entityType: 'payment',
                entityId: $payment->id,
                old: null,
                new: [
                    'transaction_number' => $payment->transaction_number,
                    'customer_id' => $customer->id,
                    'amount_received' => $amountReceived,
                    'method' => $methodEnum->value,
                ],
                reason: $notes,
            );

            return $payment->fresh();
        });
    }

    public function cancel(Payment $payment, User $admin, string $reason): Payment
    {
        if ($payment->isCancelled()) {
            throw new RuntimeException('Transaksi ini sudah dibatalkan.');
        }

        return DB::transaction(function () use ($payment, $admin, $reason) {
            $allocations = $payment->allocations()->with('invoice')->get();

            foreach ($allocations as $allocation) {
                $invoice = $allocation->invoice;
                $newPaid = max(0, (int) $invoice->paid_amount - (int) $allocation->amount_allocated);
                $newStatus = $newPaid <= 0
                    ? InvoiceStatus::Unpaid
                    : ($newPaid < $invoice->amount ? InvoiceStatus::Partial : InvoiceStatus::Paid);

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'status' => $newStatus,
                ]);
            }

            $payment->update([
                'status' => PaymentStatus::Cancelled,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            AuditLog::record(
                userId: $admin->id,
                action: 'payment.cancelled',
                entityType: 'payment',
                entityId: $payment->id,
                old: ['status' => PaymentStatus::Success->value],
                new: ['status' => PaymentStatus::Cancelled->value, 'reason' => $reason],
                reason: $reason,
            );

            return $payment->fresh();
        });
    }
}
