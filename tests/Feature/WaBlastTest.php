<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\WaBlastLog;
use App\Services\BillingMessageService;
use Tests\TestCase;

class WaBlastTest extends TestCase
{
    public function test_index_lists_customers_with_arrears(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer(['name' => 'Budi Santoso', 'dusun' => 'Mangonan']);
        $this->makeArrears($customer, 2);
        $this->makeCustomer(['name' => 'Pelanggan Lunas', 'customer_code' => 'RTW-0002']);

        $this->get('/blast')
            ->assertOk()
            ->assertSee('Blast WhatsApp Penagihan')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Pelanggan Lunas');
    }

    public function test_filter_can_include_customers_without_arrears(): void
    {
        $this->actingAsCollector();
        $this->makeCustomer(['name' => 'Pelanggan Lunas', 'customer_code' => 'RTW-0009']);

        $this->get('/blast?only_arrears=0')
            ->assertOk()
            ->assertSee('Pelanggan Lunas');
    }

    public function test_custom_template_is_applied_to_message_preview(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer(['name' => 'Siti Aminah']);
        $this->makeArrears($customer, 1);

        $this->get('/blast?template='.urlencode('Halo {nama}, tagihan {total}'))
            ->assertOk()
            ->assertSee('Halo {nama}, tagihan {total}')
            ->assertSee('data-periods="Januari 2026"', false)
            ->assertSee('data-total="Rp150.000"', false);
    }

    public function test_message_uses_customer_placeholders(): void
    {
        $customer = $this->makeCustomer(['name' => 'Siti Aminah', 'customer_code' => 'RTW-0777']);
        $this->makeArrears($customer, 2);
        $customer->load(['package', 'invoices' => fn ($q) => $q->unsettled()->orderBy('billing_period')]);

        $service = new BillingMessageService;
        $message = $service->render($customer, 'Halo {nama} ({kode}) tunggakan {total} periode {periode}.');

        $this->assertStringContainsString('Halo Siti Aminah (RTW-0777)', $message);
        $this->assertStringContainsString('Rp300.000', $message);
        $this->assertStringContainsString('Januari 2026', $message);
    }

    public function test_logging_blast_records_history_and_audit(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $this->makeArrears($customer, 1);

        $this->postJson('/blast/log', [
            'items' => [
                ['customer_id' => $customer->id, 'message' => 'Halo, ini pengingat tagihan.'],
            ],
        ])->assertOk()->assertJson(['logged' => 1]);

        $this->assertDatabaseHas('wa_blast_logs', [
            'customer_id' => $customer->id,
            'user_id' => $collector->id,
            'channel' => 'whatsapp',
        ]);
        $this->assertSame(1, AuditLog::where('action', 'wa_blast.sent')->count());
    }

    public function test_logging_requires_items(): void
    {
        $this->actingAsCollector();

        $this->postJson('/blast/log', ['items' => []])->assertStatus(422);
    }

    public function test_history_page_shows_only_own_logs_for_collector(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();

        WaBlastLog::create([
            'customer_id' => $customer->id,
            'user_id' => $collector->id,
            'phone' => $customer->phone,
            'message' => 'Pesan milik saya',
            'total_arrears' => 150000,
            'channel' => 'whatsapp',
            'sent_at' => now(),
        ]);

        $other = $this->collector();
        WaBlastLog::create([
            'customer_id' => $customer->id,
            'user_id' => $other->id,
            'phone' => $customer->phone,
            'message' => 'Pesan petugas lain',
            'total_arrears' => 150000,
            'channel' => 'whatsapp',
            'sent_at' => now(),
        ]);

        $this->get('/blast/history')
            ->assertOk()
            ->assertSee('Pesan milik saya')
            ->assertDontSee('Pesan petugas lain');
    }

    public function test_guest_cannot_access_blast(): void
    {
        $this->get('/blast')->assertRedirect('/login');
    }
}
