@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-cash-stack"></i> {{ auth()->user()->isAdmin() ? 'Transaksi' : 'Riwayat Transaksi Saya' }}</h4>
</div>

<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
        <label class="form-label small">Cari</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="No. transaksi / pelanggan">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small">Dari</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small">Sampai</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small">Metode</label>
        <select name="method" class="form-select"><option value="">Semua</option>@foreach($methods as $m)<option value="{{ $m->value }}" @selected(request('method')===$m->value)>{{ $m->label() }}</option>@endforeach</select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select"><option value="">Semua</option>@foreach($statuses as $s)<option value="{{ $s->value }}" @selected(request('status')===$s->value)>{{ $s->label() }}</option>@endforeach</select>
    </div>
    @if(auth()->user()->isAdmin() && $collectors->isNotEmpty())
    <div class="col-6 col-md-3">
        <label class="form-label small">Petugas</label>
        <select name="collector_id" class="form-select"><option value="">Semua</option>@foreach($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
    </div>
    @endif
    <div class="col-12 col-md-1 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
</form>
</div></div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>No. Transaksi</th><th>Pelanggan</th><th>Nominal</th><th>Metode</th><th>Petugas</th><th>Waktu</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
<tbody>
@forelse($payments as $p)
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
@empty
<tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($payments->hasPages())<div class="card-footer bg-white">{{ $payments->links() }}</div>@endif
</div>
@endsection
