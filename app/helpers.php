<?php

use Illuminate\Support\Carbon;

if (! function_exists('format_rupiah')) {
    function format_rupiah(int|float|null $value, bool $withSymbol = true): string
    {
        $value = (int) ($value ?? 0);
        $formatted = number_format($value, 0, ',', '.');

        return $withSymbol ? 'Rp'.$formatted : $formatted;
    }
}

if (! function_exists('parse_rupiah')) {
    function parse_rupiah(string|int|null $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (! $value) {
            return 0;
        }
        $value = preg_replace('/[^0-9]/', '', (string) $value);

        return (int) $value;
    }
}

if (! function_exists('format_date')) {
    function format_date($date, string $format = 'd M Y'): string
    {
        if (! $date) {
            return '-';
        }
        $locale = setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian_indonesia.1252');
        $dt = Carbon::parse($date)->timezone(config('app.timezone'));

        return $locale
            ? $dt->translatedFormat(str_replace(['M', 'F', 'l', 'D'], ['M', 'F', 'l', 'D'], $format))
            : $dt->format($format);
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime($date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)
            ->timezone(config('app.timezone'))
            ->translatedFormat('d M Y H:i').' WIB';
    }
}

if (! function_exists('format_period')) {
    function format_period(string $period): string
    {
        if (! $period || strlen($period) < 7) {
            return $period ?: '-';
        }
        [$year, $month] = explode('-', $period);
        $months = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];

        return ($months[$month] ?? $month).' '.$year;
    }
}
