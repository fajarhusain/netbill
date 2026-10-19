<?php

namespace Tests\Feature;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    public function test_customer_has_unique_code_and_barcode_token(): void
    {
        $this->actingAsAdmin();
        $c1 = $this->makeCustomer(['customer_code' => 'RTW-0001']);
        $c2 = $this->makeCustomer(['customer_code' => 'RTW-0002', 'barcode_token' => 'token-2']);

        $this->assertNotEquals($c1->customer_code, $c2->customer_code);
        $this->assertNotEquals($c1->barcode_token, $c2->barcode_token);
        $this->assertNotEmpty($c1->barcode_token);
    }

    public function test_qr_code_endpoint_returns_svg(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        $this->get("/customers/{$customer->id}/qrcode")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_customer_card_page_loads_with_qr(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        $this->get("/customers/{$customer->id}/card")
            ->assertOk()
            ->assertSee($customer->customer_code)
            ->assertSee($customer->name);
    }

    public function test_qr_service_generates_svg_content(): void
    {
        $svg = app(QrCodeService::class)->generateSvg('RTW-0001', 120);

        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_regenerate_barcode_creates_new_token(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $oldToken = $customer->barcode_token;

        $this->post("/customers/{$customer->id}/regenerate-barcode")
            ->assertRedirect();

        $this->assertNotEquals($oldToken, $customer->fresh()->barcode_token);
    }
}
