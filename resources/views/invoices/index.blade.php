@extends('layouts.app')
@section('title', 'Daftar Tagihan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-file-text"></i> Daftar Tagihan</h4>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Tagihan</a>
</div>

<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-4">
        <label class="form-label small">Cari Pelanggan</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nama / kode">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small">Periode</label>
        <input type="month" name="period" value="{{ request('period') }}" class="form-control">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select">
            <option value="">Semua</option>
            @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(request('status')===$s->value)>{{ $s->label() }}</option>@endforeach
        </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
        <button class="btn btn-outline-primary flex-grow-1"><i class="bi bi-search"></i></button>
        <a href="{{ route('invoices.index', ['arrears' => 1]) }}" class="btn btn-outline-danger" title="Tunggakan"><i class="bi bi-exclamation-triangle"></i></a>
    </div>
</form>
</div></div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>No. Tagihan</th><th>Pelanggan</th><th>Periode</th><th>Nominal</th><th>Sisa</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
<tbody>
@forelse($invoices as $inv)
<tr>
    <td>{{ $inv->invoice_number }}</td>
    <td>{{ $inv->customer->name }}<div class="small text-muted">{{ $inv->customer->customer_code }}</div></td>
    <td>{{ format_period($inv->billing_period) }}</td>
    <td>{{ format_rupiah($inv->amount) }}</td>
    <td class="{{ $inv->balance() > 0 ? 'text-danger' : '' }}">{{ format_rupiah($inv->balance()) }}</td>
    <td><span class="badge {{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span></td>
    <td class="text-end"><a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
</tr>
@empty
<tr><td colspan="7" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif
</div>
@endsection
