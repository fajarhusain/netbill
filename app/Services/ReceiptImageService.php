<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReceiptImageService
{
    private const WIDTH = 760;

    private const SCALE = 2;

    /**
     * Render the payment receipt as a watermarked PNG image.
     */
    public function render(Payment $payment): string
    {
        $payment->loadMissing(['customer.package', 'collector', 'allocations.invoice']);

        $s = self::SCALE;
        $width = self::WIDTH * $s;
        $margin = 24 * $s;
        $cardW = $width - 2 * $margin;
        $cardX = $margin;
        $pad = 28 * $s;
        $contentX = $cardX + $pad;
        $contentW = $cardW - 2 * $pad;

        $ops = $this->layout($payment, $contentX, $contentW, $s);
        $contentHeight = 0;
        foreach ($ops as $op) {
            $contentHeight = max($contentHeight, $op['y'] + ($op['h'] ?? 0));
        }

        $cardY = $margin;
        $cardH = $contentHeight + $pad * 2;
        $height = $cardH + 2 * $margin;

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $bg = imagecolorallocate($img, 241, 245, 249);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        $this->roundedRect($img, $cardX, $cardY, $cardW, $cardH, 16 * $s, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardW, $cardY + 6 * $s, imagecolorallocate($img, 99, 102, 241));

        $this->drawWatermark($img, $payment, $cardX, $cardY, $cardW, $cardH, $s);

        foreach ($ops as $op) {
            $this->drawOp($img, $op, $cardY + $pad);
        }

        $final = imagescale($img, self::WIDTH, (int) round($height / $s));
        imagedestroy($img);

        ob_start();
        imagepng($final, null, 7);
        $binary = (string) ob_get_clean();
        imagedestroy($final);

        return $binary;
    }

    /**
     * Render and persist the receipt image, returning the storage path.
     */
    public function store(Payment $payment): string
    {
        $path = 'receipts/'.$payment->id.'-'.now()->format('YmdHis').'.png';

        Storage::disk('local')->put($path, $this->render($payment));

        if ($payment->receipt_image_path && $payment->receipt_image_path !== $path) {
            Storage::disk('local')->delete($payment->receipt_image_path);
        }

        return $path;
    }

    private function layout(Payment $payment, int $x, int $w, int $s): array
    {
        $ops = [];
        $y = 0;

        $muted = [100, 116, 139];
        $dark = [15, 23, 42];
        $primary = [99, 102, 241];
        $success = [22, 163, 74];
        $danger = [220, 38, 38];

        $text = function (string $value, int $size, array $color, string $align, bool $bold, int $atY) use (&$ops, $x, $w) {
            $ops[] = [
                'type' => 'text',
                'text' => $value,
                'size' => $size,
                'color' => $color,
                'align' => $align,
                'font' => $bold ? 1 : 0,
                'x' => $x,
                'w' => $w,
                'y' => $atY,
                'h' => (int) round($size * 1.4),
            ];
        };

        $line = function (int $atY) use (&$ops, $x, $w) {
            $ops[] = ['type' => 'line', 'x' => $x, 'w' => $w, 'y' => $atY, 'color' => [226, 232, 240], 'h' => 1];
        };

        // Header
        $text('JRC MEDIA', 22 * $s, $primary, 'center', true, $y);
        $y += 34 * $s;
        $text('BUKTI PEMBAYARAN', 10 * $s, $muted, 'center', false, $y);
        $y += 20 * $s;

        $status = $payment->isCancelled() ? 'DIBATALKAN' : 'BERHASIL';
        $text($status, 10 * $s, $payment->isCancelled() ? $danger : $success, 'center', true, $y);
        $y += 24 * $s;

        $line($y);
        $y += 18 * $s;

        $rows = [
            ['No. Transaksi', $payment->transaction_number],
            ['Tanggal Bayar', format_datetime($payment->paid_at)],
            ['ID Pelanggan', $payment->customer->customer_code],
            ['Nama', $payment->customer->name],
            ['Paket', $payment->customer->package?->name ?? '-'],
        ];

        if ($payment->customer->dusun) {
            $rows[] = ['Dusun', $payment->customer->dusun];
        }

        $rows[] = ['Petugas', $payment->collector->name];

        foreach ($rows as [$label, $value]) {
            $text($label, 11 * $s, $muted, 'left', false, $y);
            $text($value, 12 * $s, $dark, 'right', true, $y);
            $y += 25 * $s;
        }

        $y += 8 * $s;
        $line($y);
        $y += 18 * $s;

        $text('PERIODE DIBAYAR', 10 * $s, $muted, 'left', true, $y);
        $y += 24 * $s;

        foreach ($payment->allocations as $allocation) {
            $text(format_period($allocation->invoice->billing_period), 12 * $s, $dark, 'left', false, $y);
            $text(format_rupiah($allocation->amount_allocated), 12 * $s, $dark, 'right', true, $y);
            $y += 24 * $s;
        }

        $y += 8 * $s;
        $line($y);
        $y += 20 * $s;

        $text('TOTAL DIBAYAR', 12 * $s, $dark, 'left', true, $y);
        $text(format_rupiah($payment->amount_received), 20 * $s, $primary, 'right', true, $y - 6 * $s);
        $y += 34 * $s;

        $text('Metode: '.$payment->payment_method->label(), 10 * $s, $muted, 'left', false, $y);
        $y += 22 * $s;

        $line($y);
        $y += 18 * $s;

        $text('Terima kasih atas pembayaran Anda.', 10 * $s, $muted, 'center', false, $y);
        $y += 18 * $s;
        $text('Simpan bukti ini sebagai arsip.', 10 * $s, $muted, 'center', false, $y);
        $y += 26 * $s;

        $sentAt = $payment->receipt_sent_at ?? now();
        $text('Dibuat oleh '.$payment->collector->name.' • '.format_datetime($sentAt), 10 * $s, $primary, 'center', true, $y);
        $y += 20 * $s;

        return $ops;
    }

    private function drawOp($img, array $op, int $offsetY): void
    {
        $color = imagecolorallocate($img, ...$op['color']);

        if ($op['type'] === 'line') {
            imageline($img, $op['x'], $op['y'] + $offsetY, $op['x'] + $op['w'], $op['y'] + $offsetY, $color);

            return;
        }

        $font = $this->font((bool) $op['font']);
        $box = imagettfbbox($op['size'], 0, $font, $op['text']);
        $textW = abs($box[2] - $box[0]);

        $x = $op['x'];
        if ($op['align'] === 'center') {
            $x = $op['x'] + (int) round(($op['w'] - $textW) / 2);
        } elseif ($op['align'] === 'right') {
            $x = $op['x'] + $op['w'] - $textW;
        }

        $y = $op['y'] + $offsetY + $op['size'];
        imagettftext($img, $op['size'], 0, $x, $y, $color, $font, $op['text']);
    }

    private function drawWatermark($img, Payment $payment, int $x, int $y, int $w, int $h, int $s): void
    {
        $color = imagecolorallocatealpha($img, 99, 102, 241, 110);
        $font = $this->font(true);
        $label = $payment->collector->name.' • '.($payment->receipt_sent_at ?? now())->format('d/m/Y H:i');
        $size = 15 * $s;
        $angle = 28;

        $box = imagettfbbox($size, $angle, $font, $label);
        $textW = abs($box[2] - $box[0]);
        $stepX = $textW + 80 * $s;
        $stepY = 120 * $s;

        for ($row = 0; $row * $stepY < $h + $stepY; $row++) {
            $offsetX = $row % 2 === 0 ? 0 : (int) round($stepX / 2);
            for ($col = -1; $col * $stepX < $w + $stepX; $col++) {
                $px = $x + $col * $stepX + $offsetX;
                $py = $y + 90 * $s + $row * $stepY;
                imagettftext($img, $size, $angle, $px, $py, $color, $font, $label);
            }
        }
    }

    private function roundedRect($img, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
        imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }

    private function font(bool $bold = false): string
    {
        $file = $bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf';
        $candidates = [
            resource_path('fonts/'.$file),
            base_path('vendor/dompdf/dompdf/lib/fonts/'.$file),
            '/usr/share/fonts/truetype/dejavu/'.$file,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Font untuk bukti pembayaran tidak ditemukan.');
    }
}
