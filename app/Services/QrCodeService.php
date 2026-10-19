<?php

namespace App\Services;

use App\Models\Customer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function generateSvg(string $content, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd,
        );
        $writer = new Writer($renderer);

        return $writer->writeString($content);
    }

    public function generateForCustomer(Customer $customer, int $size = 240): string
    {
        return $this->generateSvg($customer->barcode_token, $size);
    }
}
