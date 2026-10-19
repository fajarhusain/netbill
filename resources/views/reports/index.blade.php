@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart"></i> Laporan Keuangan</h4>
</div>

<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Dari</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small">Sampai</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control"></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button></div>
    <div class="col-md-2 d-grid"><a href="{{ route('reports.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</a></div>
</form>
</div></div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Total Penerimaan</div><div class="stat-value text-success">{{ format_rupiah($totalReceived) }}</div><div class="small">{{ $totalTransactions }} transaksi</div></div></div></div>
    <div class="col-6 col-md-4"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Total Tunggakan</div><div class="stat-value text-danger">{{ format_rupiah($totalArrears) }}</div></div></div></div>
    <div class="col-6 col-md-4"><div class="card stat-card"><div class="card-body"><div class="small text-muted">Pelanggan Aktif</div><div class="stat-value">{{ $customerStats['active'] }}/{{ $customerStats['total'] }}</div><div class="small text-muted">{{ $customerStats['inactive'] }} nonaktif, {{ $customerStats['suspended'] }} ditangguhkan</div></div></div></div>
</div>

<div class="row g-3">
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Per Petugas</strong></div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Petugas</th><th>Transaksi</th><th>Total</th></tr></thead>
        <tbody>@forelse($byCollector as $r)<tr><td>{{ $r->collector->name }}</td><td>{{ $r->tx_count }}</td><td>{{ format_rupiah($r->total) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Per Metode</strong></div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Metode</th><th>Transaksi</th><th>Total</th></tr></thead>
        <tbody>@forelse($byMethod as $r)<tr><td>{{ \App\Enums\PaymentMethod::from($r->payment_method)->label() }}</td><td>{{ $r->tx_count }}</td><td>{{ format_rupiah($r->total) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">Tidak ada data.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
<div class="col-12">
    <div class="card"><div class="card-header bg-white"><strong>Setoran & Selisih Kas</strong></div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Petugas</th><th>Diharapkan</th><th>Diterima</th><th>Selisih</th><th>Status</th></tr></thead>
        <tbody>@forelse($cashSettlements as $s)<tr><td>{{ format_date($s->settlement_date) }}</td><td>{{ $s->collector->name }}</td><td>{{ format_rupiah($s->expected_amount) }}</td><td>{{ format_rupiah($s->received_amount) }}</td><td class="{{ $s->difference_amount<0?'text-danger':'' }}">{{ format_rupiah($s->difference_amount) }}</td><td>@if($s->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Tidak ada setoran.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
<div class="col-12">
    <div class="card"><div class="card-header bg-white"><strong>Tagihan Belum Lunas</strong></div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Pelanggan</th><th>Periode</th><th>Nominal</th><th>Sisa</th></tr></thead>
        <tbody>@forelse($arrears as $inv)<tr><td>{{ $inv->customer->name }} ({{ $inv->customer->customer_code }})</td><td>{{ format_period($inv->billing_period) }}</td><td>{{ format_rupiah($inv->amount) }}</td><td class="text-danger">{{ format_rupiah($inv->balance()) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">Tidak ada tunggakan.</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
</div>
@endsection
