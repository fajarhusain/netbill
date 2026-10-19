@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Dashboard</h4>
        <p class="text-muted small mb-0">{{ format_datetime($now) }}</p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="btn-group">
        <a href="{{ route('scan.index') }}" class="btn btn-primary"><i class="bi bi-qr-code-scan"></i> Scan</a>
        <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary"><i class="bi bi-file-text"></i> Buat Tagihan</a>
    </div>
    @else
    <a href="{{ route('scan.index') }}" class="btn btn-primary btn-lg"><i class="bi bi-qr-code-scan"></i> Mulai Scan</a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-people"></i> Total Pelanggan</div>
                <div class="stat-value">{{ number_format($totalCustomers) }}</div>
                <div class="small text-success">{{ $activeCustomers }} aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-file-text"></i> Tagihan {{ format_period($currentPeriod) }}</div>
                <div class="stat-value">{{ format_rupiah($totalInvoicedCurrentPeriod) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-cash-coin"></i> Penerimaan Hari Ini</div>
                <div class="stat-value text-success">{{ format_rupiah($paidToday) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-graph-up"></i> Penerimaan Bulan Ini</div>
                <div class="stat-value">{{ format_rupiah($paidThisMonth) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-exclamation-triangle"></i> Total Tunggakan</div>
                <div class="stat-value text-danger">{{ format_rupiah($arrearsTotal) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-person-x"></i> Pelanggan Belum Bayar</div>
                <div class="stat-value">{{ number_format($unpaidCustomerCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-geo-alt"></i> Kunjungan Hari Ini</div>
                <div class="stat-value">{{ $visitsToday }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-wallet2"></i> Kas Belum Disetor</div>
                <div class="stat-value text-warning">{{ format_rupiah($pendingSettlement) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <strong><i class="bi bi-clock-history"></i> Transaksi Terbaru</strong>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('payments.index') }}" class="small text-decoration-none">Lihat semua</a>
                @else
                <a href="{{ route('payments.index') }}" class="small text-decoration-none">Riwayat saya</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($recentPayments->isEmpty())
                    <div class="p-4 text-center text-muted small">Belum ada transaksi.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>No. Transaksi</th><th>Pelanggan</th><th>Nominal</th><th>Waktu</th></tr>
                        </thead>
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
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <strong><i class="bi bi-exclamation-circle"></i> Tunggakan Terbaru</strong>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('invoices.index') }}" class="small text-decoration-none">Lihat semua</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($arrears->isEmpty())
                    <div class="p-4 text-center text-muted small">Tidak ada tunggakan.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Pelanggan</th><th>Periode</th><th>Sisa</th></tr>
                        </thead>
                        <tbody>
                        @foreach($arrears as $inv)
                            <tr>
                                <td>{{ $inv->customer->name }}<div class="small text-muted">{{ $inv->customer->customer_code }}</div></td>
                                <td>{{ format_period($inv->billing_period) }}</td>
                                <td class="fw-semibold text-danger">{{ format_rupiah($inv->balance()) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
