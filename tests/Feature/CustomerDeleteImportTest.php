<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\PaymentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerDeleteImportTest extends TestCase
{
    public function test_admin_can_delete_customer_without_payments(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer(['customer_code' => 'RTW-DEL1']);

        $this->delete("/customers/{$customer->id}")->assertRedirect('/customers');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.deleted',
            'entity_type' => 'customer',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_cannot_delete_customer_with_payments(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cust-del-1',
        );

        $this->from("/customers/{$customer->id}")
            ->delete("/customers/{$customer->id}")
            ->assertRedirect("/customers/{$customer->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_collector_cannot_delete_customer(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer();

        $this->delete("/customers/{$customer->id}")->assertForbidden();
    }

    public function test_show_page_has_delete_button(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        $this->get("/customers/{$customer->id}")
            ->assertOk()
            ->assertSee('Hapus')
            ->assertSee('confirmDeleteCustomer');
    }

    public function test_index_page_has_delete_and_import_buttons(): void
    {
        $this->actingAsAdmin();
        $this->makeCustomer();

        $this->get('/customers')
            ->assertOk()
            ->assertSee('confirmDeleteCustomer')
            ->assertSee('Import Excel')
            ->assertSee('importModal');
    }

    public function test_import_template_downloads(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/customers/import/template');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=template-import-pelanggan.xlsx');
    }

    public function test_admin_can_import_customers_from_csv(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $this->makePackage(['name' => 'Paket Reguler']);

        $csvContent = "nama,kode,telepon,alamat,dusun,paket,tarif,tanggal_mulai,tanggal_jatuh_tempo,status,catatan\n";
        $csvContent .= "Andi Wijaya,RTW-IMP1,081200000001,Jl. Mangga,Dusun Makmur,Paket Reguler,150000,2026-01-05,10,active,\n";
        $csvContent .= "Budi Hartono,RTW-IMP2,081200000002,Jl. Jambu,,Paket Reguler,150000,2026-02-01,15,active,Tes catatan\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $this->post('/customers/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', ['customer_code' => 'RTW-IMP1', 'name' => 'Andi Wijaya']);
        $this->assertDatabaseHas('customers', ['customer_code' => 'RTW-IMP2', 'name' => 'Budi Hartono']);
    }

    public function test_import_skips_duplicate_customer_codes(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $this->makePackage(['name' => 'Paket Reguler']);
        $this->makeCustomer(['customer_code' => 'RTW-DUP', 'name' => 'Existing']);

        $csvContent = "nama,kode,telepon,alamat,dusun,paket,tarif,tanggal_mulai,tanggal_jatuh_tempo,status,catatan\n";
        $csvContent .= "New Person,RTW-DUP,081200000003,Jl. Test,,Paket Reguler,150000,2026-01-05,10,active,\n";
        $csvContent .= "Fresh Person,RTW-NEW1,081200000004,Jl. Baru,,Paket Reguler,150000,2026-01-05,10,active,\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $this->post('/customers/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', ['customer_code' => 'RTW-DUP', 'name' => 'Existing']);
        $this->assertDatabaseHas('customers', ['customer_code' => 'RTW-NEW1', 'name' => 'Fresh Person']);
    }

    public function test_import_requires_file(): void
    {
        $this->actingAsAdmin();

        $this->post('/customers/import', [])->assertSessionHasErrors('file');
    }

    public function test_import_requires_valid_file_type(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->post('/customers/import', ['file' => $file])->assertSessionHasErrors('file');
    }

    public function test_collector_cannot_import(): void
    {
        $this->actingAsCollector();

        $this->post('/customers/import', [])->assertForbidden();
    }

    public function test_collector_cannot_download_template(): void
    {
        $this->actingAsCollector();

        $this->get('/customers/import/template')->assertForbidden();
    }
}
