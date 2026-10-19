@extends('layouts.app')
@section('title', 'Kunjungan Penagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-geo-alt"></i> Kunjungan Penagihan</h4>
    <a href="{{ route('visits.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Kunjungan</a>
</div>

<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Cari Pelanggan</label><input type="text" name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small">Hasil</label><select name="result" class="form-select"><option value="">Semua</option>@foreach($results as $r)<option value="{{ $r->value }}" @selected(request('result')===$r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    @if(auth()->user()->isAdmin() && $collectors->isNotEmpty())<div class="col-md-3"><label class="form-label small">Petugas</label><select name="collector_id" class="form-select"><option value="">Semua</option>@foreach($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>@endif
    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
</form>
</div></div>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Waktu</th><th>Pelanggan</th><th>Petugas</th><th>Hasil</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
<tbody>
@forelse($visits as $v)
<tr>
    <td class="small">{{ format_datetime($v->visited_at) }}</td>
    <td>{{ $v->customer->name }}<div class="small text-muted">{{ $v->customer->customer_code }}</div></td>
    <td>{{ $v->collector->name }}</td>
    <td>{{ $v->result->label() }}</td>
    <td class="small">{{ Illuminate\Support\Str::limit($v->notes, 40) }}</td>
    <td class="text-end"><a href="{{ route('visits.show', $v) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-4">Belum ada kunjungan.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($visits->hasPages())<div class="card-footer bg-white">{{ $visits->links() }}</div>@endif
</div>
@endsection
