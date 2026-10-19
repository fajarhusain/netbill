@extends('layouts.app')
@section('title', 'Laporan')

@php
    $exportParams = ['date_from' => $dateFrom, 'date_to' => $dateTo];
    if ($dusun) { $exportParams['dusun'] = $dusun; }
    if ($statusFilter) { $exportParams['status'] = $statusFilter; }
    $showArrears = in_array($statusFilter, [null, 'arrears']);
    $showPaid = in_array($statusFilter, [null, 'paid']);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Laporan Keuangan</h5>
</div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-6 col-md-2"><label class="form-label small mb-1">Dari</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control"></div>
    <div class="col-6 col-md-2"><label class="form-label small mb-1">Sampai</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control"></div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Dusun</label>
        <select name="dusun" class="form-select">
            <option value="">Semua Dusun</option>
            @foreach($dusunList as $d)
                <option value="{{ $d }}" @selected($dusun === $d)>{{ $d }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select">
            <option value="">Semua</option>
            <option value="arrears" @selected($statusFilter === 'arrears')>Menunggak</option>
            <option value="paid" @selected($statusFilter === 'paid')>Lunas</option>
        </select>
    </div>
    <div class="col-6 col-md-1 d-grid"><button class="btn btn-outline-primary btn-touch"><i class="bi bi-funnel"></i></button></div>
    <div class="col-6 col-md-3">
        <div class="btn-group w-100">
            <a href="{{ route('reports.export', $exportParams) }}" class="btn btn-outline-secondary btn-touch"><i class="bi bi-filetype-csv"></i> CSV</a>
            <a href="{{ route('reports.export.excel', $exportParams) }}" class="btn btn-outline-secondary btn-touch"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
            <a href="{{ route('reports.export.pdf', $exportParams) }}" class="btn btn-outline-danger btn-touch"><i class="bi bi-file-pdf"></i> PDF</a>
        </div>
    </div>
</form>
@if($dusun)
<div class="mt-2">
    <span class="badge text-bg-primary"><i class="bi bi-geo-alt"></i> {{ $dusun }}</span>
    <a href="{{ route('reports.index', array_merge($exportParams, ['dusun' => ''])) }}" class="small text-decoration-none">Hapus filter dusun</a>
</div>
@endif
</div></div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body py-2">
        <div class="small text-muted"><i class="bi bi-cash-coin"></i> Penerimaan</div>
        <div class="stat-value text-success">{{ format_rupiah($totalReceived) }}</div>
        <div class="small">{{ $totalTransactions }} transaksi</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body py-2">
        <div class="small text-muted"><i class="bi bi-exclamation-triangle"></i> Tunggakan</div>
        <div class="stat-value text-danger">{{ format_rupiah($totalArrears) }}</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body py-2">
        <div class="small text-muted"><i class="bi bi-check-circle"></i> Pelanggan Lunas</div>
        <div class="stat-value text-success">{{ $paidInvoices->count() }}</div>
    </div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body py-2">
        <div class="small text-muted"><i class="bi bi-people"></i> Pelanggan Aktif</div>
        <div class="stat-value">{{ $customerStats['active'] }}/{{ $customerStats['total'] }}</div>
    </div></div></div>
</div>

<div class="row g-3">
<div class="col-md-6">
    <div class="card h-100"><div class="card-header bg-white"><strong>Per Petugas</strong></div>
    <div class="card-body p-0">
        @forelse($byCollector as $r)
        <div class="nb-list-card mb-0 rounded-0 border-bottom">
            <div class="nb-avatar" style="background:#6610f2;"><i class="bi bi-person"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ $r->collector->name }}</p>
                <p class="nb-sub">{{ $r->tx_count }} transaksi</p>
            </div>
            <div class="nb-end">
                <div class="fw-bold">{{ format_rupiah($r->total) }}</div>
            </div>
        </div>
        @empty
        <div class="p-4 text-center text-muted small">Tidak ada data.</div>
        @endforelse
    </div></div>
</div>
<div class="col-md-6">
    <div class="card h-100"><div class="card-header bg-white"><strong>Per Metode</strong></div>
    <div class="card-body p-0">
        @forelse($byMethod as $r)
        <div class="nb-list-card mb-0 rounded-0 border-bottom">
            <div class="nb-avatar" style="background:#0d6efd;"><i class="bi bi-credit-card"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ $r->method->label() }}</p>
                <p class="nb-sub">{{ $r->tx_count }} transaksi</p>
            </div>
            <div class="nb-end">
                <div class="fw-bold">{{ format_rupiah($r->total) }}</div>
            </div>
        </div>
        @empty
        <div class="p-4 text-center text-muted small">Tidak ada data.</div>
        @endforelse
    </div></div>
</div>

<div class="col-12">
    <div class="card"><div class="card-header bg-white"><strong>Setoran & Selisih Kas</strong></div>
    <div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Petugas</th><th class="text-end">Diharapkan</th><th class="text-end">Diterima</th><th class="text-end">Selisih</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($cashSettlements as $s)
        <tr>
            <td>{{ format_date($s->settlement_date) }}</td>
            <td>{{ $s->collector->name }}</td>
            <td class="text-end">{{ format_rupiah($s->expected_amount) }}</td>
            <td class="text-end">{{ format_rupiah($s->received_amount) }}</td>
            <td class="text-end {{ $s->difference_amount < 0 ? 'text-danger' : '' }}">{{ format_rupiah($s->difference_amount) }}</td>
            <td>@if($s->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada setoran.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div></div>
</div>

@if($showArrears)
<div class="col-12">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-exclamation-triangle text-danger"></i> Pelanggan Menunggak</strong>
            <span class="badge text-bg-danger">{{ $arrears->count() }} tagihan</span>
        </div>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Pelanggan</th><th>Dusun</th><th>Periode</th><th class="text-end">Nominal</th><th class="text-end">Sisa</th></tr></thead>
            <tbody>
            @forelse($arrears as $inv)
            <tr>
                <td>{{ $inv->customer->name }} <span class="text-muted small">{{ $inv->customer->customer_code }}</span></td>
                <td>{{ $inv->customer->dusun ?: '-' }}</td>
                <td>{{ format_period($inv->billing_period) }}</td>
                <td class="text-end">{{ format_rupiah($inv->amount) }}</td>
                <td class="text-end text-danger fw-semibold">{{ format_rupiah($inv->balance()) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-success py-3"><i class="bi bi-check-circle"></i> Tidak ada tunggakan.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endif

@if($showPaid)
<div class="col-12">
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-check-circle text-success"></i> Pelanggan Lunas</strong>
            <span class="badge text-bg-success">{{ $paidInvoices->count() }} tagihan</span>
        </div>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Pelanggan</th><th>Dusun</th><th>Periode</th><th class="text-end">Nominal</th><th class="text-end">Dibayar</th></tr></thead>
            <tbody>
            @forelse($paidInvoices as $inv)
            <tr>
                <td>{{ $inv->customer->name }} <span class="text-muted small">{{ $inv->customer->customer_code }}</span></td>
                <td>{{ $inv->customer->dusun ?: '-' }}</td>
                <td>{{ format_period($inv->billing_period) }}</td>
                <td class="text-end">{{ format_rupiah($inv->amount) }}</td>
                <td class="text-end text-success fw-semibold">{{ format_rupiah($inv->paid_amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada pelanggan lunas.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endif
</div>
@endsection