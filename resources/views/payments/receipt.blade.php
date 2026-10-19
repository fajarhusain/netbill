<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Bukti Pembayaran {{ $payment->transaction_number }}</title>
<style>
    body { font-family: 'Courier New', monospace; margin:0; padding:12px; color:#000; }
    .receipt { max-width:380px; margin:0 auto; border:1px dashed #000; padding:14px; }
    .center{text-align:center;} .bold{font-weight:bold;}
    .sep{border-top:1px dashed #000; margin:6px 0;}
    .row{display:flex; justify-content:space-between; font-size:12px; margin:2px 0;}
    @media print { .no-print{display:none;} body{padding:0;} .receipt{border:1px dashed #000;} }
</style>
</head>
<body>
<div class="receipt">
    <div class="center bold">RT/RW NET ANDA</div>
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
<div class="center no-print" style="margin-top:12px;">
    <button onclick="window.print()" style="padding:6px 14px;">Cetak</button>
</div>
</body>
</html>
