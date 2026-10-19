@extends('layouts.app')
@section('title', 'Kunjungan Penagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Kunjungan</h5>
    <a href="{{ route('visits.create') }}" class="btn btn-primary btn-touch"><i class="bi bi-plus-lg"></i> Catat</a>
</div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari Pelanggan</label><input type="text" name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-6 col-md-3"><label class="form-label small mb-1">Hasil</label><select name="result" class="form-select"><option value="">Semua</option>@foreach($results as $r)<option value="{{ $r->value }}" @selected(request('result')===$r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    @if(auth()->user()->isAdmin() && $collectors->isNotEmpty())<div class="col-6 col-md-3"><label class="form-label small mb-1">Petugas</label><select name="collector_id" class="form-select"><option value="">Semua</option>@foreach($collectors as $c)<option value="{{ $c->id }}" @selected(request('collector_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>@endif
    <div class="col-12 col-md-2 d-grid"><button class="btn btn-outline-primary btn-touch"><i class="bi bi-search"></i> Filter</button></div>
</form>
</div></div>

@php
    $resultIcons = ['paid'=>'check-circle text-success','not_home'=>'door-closed text-warning','recall_requested'=>'arrow-repeat text-info','cannot_pay'=>'cash-coin text-warning','address_not_found'=>'exclamation-circle text-danger','other'=>'question-circle text-muted'];
@endphp

@if($visits->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-geo-alt fs-1 d-block mb-2 opacity-50"></i>
        Belum ada kunjungan.
    </div></div>
@else
    <div class="d-md-none">
        @foreach($visits as $v)
        <a href="{{ route('visits.show', $v) }}" class="nb-list-card">
            <div class="nb-avatar" style="background:#6c757d;"><i class="bi bi-{{ $resultIcons[$v->result->value] ?? 'geo-alt' }}"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ $v->customer->name }}</p>
                <p class="nb-sub">{{ format_datetime($v->visited_at) }}</p>
            </div>
            <div class="nb-end">
                <span class="small text-muted">{{ $v->result->label() }}</span>
            </div>
        </a>
        @endforeach
    </div>

    <div class="card d-none d-md-block">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Waktu</th><th>Pelanggan</th><th>Petugas</th><th>Hasil</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @foreach($visits as $v)
            <tr>
                <td class="small">{{ format_datetime($v->visited_at) }}</td>
                <td>{{ $v->customer->name }}<div class="small text-muted">{{ $v->customer->customer_code }}</div></td>
                <td>{{ $v->collector->name }}</td>
                <td>{{ $v->result->label() }}</td>
                <td class="small">{{ Illuminate\Support\Str::limit($v->notes, 40) }}</td>
                <td class="text-end"><a href="{{ route('visits.show', $v) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

@if($visits->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $visits->links() }}</div>
@endif
@endsection