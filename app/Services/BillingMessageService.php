<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;

class BillingMessageService
{
    public const DEFAULT_TEMPLATE = "Halo {nama}, kami dari JRC Media ingin mengingatkan tagihan internet Anda.\n\n"
        ."Kode: {kode}\n"
        ."Paket: {paket}\n"
        ."Periode: {periode}\n"
        ."Tunggakan: {total}\n"
        ."Jatuh tempo: {jatuh_tempo}\n\n"
        ."Mohon segera melakukan pembayaran kepada petugas kami. Abaikan pesan ini jika sudah membayar.\n\n"
        .'Terima kasih.'."\n"
        .'— {penagih}';

    /**
     * @return array<string, string>
     */
    public function placeholders(): array
    {
        return [
            '{nama}' => 'Nama pelanggan',
            '{kode}' => 'Kode pelanggan',
            '{paket}' => 'Nama paket',
            '{periode}' => 'Periode menunggak',
            '{total}' => 'Total tunggakan',
            '{jatuh_tempo}' => 'Jatuh tempo terdekat',
            '{dusun}' => 'Dusun',
            '{alamat}' => 'Alamat',
            '{penagih}' => 'Nama penagih',
        ];
    }

    public function render(Customer $customer, ?string $template = null, ?User $user = null): string
    {
        $template = $template ?: self::DEFAULT_TEMPLATE;

        $invoices = $customer->relationLoaded('invoices')
            ? $customer->invoices
            : $customer->invoices()->unsettled()->orderBy('billing_period')->get();

        $unsettled = $invoices->filter(fn (Invoice $i) => $i->balance() > 0)->sortBy('billing_period')->values();

        $periods = $unsettled
            ->map(fn (Invoice $i) => format_period($i->billing_period))
            ->implode(', ');

        $total = (int) $unsettled->sum(fn (Invoice $i) => $i->balance());

        $dueDate = $unsettled->first()?->due_date;

        $replacements = [
            '{nama}' => (string) $customer->name,
            '{kode}' => (string) $customer->customer_code,
            '{paket}' => (string) ($customer->package?->name ?? '-'),
            '{periode}' => $periods !== '' ? $periods : '-',
            '{total}' => format_rupiah($total),
            '{jatuh_tempo}' => $dueDate ? format_date($dueDate) : '-',
            '{dusun}' => (string) ($customer->dusun ?? '-'),
            '{alamat}' => (string) ($customer->address ?? '-'),
            '{penagih}' => (string) ($user?->name ?? 'JRC Media'),
        ];

        return strtr($template, $replacements);
    }

    public function normalizeNumber(?string $phone): ?string
    {
        $number = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($number === '') {
            return null;
        }

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        return $number;
    }

    public function whatsappUrl(?string $phone, string $message): ?string
    {
        $number = $this->normalizeNumber($phone);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
