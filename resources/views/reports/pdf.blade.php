<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #222; font-size: 12px; margin: 0; }
    .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 12px; }
    .header h1 { font-size: 18px; margin: 0; color: #0d6efd; }
    .header .sub { font-size: 11px; color: #666; margin-top: 2px; }
    .summary { display: table; width: 100%; margin-bottom: 12px; }
    .summary-cell { display: table-cell; width: 33.33%; border: 1px solid #ddd; padding: 8px; text-align: center; }
    .summary-cell .label { font-size: 10px; color: #666; }
    .summary-cell .value { font-size: 16px; font-weight: bold; }
    h2 { font-size: 13px; background: #f0f4ff; padding: 4px 8px; border-left: 3px solid #0d6efd; margin: 14px 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th { background: #f8f9fa; text-align: left; font-size: 10px; padding: 4px 6px; border-bottom: 1px solid #ddd; }
    td { padding: 4px 6px; border-bottom: 1px solid #eee; font-size: 11px; }
    .text-right { text-align: right; }
    .text-danger { color: #dc3545; }
    .text-success { color: #198754; }
    .small { font-size: 10px; color: #666; }
    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 8px; }
</style>
</head>
<body>
<div class="header">
    <h1>Laporan Keuangan Penagihan</h1>
    <div class="sub">Periode {{ format_date($dateFrom) }} s/d {{ format_date($dateTo) }}@if($dusun) · Dusun {{ $dusun }}@endif</div>
</div>

<table class="summary">
    <tr>
        <td class="summary-cell">
            <div class="label">Total Penerimaan</div>
            <div class="value text-success">{{ format_rupiah($totalReceived) }}</div>
            <div class="small">{{ $totalTransactions }} transaksi</div>
        </td>
        <td class="summary-cell">
            <div class="label">Total Tunggakan</div>
            <div class="value text-danger">{{ format_rupiah($totalArrears) }}</div>
        </td>
        <td class="summary-cell">
            <div class="label">Pelanggan Aktif</div>
            <div class="value">{{ $customerStats['active'] }}/{{ $customerStats['total'] }}</div>
            <div class="small">{{ $customerStats['inactive'] }} nonaktif, {{ $customerStats['suspended'] }} ditangguhkan</div>
        </td>
    </tr>
</table>

<h2>Per Petugas</h2>
@if($byCollector->isEmpty())
<p class="small">Tidak ada data.</p>
@else
<table>
    <thead><tr><th>Petugas</th><th class="text-right">Transaksi</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @foreach($byCollector as $r)
    <tr><td>{{ $r->collector->name }}</td><td class="text-right">{{ $r->tx_count }}</td><td class="text-right">{{ format_rupiah($r->total) }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<h2>Per Metode Pembayaran</h2>
@if($byMethod->isEmpty())
<p class="small">Tidak ada data.</p>
@else
<table>
    <thead><tr><th>Metode</th><th class="text-right">Transaksi</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @foreach($byMethod as $r)
    <tr><td>{{ $r->method->label() }}</td><td class="text-right">{{ $r->tx_count }}</td><td class="text-right">{{ format_rupiah($r->total) }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<h2>Setoran & Selisih Kas</h2>
@if($cashSettlements->isEmpty())
<p class="small">Tidak ada setoran.</p>
@else
<table>
    <thead><tr><th>Tanggal</th><th>Petugas</th><th class="text-right">Diharapkan</th><th class="text-right">Diterima</th><th class="text-right">Selisih</th><th>Status</th></tr></thead>
    <tbody>
    @foreach($cashSettlements as $s)
    <tr>
        <td>{{ format_date($s->settlement_date) }}</td>
        <td>{{ $s->collector->name }}</td>
        <td class="text-right">{{ format_rupiah($s->expected_amount) }}</td>
        <td class="text-right">{{ format_rupiah($s->received_amount) }}</td>
        <td class="text-right {{ $s->difference_amount < 0 ? 'text-danger' : '' }}">{{ format_rupiah($s->difference_amount) }}</td>
        <td>{{ $s->isConfirmed() ? 'Dikonfirmasi' : 'Menunggu' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<h2>Tagihan Belum Lunas (Menunggak)</h2>
@if($arrears->isEmpty())
<p class="small">Tidak ada tunggakan.</p>
@else
<table>
    <thead><tr><th>Pelanggan</th><th>Dusun</th><th>Periode</th><th class="text-right">Nominal</th><th class="text-right">Sisa</th></tr></thead>
    <tbody>
    @foreach($arrears as $inv)
    <tr>
        <td>{{ $inv->customer->name }} ({{ $inv->customer->customer_code }})</td>
        <td>{{ $inv->customer->dusun ?: '-' }}</td>
        <td>{{ format_period($inv->billing_period) }}</td>
        <td class="text-right">{{ format_rupiah($inv->amount) }}</td>
        <td class="text-right text-danger">{{ format_rupiah($inv->balance()) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<h2>Tagihan Lunas</h2>
@if($paidInvoices->isEmpty())
<p class="small">Belum ada pelanggan lunas.</p>
@else
<table>
    <thead><tr><th>Pelanggan</th><th>Dusun</th><th>Periode</th><th class="text-right">Nominal</th><th class="text-right">Dibayar</th></tr></thead>
    <tbody>
    @foreach($paidInvoices as $inv)
    <tr>
        <td>{{ $inv->customer->name }} ({{ $inv->customer->customer_code }})</td>
        <td>{{ $inv->customer->dusun ?: '-' }}</td>
        <td>{{ format_period($inv->billing_period) }}</td>
        <td class="text-right">{{ format_rupiah($inv->amount) }}</td>
        <td class="text-right text-success">{{ format_rupiah($inv->paid_amount) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Laporan dibuat pada {{ format_datetime(now()) }} WIB<br>
    JRC Media — Aplikasi Penagihan RT/RW Net · Dikembangkan oleh PATITECH.ID
</div>
</body>
</html>