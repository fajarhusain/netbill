<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    public function generateCustomerCode(string $prefix = 'RTW'): string
    {
        $last = Customer::where('customer_code', 'like', $prefix.'-%')
            ->orderByRaw('CAST(SUBSTRING(customer_code, '.(strlen($prefix) + 2).') AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) Str::after($last->customer_code, $prefix.'-')) + 1 : 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function generateBarcodeToken(): string
    {
        return Str::random(40).bin2hex(random_bytes(4));
    }

    public function generateTransactionNumber(): string
    {
        $prefix = 'PAY-'.now()->format('Ym').'-';
        $last = Payment::where('transaction_number', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(transaction_number, '.(strlen($prefix) + 1).') AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        $next = $last ? ((int) Str::after($last->transaction_number, $prefix)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function generateInvoiceNumber(string $period): string
    {
        $prefix = 'INV-'.$period.'-';
        $count = Invoice::where('invoice_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function generateIdempotencyKey(): string
    {
        return (string) Str::uuid();
    }
}
