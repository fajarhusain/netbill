@extends('layouts.app')
@section('title', $customer->name)

@php
    $waPhone = preg_replace('/[^0-9]/', '', $customer->phone ?? '');
    if (str_starts_with($waPhone, '0')) { $waPhone = '62' . substr($waPhone, 1); }
    $initial = mb_strtoupper(mb_substr($customer->name, 0, 1));
@endphp

@section('content')
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="{{ route('scan.index') }}" class="btn btn-outline-secondary btn-touch text-nowrap"><i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Scan Ulang</span></a>
    <div class="flex-grow-1 min-w-0">
        <h5 class="mb-0 text-truncate">{{ $customer->name }}</h5>
        <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
            <span>{{ $customer->customer_code }}</span>
            <span class="badge {{ $customer->status->badgeClass() }}">{{ $customer->status->label() }}</span>
        </div>
    </div>
</div>

@if(session('warning'))
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}</div>
@endif

@php
    $avatarColors = ['#0d6efd','#6610f2','#0dcaf0','#198754','#fd7e14','#dc3545','#d63384','#6f42c1'];
    $colorIdx = ord($initial) % count($avatarColors);
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="nb-avatar" style="width:56px;height:56px;background:{{ $avatarColors[$colorIdx] }};font-size:1.4rem;margin-right:.85rem;">{{ $initial }}</div>
            <div class="min-w-0">
                <div class="fw-bold fs-5 text-truncate">{{ $customer->name }}</div>
                <div class="small text-muted">{{ $customer->package?->name ?? '-' }} · {{ format_rupiah($customer->monthly_fee) }}/bln</div>
            </div>
        </div>

        <div class="row g-2 small">
            <div class="col-12">
                <i class="bi bi-geo-alt text-muted me-1"></i> {{ $customer->address ?: 'Alamat belum diisi' }}
            </div>
            <div class="col-12 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-whatsapp text-muted me-1"></i> {{ $customer->phone ?: '-' }}</span>
                @if($waPhone)
                <a href="https://wa.me/{{ $waPhone }}?text=Halo%20{{ urlencode($customer->name) }}%2C%20ini%20petugas%20penagihan%20RT%2FRW%20Net." target="_blank" class="btn btn-sm btn-success btn-touch"><i class="bi bi-chat-dots"></i> Hubungi</a>
                @endif
            </div>
        </div>

        @if($customer->notes)
        <div class="alert alert-info small mt-2 mb-0"><i class="bi bi-info-circle"></i> {{ $customer->notes }}</div>
        @endif
    </div>
</div>

<div class="card mb-3 @if($totalArrears > 0) bg-danger text-white @else bg-success text-white @endif">
    <div class="card-body text-center py-3">
        <div class="small opacity-75">Total Tunggakan</div>
        <div class="fs-3 fw-bold">{{ format_rupiah($totalArrears) }}</div>
        @if($totalArrears == 0)
        <div class="small"><i class="bi bi-check-circle"></i> Pelanggan lunas</div>
        @endif
    </div>
</div>

@if($arrears->isNotEmpty())
<a href="{{ route('scan.pay.form', $customer) }}" class="btn btn-success btn-lg btn-touch w-100 mb-3 fw-bold">
    <i class="bi bi-cash-coin"></i> Terima Pembayaran
</a>
@else
<span class="btn btn-success btn-lg w-100 mb-3 disabled">Tidak ada tunggakan</span>
@endif

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('visits.create') }}?customer_id={{ $customer->id }}" class="btn btn-outline-secondary btn-touch flex-fill"><i class="bi bi-geo-alt"></i> Catat Kunjungan</a>
</div>

<h6 class="mb-2"><i class="bi bi-file-text"></i> Tagihan Belum Lunas</h6>
@if($arrears->isEmpty())
    <div class="card mb-3"><div class="card-body text-center text-muted small py-3">Tidak ada tunggakan.</div></div>
@else
    <div class="d-md-none">
    @foreach($arrears as $inv)
        <div class="nb-list-card">
            <div class="nb-body">
                <p class="nb-title">{{ format_period($inv->billing_period) }}</p>
                <p class="nb-sub">Sisa: <span class="text-danger fw-semibold">{{ format_rupiah($inv->balance()) }}</span></p>
            </div>
            <div class="nb-end">
                <span class="badge {{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span>
            </div>
        </div>
    @endforeach
    </div>
    <div class="card d-none d-md-block">
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Periode</th><th>Nominal</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($arrears as $inv)
            <tr>
                <td>{{ format_period($inv->billing_period) }}</td>
                <td>{{ format_rupiah($inv->amount) }}</td>
                <td>{{ format_rupiah($inv->paid_amount) }}</td>
                <td class="text-danger fw-semibold">{{ format_rupiah($inv->balance()) }}</td>
                <td><span class="badge {{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

<h6 class="mt-3 mb-2"><i class="bi bi-clock-history"></i> Riwayat Pembayaran</h6>
@if($history->isEmpty())
    <div class="card"><div class="card-body text-center text-muted small py-3">Belum ada pembayaran.</div></div>
@else
    <div class="d-md-none">
    @foreach($history as $p)
        <a href="{{ route('payments.show', $p) }}" class="nb-list-card">
            <div class="nb-avatar" style="background: var(--nb-primary);"><i class="bi bi-receipt"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ $p->transaction_number }}</p>
                <p class="nb-sub">{{ format_date($p->paid_at) }}</p>
            </div>
            <div class="nb-end">
                <span class="badge {{ $p->status->badgeClass() }}">{{ format_rupiah($p->amount_received) }}</span>
            </div>
        </a>
    @endforeach
    </div>
    <div class="card d-none d-md-block">
        <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Transaksi</th><th>Tanggal</th><th>Nominal</th></tr></thead>
            <tbody>
            @foreach($history as $p)
            <tr>
                <td><a href="{{ route('payments.show', $p) }}">{{ $p->transaction_number }}</a></td>
                <td class="small">{{ format_date($p->paid_at) }}</td>
                <td>{{ format_rupiah($p->amount_received) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif
@endsection