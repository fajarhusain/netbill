<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function __construct(
        private CodeGeneratorService $codes,
    ) {}

    public function generateForPeriod(string $period, ?array $customerIds = null, int $issuerId = 0): array
    {
        $this->validatePeriod($period);

        [$year, $month] = explode('-', $period);
        $issuedAt = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();

        $query = Customer::where('status', CustomerStatus::Active)
            ->whereNotNull('started_at')
            ->whereDate('started_at', '<=', $issuedAt->copy()->endOfMonth());

        if (! empty($customerIds)) {
            $query->whereIn('id', $customerIds);
        }

        $customers = $query->get();
        $created = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('billing_period', $period)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $this->createInvoice($customer, $period, $issuedAt, $issuerId);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function createInvoice(Customer $customer, string $period, ?Carbon $issuedAt = null, int $issuerId = 0): Invoice
    {
        $this->validatePeriod($period);

        if ($customer->status !== CustomerStatus::Active) {
            throw new RuntimeException('Pelanggan tidak aktif, tidak dapat dibuatkan tagihan.');
        }

        $exists = Invoice::where('customer_id', $customer->id)
            ->where('billing_period', $period)
            ->exists();

        if ($exists) {
            throw new RuntimeException('Tagihan untuk periode tersebut sudah ada.');
        }

        [$year, $month] = explode('-', $period);
        $issuedAt = $issuedAt ?? Carbon::now();
        $dueDay = max(1, min(28, (int) $customer->due_day));
        $dueDate = Carbon::createFromDate((int) $year, (int) $month, $dueDay);

        return DB::transaction(function () use ($customer, $period, $issuedAt, $dueDate, $issuerId) {
            $invoice = Invoice::create([
                'invoice_number' => $this->codes->generateInvoiceNumber($period),
                'customer_id' => $customer->id,
                'billing_period' => $period,
                'issued_at' => $issuedAt->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'amount' => $customer->monthly_fee,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            AuditLog::record(
                userId: $issuerId ?: (auth()->id() ?: null),
                action: 'invoice.created',
                entityType: 'invoice',
                entityId: $invoice->id,
                old: null,
                new: ['customer_id' => $customer->id, 'period' => $period, 'amount' => $customer->monthly_fee],
            );

            return $invoice;
        });
    }

    public function cancel(Invoice $invoice, int $userId, string $reason): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new RuntimeException('Tagihan yang sudah lunas tidak dapat dibatalkan.');
        }
        if ($invoice->paid_amount > 0) {
            throw new RuntimeException('Tagihan yang sudah memiliki pembayaran tidak dapat dibatalkan. Batalkan transaksi pembayaran terlebih dahulu.');
        }

        return DB::transaction(function () use ($invoice, $userId, $reason) {
            $old = ['status' => $invoice->status->value];
            $invoice->update(['status' => InvoiceStatus::Cancelled]);
            AuditLog::record(
                userId: $userId,
                action: 'invoice.cancelled',
                entityType: 'invoice',
                entityId: $invoice->id,
                old: $old,
                new: ['status' => InvoiceStatus::Cancelled->value],
                reason: $reason,
            );

            return $invoice->fresh();
        });
    }

    private function validatePeriod(string $period): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw new RuntimeException('Format periode tidak valid. Gunakan format YYYY-MM, misalnya 2026-10.');
        }
    }
}
