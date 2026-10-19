@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0">{{ $customer->name }}</h4>
        <p class="text-muted small mb-0">{{ $customer->customer_code }} &middot; <span class="badge {{ $customer->status->value === 'active' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">{{ $customer->status->label() }}</span></p>
    </div>
    <div class="btn-group">
        <a href="{{ route('scan.index') }}" class="btn btn-outline-secondary"><i class="bi bi-qr-code-scan"></i> Scan Ulang</a>
        @if($arrears->isNotEmpty())
        <a href="{{ route('scan.pay.form', $customer) }}" class="btn btn-success btn-lg"><i class="bi bi-cash"></i> Terima Pembayaran</a>
        @else
        <span class="btn btn-success disabled">Tidak ada tunggakan</span>
        @endif
    </div>
</div>

@if(session('warning'))
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-5">
        <div class="card mb-3"><div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-5">Alamat</dt><dd class="col-7">{{ $customer->address ?: '-' }}</dd>
                <dt class="col-5">WhatsApp</dt><dd class="col-7">{{ $customer->phone ?: '-' }}</dd>
                <dt class="col-5">Paket</dt><dd class="col-7">{{ $customer->package?->name ?? '-' }}</dd>
                <dt class="col-5">Tarif</dt><dd class="col-7">{{ format_rupiah($customer->monthly_fee) }}</dd>
            </dl>
        </div></div>
        <div class="card bg-danger text-white"><div class="card-body text-center">
            <div class="small">Total Tunggakan</div>
            <div class="fs-4 fw-bold">{{ format_rupiah($totalArrears) }}</div>
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="card mb-3"><div class="card-header bg-white"><strong>Tagihan Belum Lunas</strong></div>
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Periode</th><th>Nominal</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($arrears as $inv)
            <tr>
                <td>{{ format_period($inv->billing_period) }}</td>
                <td>{{ format_rupiah($inv->amount) }}</td>
                <td>{{ format_rupiah($inv->paid_amount) }}</td>
                <td class="text-danger fw-semibold">{{ format_rupiah($inv->balance()) }}</td>
                <td><span class="badge {{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-success py-3">Tidak ada tunggakan. Pelanggan lunas.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div></div>

        <div class="card"><div class="card-header bg-white"><strong>Riwayat Pembayaran</strong></div>
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Transaksi</th><th>Tanggal</th><th>Nominal</th></tr></thead>
            <tbody>
            @forelse($history as $p)
            <tr>
                <td><a href="{{ route('payments.show', $p) }}">{{ $p->transaction_number }}</a></td>
                <td class="small">{{ format_date($p->paid_at) }}</td>
                <td>{{ format_rupiah($p->amount_received) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada pembayaran.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div></div>
    </div>
</div>
@endsection
