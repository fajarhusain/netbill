@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Audit Log</h4>
</div>
<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label small">Cari Alasan</label><input type="text" name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small">Aksi</label><select name="action" class="form-select"><option value="">Semua</option>@foreach($actions as $a)<option value="{{ $a }}" @selected(request('action')===$a)>{{ $a }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Entitas</label><select name="entity_type" class="form-select"><option value="">Semua</option>@foreach($entityTypes as $e)<option value="{{ $e }}" @selected(request('entity_type')===$e)>{{ $e }}</option>@endforeach</select></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
</form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
<thead class="table-light"><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Entitas</th><th>Alasan</th></tr></thead>
<tbody>
@forelse($logs as $l)
<tr>
    <td class="small">{{ format_datetime($l->created_at) }}</td>
    <td>{{ $l->user?->name ?? 'Sistem' }}</td>
    <td><code>{{ $l->action }}</code></td>
    <td class="small">{{ $l->entity_type ? $l->entity_type.' #'.$l->entity_id : '-' }}</td>
    <td class="small">{{ $l->reason ?: '-' }}</td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted py-4">Belum ada catatan audit.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($logs->hasPages())<div class="card-footer bg-white">{{ $logs->links() }}</div>@endif
</div>
@endsection
