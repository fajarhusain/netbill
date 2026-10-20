<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bukti Pembayaran {{ $payment->transaction_number }}</title>
<style>
    body { font-family: 'Courier New', monospace; margin:0; padding:12px; color:#000; background:#f4f6f9; }
    .receipt { max-width:380px; margin:0 auto; border:1px dashed #000; padding:14px; background:#fff; }
    .center{text-align:center;} .bold{font-weight:bold;}
    .sep{border-top:1px dashed #000; margin:6px 0;}
    .row{display:flex; justify-content:space-between; font-size:12px; margin:2px 0;}
    .actions { max-width:380px; margin:12px auto 0; display:flex; gap:8px; }
    .btn { flex:1; padding:10px; border:0; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; }
    .btn-print { background:#6c757d; color:#fff; }
    .btn-wa { background:#25D366; color:#fff; }
    @media print { .no-print{display:none;} body{padding:0;background:#fff;} .receipt{border:1px dashed #000;} }
</style>
</head>
<body>
@php
    $waPhone = preg_replace('/[^0-9]/', '', $payment->customer->phone ?? '');
    if (str_starts_with($waPhone, '0')) { $waPhone = '62' . substr($waPhone, 1); }
    $waText = "Bukti Pembayaran JRC Media\nNo: {$payment->transaction_number}\nNama: {$payment->customer->name}\nTotal: " . format_rupiah($payment->amount_received) . "\nMetode: {$payment->payment_method->label()}\nStatus: " . ($payment->isCancelled() ? 'DIBATALKAN' : 'BERHASIL');
@endphp
<div class="receipt">
    <div class="center bold">JRC Media</div>
    <div class="center" style="font-size:11px;">BUKTI PEMBAYARAN</div>
    <div class="sep"></div>
    <div class="row"><span>No. Transaksi</span><span class="bold">{{ $payment->transaction_number }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ format_datetime($payment->paid_at) }}</span></div>
    <div class="row"><span>ID Pelanggan</span><span>{{ $payment->customer->customer_code }}</span></div>
    <div class="row"><span>Nama</span><span>{{ $payment->customer->name }}</span></div>
    <div class="row"><span>Paket</span><span>{{ $payment->customer->package?->name ?? '-' }}</span></div>
    <div class="sep"></div>
    <div class="bold" style="font-size:11px; margin-bottom:2px;">Periode Dibayar:</div>
    @foreach($payment->allocations as $a)
    <div class="row"><span>{{ format_period($a->invoice->billing_period) }}</span><span>{{ format_rupiah($a->amount_allocated) }}</span></div>
    @endforeach
    <div class="sep"></div>
    <div class="row"><span class="bold">Total Dibayar</span><span class="bold">{{ format_rupiah($payment->amount_received) }}</span></div>
    <div class="row"><span>Metode</span><span>{{ $payment->payment_method->label() }}</span></div>
    <div class="row"><span>Petugas</span><span>{{ $payment->collector->name }}</span></div>
    <div class="row"><span>Status</span><span class="bold">{{ $payment->isCancelled() ? 'DIBATALKAN' : 'BERHASIL' }}</span></div>
    <div class="sep"></div>
    <div class="center" style="font-size:11px;">
        Terima kasih atas pembayaran Anda.<br>
        Simpan bukti ini sebagai arsip.
    </div>
</div>
<div class="actions no-print">
    <button class="btn btn-print" onclick="window.print()">Cetak</button>
    @if($waPhone && ! $payment->isCancelled())
    <a class="btn btn-wa" target="_blank" href="https://wa.me/{{ $waPhone }}?text={{ urlencode($waText) }}"><i class="bi bi-whatsapp"></i> WhatsApp</a>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></script>
</body>
</html>