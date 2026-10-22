<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptImageTest extends TestCase
{
    private function makePayment(int $amount = 150000): Payment
    {
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer, ['amount' => $amount]);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'receipt-image-'.uniqid(),
            'invoices' => [$invoice->id],
            'amount_received' => $amount,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect();

        return Payment::latest('id')->firstOrFail();
    }

    public function test_receipt_image_is_generated_right_after_payment_success(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->assertNotNull($payment->receipt_image_path);
        $this->assertNotNull($payment->receipt_share_token);
        $this->assertNotNull($payment->receipt_sent_at);
        Storage::disk('local')->assertExists($payment->receipt_image_path);
    }

    public function test_payment_without_receipt_can_be_generated_on_demand(): void
    {
        Storage::fake('local');
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();

        $payment = Payment::create([
            'transaction_number' => 'PAY-MANUAL-1',
            'idempotency_key' => 'manual-1',
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'paid_at' => now(),
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'status' => 'success',
        ]);

        $this->get("/payments/{$payment->id}")
            ->assertOk()
            ->assertSee('Buat Bukti Sekarang');

        $this->post("/payments/{$payment->id}/send-receipt")
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->receipt_image_path);
        $this->assertNotNull($payment->receipt_share_token);
        Storage::disk('local')->assertExists($payment->receipt_image_path);
    }

    public function test_public_receipt_link_is_accessible_without_login(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->post("/payments/{$payment->id}/send-receipt")->assertRedirect();

        $publicUrl = $payment->fresh()->publicReceiptUrl();
        $this->assertNotNull($publicUrl);

        auth()->logout();

        $path = parse_url($publicUrl, PHP_URL_PATH);
        $response = $this->get($path)->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_public_receipt_link_404_for_unknown_token(): void
    {
        $this->get('/bukti/token-tidak-ada')->assertNotFound();
    }

    public function test_collector_can_send_receipt_image_for_own_payment(): void
    {
        Storage::fake('local');
        $collector = $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->post("/payments/{$payment->id}/send-receipt")
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->receipt_image_path);
        $this->assertNotNull($payment->receipt_sent_at);
        $this->assertSame($collector->id, $payment->receipt_sent_by);
        Storage::disk('local')->assertExists($payment->receipt_image_path);
        $this->assertSame(1, AuditLog::where('action', 'payment.receipt_sent')->count());

        $this->get("/payments/{$payment->id}")
            ->assertOk()
            ->assertSee('Bukti Pembayaran (Gambar)')
            ->assertSee($collector->name)
            ->assertSee('Salin Link')
            ->assertSee($payment->fresh()->publicReceiptUrl());
    }

    public function test_send_receipt_returns_json_payload_for_ajax(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->postJson("/payments/{$payment->id}/send-receipt")
            ->assertOk()
            ->assertJsonStructure(['image_url', 'download_url', 'sent_at', 'sent_by', 'whatsapp_url']);
    }

    public function test_receipt_image_is_served_as_png(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->post("/payments/{$payment->id}/send-receipt")->assertRedirect();

        $response = $this->get("/payments/{$payment->id}/receipt-image")->assertOk();
        $response->assertHeader('Content-Type', 'image/png');

        $body = $response->getContent();
        $this->assertStringStartsWith("\x89PNG", $body);
    }

    public function test_collector_cannot_send_receipt_for_other_collector_payment(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $other = $this->collector();
        $this->actingAs($other);

        $sentAt = $payment->receipt_sent_at;

        $this->post("/payments/{$payment->id}/send-receipt")->assertForbidden();
        $this->get("/payments/{$payment->id}/receipt-image")->assertForbidden();

        $this->assertSame(
            $sentAt?->toDateTimeString(),
            $payment->fresh()->receipt_sent_at?->toDateTimeString(),
        );
    }

    public function test_admin_can_send_receipt_for_any_payment(): void
    {
        Storage::fake('local');
        $this->actingAsCollector();
        $payment = $this->makePayment();

        $this->actingAsAdmin();

        $this->post("/payments/{$payment->id}/send-receipt")->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($payment->fresh()->receipt_image_path);
    }

    public function test_cancelled_payment_receipt_is_rejected(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();
        $payment = $this->makePayment();

        $this->post("/payments/{$payment->id}/cancel", ['reason' => 'Salah input nominal'])->assertRedirect();

        $this->post("/payments/{$payment->id}/send-receipt")->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($payment->fresh()->isCancelled());
    }
}
