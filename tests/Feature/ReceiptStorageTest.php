<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Services\ReceiptImageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptStorageTest extends TestCase
{
    private function paymentWithReceipt(): Payment
    {
        $collector = User::factory()->collector()->create();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = Payment::create([
            'transaction_number' => 'PAY-STORAGE-'.uniqid(),
            'idempotency_key' => 'storage-'.uniqid(),
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'paid_at' => now(),
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'status' => 'success',
            'receipt_sent_at' => now(),
            'receipt_sent_by' => $collector->id,
            'receipt_share_token' => 'token-'.uniqid(),
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_allocated' => 150000,
        ]);

        $payment->receipt_image_path = app(ReceiptImageService::class)->store($payment);
        $payment->save();

        return $payment->fresh();
    }

    public function test_admin_can_view_storage_page(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();

        $this->get('/receipts/storage')
            ->assertOk()
            ->assertSee('Penyimpanan Bukti Pembayaran')
            ->assertSee('Reset Penyimpanan');
    }

    public function test_collector_cannot_access_storage_page(): void
    {
        $this->actingAsCollector();

        $this->get('/receipts/storage')->assertForbidden();
        $this->post('/receipts/storage/reset', ['scope' => 'images', 'confirm' => 1])->assertForbidden();
    }

    public function test_reset_images_deletes_files_but_keeps_payment_metadata(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();

        Storage::disk('local')->assertExists($payment->receipt_image_path);

        $this->post('/receipts/storage/reset', [
            'scope' => 'images',
            'confirm' => 1,
            'reason' => 'bersihkan penyimpanan',
        ])->assertRedirect()->assertSessionHas('success');

        $payment->refresh();
        $this->assertNull($payment->receipt_image_path);
        $this->assertNotNull($payment->receipt_sent_at);
        $this->assertSame([], Storage::disk('local')->files('receipts'));
        $this->assertSame(1, AuditLog::where('action', 'receipt.storage_reset')->count());
    }

    public function test_reset_all_also_clears_metadata_and_share_token(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();
        $payment->forceFill(['receipt_share_token' => 'token-abc'])->save();

        $this->post('/receipts/storage/reset', [
            'scope' => 'all',
            'confirm' => 1,
        ])->assertRedirect();

        $payment->refresh();
        $this->assertNull($payment->receipt_image_path);
        $this->assertNull($payment->receipt_sent_at);
        $this->assertNull($payment->receipt_sent_by);
        $this->assertNull($payment->receipt_share_token);
    }

    public function test_reset_requires_confirmation(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();

        $this->from('/receipts/storage')->post('/receipts/storage/reset', [
            'scope' => 'images',
        ])->assertRedirect('/receipts/storage')->assertSessionHasErrors('confirm');

        Storage::disk('local')->assertExists($payment->fresh()->receipt_image_path);
    }

    public function test_regenerate_rebuilds_missing_images(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();

        $this->post('/receipts/storage/reset', ['scope' => 'images', 'confirm' => 1])->assertRedirect();
        $this->assertNull($payment->fresh()->receipt_image_path);

        $this->post('/receipts/storage/regenerate', ['limit' => 10])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->receipt_image_path);
        Storage::disk('local')->assertExists($payment->receipt_image_path);
        $this->assertSame(1, AuditLog::where('action', 'receipt.regenerated')->count());
    }

    public function test_admin_can_reset_single_payment_receipt(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();

        $this->post("/payments/{$payment->id}/reset-receipt")->assertRedirect();

        $payment->refresh();
        $this->assertNull($payment->receipt_image_path);
        $this->assertSame(1, AuditLog::where('action', 'receipt.reset')->count());
    }

    public function test_public_link_stops_working_after_reset(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->paymentWithReceipt();

        $this->post("/payments/{$payment->id}/send-receipt")->assertRedirect();
        $token = $payment->fresh()->receipt_share_token;
        $this->assertNotNull($token);

        $this->get("/bukti/{$token}")->assertOk();

        $this->post('/receipts/storage/reset', ['scope' => 'all', 'confirm' => 1])->assertRedirect();

        $this->get("/bukti/{$token}")->assertNotFound();
    }
}
