@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-stack"></i> {{ auth()->user()->isAdmin() ? 'Transaksi' : 'Riwayat Transaksi' }}</h5>
</div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
        <label class="form-label small mb-1">Cari</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="No. transaksi / pelanggan">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Dari</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Sampai</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Metode</label>
        <select name="method" class="form-select"><option value="">Semua</option>@foreach($methods as $m)<option value="{{ $m->value }}" @selected(request('method')===$m->value)>{{ $m->label() }}</option>@endforeach</select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select"><option value="">Semua</option>@foreach($statuses as $s)<option value="{{ $s->value }}" @selected(request('status')===$s->value)>{{ $s->label() }}</option>@endforeach</select>
    </div>
    @if(auth()->user()->isAdmin() && $collectors->isNotEmpty())
    <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Petugas</label>
        <select name="collector_id" class="form-select"><option value="">Semua</option>@foreach($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
    </div>
    @endif
    <div class="col-12 col-md-1 d-grid"><button class="btn btn-outline-primary btn-touch"><i class="bi bi-search"></i></button></div>
</form>
</div></div>

@php
    $colors = ['#0d6efd','#6610f2','#0dcaf0','#198754','#fd7e14','#dc3545','#d63384','#6f42c1'];
@endphp

@if($payments->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 opacity-50"></i>
        Belum ada transaksi.
    </div></div>
@else
    <div class="d-md-none">
        @foreach($payments as $p)
        <a href="{{ route('payments.show', $p) }}" class="nb-list-card">
            <div class="nb-avatar" style="background:{{ $colors[ord(mb_strtoupper(mb_substr($p->customer->name,0,1))) % count($colors)] }}">{{ mb_strtoupper(mb_substr($p->customer->name,0,1)) }}</div>
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
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>No. Transaksi</th><th>Pelanggan</th><th>Nominal</th><th>Metode</th><th>Petugas</th><th>Waktu</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @foreach($payments as $p)
            <tr>
                <td><strong>{{ $p->transaction_number }}</strong></td>
                <td>{{ $p->customer->name }}<div class="small text-muted">{{ $p->customer->customer_code }}</div></td>
                <td>{{ format_rupiah($p->amount_received) }}</td>
                <td>{{ $p->payment_method->label() }}</td>
                <td>{{ $p->collector->name }}</td>
                <td class="small">{{ format_datetime($p->paid_at) }}</td>
                <td><span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span></td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    <a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-outline-primary" title="Bukti"><i class="bi bi-receipt"></i></a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

@if($payments->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $payments->links() }}</div>
@endif
@endsection