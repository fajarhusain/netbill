@extends('layouts.app')
@section('title', 'Kartu Pelanggan')

@section('content')
<div class="no-print mb-3 d-flex gap-2">
    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak</button>
</div>

<div class="d-flex justify-content-center">
    <div class="card border border-dark" style="width:340px;">
        <div class="card-header text-center bg-primary text-white py-2">
            <div class="fw-bold">RT/RW NET ANDA</div>
            <div class="small">Kartu Pelanggan</div>
        </div>
        <div class="card-body text-center">
            <img src="{{ route('customers.qrcode', $customer) }}" alt="QR" style="width:200px;">
            <div class="mt-2"><strong>{{ $customer->customer_code }}</strong></div>
            <div>{{ $customer->name }}</div>
            <div class="small text-muted">Paket: {{ $customer->package?->name ?? '-' }} &middot; {{ format_rupiah($customer->monthly_fee) }}/bln</div>
        </div>
        <div class="card-footer text-center small text-muted py-1">
            Pindai QR untuk penagihan
        </div>
    </div>
</div>
@endsection
