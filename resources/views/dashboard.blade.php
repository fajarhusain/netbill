@extends('layouts.app')
@section('title', 'Dashboard')

@php
    $isCollector = ! auth()->user()->isAdmin();
    $initials = collect(mb_str_split(auth()->user()->name))->take(2)->join('');
    $adminColors = ['primary'=>'#6366f1','success'=>'#10b981','danger'=>'#ef4444','warning'=>'#f59e0b','info'=>'#0ea5e9'];
@endphp

@section('content')
@if($isCollector)
    <div class="nb-hero mb-3">
        <div class="d-flex align-items-center mb-3">
            <div class="nb-avatar" style="width:48px;height:48px;background:rgba(255,255,255,.2);margin-right:.75rem;font-size:1.1rem;">{{ $initials }}</div>
            <div>
                <div class="small opacity-75">Selamat datang,</div>
                <div class="fw-bold fs-5">{{ auth()->user()->name }}</div>
            </div>
        </div>
        <div class="small opacity-75 mb-3">{{ format_datetime($now) }}</div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="small opacity-75"><i class="bi bi-cash-coin"></i> Penerimaan Hari Ini</div>
                <div class="fs-4 fw-bold">{{ format_rupiah($paidToday) }}</div>
            </div>
            <div class="col-6">
                <div class="small opacity-75"><i class="bi bi-wallet2"></i> Belum Disetor</div>
                <div class="fs-4 fw-bold">{{ format_rupiah($pendingSettlement) }}</div>
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
    <div class="nb-page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Dashboard</h4>
            <p class="text-muted small mb-0">{{ format_datetime($now) }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('scan.index') }}" class="btn btn-primary btn-touch"><i class="bi bi-qr-code-scan"></i> Scan</a>
            <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-touch"><i class="bi bi-file-text"></i> Buat Tagihan</a>
        </div>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left:4px solid {{ $adminColors['primary'] }};"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small"><i class="bi bi-people"></i> Total Pelanggan</div>
                    <span class="badge text-bg-light">{{ $activeCustomers }} aktif</span>
                </div>
                <div class="stat-value">{{ number_format($totalCustomers) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left:4px solid {{ $adminColors['success'] }};"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-cash-coin"></i> Penerimaan Hari Ini</div>
                <div class="stat-value text-success">{{ format_rupiah($paidToday) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left:4px solid {{ $adminColors['info'] }};"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-graph-up"></i> Penerimaan Bulan Ini</div>
                <div class="stat-value">{{ format_rupiah($paidThisMonth) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100" style="border-left:4px solid {{ $adminColors['danger'] }};"><div class="card-body">
                <div class="text-muted small"><i class="bi bi-exclamation-triangle"></i> Total Tunggakan</div>
                <div class="stat-value text-danger">{{ format_rupiah($arrearsTotal) }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-file-text"></i> Tagihan {{ format_period($currentPeriod) }}</div>
                <div class="stat-value">{{ format_rupiah($totalInvoicedCurrentPeriod) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-person-x"></i> Pelanggan Belum Bayar</div>
                <div class="stat-value text-warning">{{ number_format($unpaidCustomerCount) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-geo-alt"></i> Kunjungan Hari Ini</div>
                <div class="stat-value">{{ $visitsToday }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card h-100"><div class="card-body py-2">
                <div class="text-muted small"><i class="bi bi-bank"></i> Kas Belum Disetor</div>
                <div class="stat-value text-warning">{{ format_rupiah($pendingSettlement) }}</div>
            </div></div>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history"></i> Transaksi Terbaru</h6>
    <a href="{{ route('payments.index') }}" class="small text-decoration-none fw-semibold">Lihat semua →</a>
</div>

@if($recentPayments->isEmpty())
    <div class="card mb-3"><div class="card-body text-center text-muted small py-5">
        <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 opacity-50"></i>
        Belum ada transaksi.
    </div></div>
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
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>No. Transaksi</th><th>Pelanggan</th><th class="text-end">Nominal</th><th>Waktu</th></tr></thead>
                <tbody>
                @foreach($recentPayments as $p)
                    <tr>
                        <td><a href="{{ route('payments.show', $p) }}" class="text-decoration-none fw-semibold">{{ $p->transaction_number }}</a></td>
                        <td>{{ $p->customer->name }}<div class="small text-muted">{{ $p->customer->customer_code }}</div></td>
                        <td class="text-end fw-bold text-success">{{ format_rupiah($p->amount_received) }}</td>
                        <td class="small text-muted">{{ format_datetime($p->paid_at) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($isAdmin && $arrears->isNotEmpty())
<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
    <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-circle"></i> Tunggakan Terbaru</h6>
    <a href="{{ route('invoices.index') }}" class="small text-decoration-none fw-semibold">Lihat semua →</a>
</div>
<div class="d-md-none">
    @foreach($arrears as $inv)
    <a href="{{ route('customers.show', $inv->customer) }}" class="nb-list-card">
        <div class="nb-avatar" style="background:#ef4444;"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="nb-body">
            <p class="nb-title">{{ $inv->customer->name }}</p>
            <p class="nb-sub">{{ $inv->customer->customer_code }} · {{ format_period($inv->billing_period) }}</p>
        </div>
        <div class="nb-end">
            <div class="fw-bold text-danger">{{ format_rupiah($inv->balance()) }}</div>
        </div>
    </a>
    @endforeach
</div>
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Pelanggan</th><th>Periode</th><th class="text-end">Sisa</th></tr></thead>
            <tbody>
            @foreach($arrears as $inv)
                <tr>
                    <td><a href="{{ route('customers.show', $inv->customer) }}" class="text-decoration-none fw-semibold">{{ $inv->customer->name }}</a> <span class="text-muted small">{{ $inv->customer->customer_code }}</span></td>
                    <td>{{ format_period($inv->billing_period) }}</td>
                    <td class="text-end fw-bold text-danger">{{ format_rupiah($inv->balance()) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection