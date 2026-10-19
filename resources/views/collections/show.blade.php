@extends('layouts.app')
@section('title', 'Dusun ' . $dusun->name)

@php
    $avatarColors = ['#0d6efd','#6610f2','#0dcaf0','#198754','#fd7e14','#dc3545','#d63384','#6f42c1'];
@endphp

@section('content')
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary btn-touch"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1 min-w-0">
        <h5 class="mb-0"><i class="bi bi-geo-alt"></i> {{ $dusun->name }}</h5>
        <p class="text-muted small mb-0">{{ $customers->count() }} pelanggan · {{ $withArrears }} menunggak</p>
    </div>
</div>

@if($totalArrears > 0)
<div class="card bg-danger text-white mb-3"><div class="card-body py-2">
    <div class="d-flex justify-content-between align-items-center">
        <div class="small">Total Tunggakan</div>
        <div class="fw-bold fs-5">{{ format_rupiah($totalArrears) }}</div>
    </div>
</div></div>
@else
<div class="card bg-success text-white mb-3"><div class="card-body py-2">
    <div class="small"><i class="bi bi-check-circle"></i> Semua pelanggan lunas.</div>
</div></div>
@endif

<div class="mb-3">
    <div class="btn-group w-100">
        <a href="{{ route('collections.show', $dusun->id) }}" class="btn btn-outline-secondary btn-touch {{ !request('filter') ? 'active' : '' }}">Semua</a>
        <a href="{{ route('collections.show', [$dusun->id, 'filter' => 'arrears']) }}" class="btn btn-outline-danger btn-touch {{ request('filter') === 'arrears' ? 'active' : '' }}">Menunggak ({{ $withArrears }})</a>
    </div>
</div>

@if($customers->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
        Tidak ada pelanggan yang sesuai.
    </div></div>
@else
    @foreach($customers as $c)
    @php
        $initial = mb_strtoupper(mb_substr($c->name, 0, 1));
        $colorIdx = ord($initial) % count($avatarColors);
        $arrears = (int) $c->invoices->sum(fn ($i) => $i->balance());
    @endphp
    <a href="{{ route('scan.customer', $c) }}" class="nb-list-card">
        <div class="nb-avatar" style="background:{{ $avatarColors[$colorIdx] }}">{{ $initial }}</div>
        <div class="nb-body">
            <p class="nb-title">{{ $c->name }}</p>
            <p class="nb-sub">{{ $c->customer_code }} · {{ $c->package?->name ?? '-' }}</p>
        </div>
        <div class="nb-end">
            @if($arrears > 0)
            <div class="fw-bold text-danger">{{ format_rupiah($arrears) }}</div>
            @else
            <div class="text-success small"><i class="bi bi-check-circle"></i> Lunas</div>
            @endif
        </div>
    </a>
    @endforeach
@endif
@endsection