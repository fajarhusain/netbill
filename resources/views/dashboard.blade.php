@extends('layouts.app')
@section('title', 'Dashboard')

@php
    $isCollector = ! auth()->user()->isAdmin();
    $initials = collect(mb_str_split(auth()->user()->name))->take(2)->join('');
@endphp

@section('content')
@if($isCollector)
    <div class="nb-hero mb-3">
        <div class="d-flex align-items-center mb-2">
            <div class="nb-avatar" style="width:44px;height:44px;background:rgba(255,255,255,.25);margin-right:.6rem;">{{ $initials }}</div>
            <div>
                <div class="small opacity-75">Halo,</div>
                <div class="fw-bold">{{ auth()->user()->name }}</div>
            </div>
        </div>
        <div class="small opacity-75 mb-3">{{ format_datetime($now) }}</div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="small opacity-75"><i class="bi bi-cash-coin"></i> Penerimaan Hari Ini</div>
                <div class="fs-5 fw-bold">{{ format_rupiah($paidToday) }}</div>
            </div>
            <div class="col-6">
                <div class="small opacity-75"><i class="bi bi-wallet2"></i> Belum Disetor</div>
                <div class="fs-5 fw-bold">{{ format_rupiah($pendingSettlement) }}</div>
            </div>
        </div>

        <a href="{{ route('scan.index') }}" class="btn btn-light btn-lg btn-touch w-100 fw-bold">
            <i class="bi bi-qr-code-scan"></i> Mulai Scan & Tagih
        </a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-graph-up"></i> Bulan Ini</div>
                <div class="stat-value">{{ format_rupiah($paidThisMonth) }}</div>
            </div></div>
        </div>
        <div class="col-6">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-geo-alt"></i> Kunjungan Hari Ini</div>
                <div class="stat-value">{{ $visitsToday }}</div>
            </div></div>
        </div>
    </div>
@else
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0">Dashboard</h4>
            <p class="text-muted small mb-0">{{ format_datetime($now) }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('scan.index') }}" class="btn btn-primary btn-touch"><i class="bi bi-qr-code-scan"></i> Scan</a>
            <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-touch"><i class="bi bi-file-text"></i> Buat Tagihan</a>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-people"></i> Total Pelanggan</div>
                <div class="stat-value">{{ number_format($totalCustomers) }}</div>
                <div class="small text-success">{{ $activeCustomers }} aktif</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-file-text"></i> Tagihan {{ format_period($currentPeriod) }}</div>
                <div class="stat-value">{{ format_rupiah($totalInvoicedCurrentPeriod) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-cash-coin"></i> Penerimaan Hari Ini</div>
                <div class="stat-value text-success">{{ format_rupiah($paidToday) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-graph-up"></i> Penerimaan Bulan Ini</div>
                <div class="stat-value">{{ format_rupiah($paidThisMonth) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-exclamation-triangle"></i> Total Tunggakan</div>
                <div class="stat-value text-danger">{{ format_rupiah($arrearsTotal) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-person-x"></i> Belum Bayar</div>
                <div class="stat-value">{{ number_format($unpaidCustomerCount) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-geo-alt"></i> Kunjungan Hari Ini</div>
                <div class="stat-value">{{ $visitsToday }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-bank"></i> Kas Belum Disetor</div>
                <div class="stat-value text-warning">{{ format_rupiah($pendingSettlement) }}</div>
            </div></div>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Transaksi Terbaru</h6>
    <a href="{{ route('payments.index') }}" class="small text-decoration-none">Lihat semua</a>
</div>

@if($recentPayments->isEmpty())
    <div class="card mb-3"><div class="card-body text-center text-muted small py-4">Belum ada transaksi.</div></div>
@else
    <div class="d-md-none">
        @foreach($recentPayments as $p)
        <a href="{{ route('payments.show', $p) }}" class="nb-list-card">
            <div class="nb-avatar" style="background: var(--nb-primary);">{{ mb_strtoupper(mb_substr($p->customer->name, 0, 1)) }}</div>
            <div class="nb-body">
                <p class="nb-title">{{ $p->customer->name }}</p>
                <p class="nb-sub">{{ $p->transaction_number }} · {{ format_datetime($p->paid_at) }}</p>
            </div>
            <div class="nb-end">
                <div class="fw-bold text-success">{{ format_rupiah($p->amount_received) }}</div>
                <span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
            </div>
        </a>
        @endforeach
    </div>
    <div class="card d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>No. Transaksi</th><th>Pelanggan</th><th>Nominal</th><th>Waktu</th></tr></thead>
                <tbody>
                @foreach($recentPayments as $p)
                    <tr>
                        <td><a href="{{ route('payments.show', $p) }}" class="text-decoration-none">{{ $p->transaction_number }}</a></td>
                        <td>{{ $p->customer->name }}<div class="small text-muted">{{ $p->customer->customer_code }}</div></td>
                        <td class="fw-semibold">{{ format_rupiah($p->amount_received) }}</td>
                        <td class="small">{{ format_datetime($p->paid_at) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection