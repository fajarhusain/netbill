<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(InvoiceService::class);
        $now = Carbon::now();
        $currentPeriod = $now->format('Y-m');

        $customers = Customer::where('status', CustomerStatus::Active)->get();

        foreach ($customers as $customer) {
            $startedAt = Carbon::parse($customer->started_at);
            $periodCursor = Carbon::createFromDate($startedAt->year, $startedAt->month, 1);

            while ($periodCursor->lte($now)) {
                $period = $periodCursor->format('Y-m');

                try {
                    $service->createInvoice($customer, $period, $startedAt, 1);
                } catch (\Throwable $e) {
                }

                $periodCursor->addMonth();
            }
        }
    }
}
