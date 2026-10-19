@extends('layouts.app')
@section('title', 'Detail Kunjungan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Kunjungan</h4>
    <div class="btn-group">
        <a href="{{ route('visits.edit', $visit) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('scan.customer', $visit->customer) }}" class="btn btn-outline-primary btn-sm">Lihat Pelanggan</a>
    </div>
</div>
<div class="card"><div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-3">Pelanggan</dt><dd class="col-sm-9">{{ $visit->customer->name }} ({{ $visit->customer->customer_code }})</dd>
    <dt class="col-sm-3">Petugas</dt><dd class="col-sm-9">{{ $visit->collector->name }}</dd>
    <dt class="col-sm-3">Waktu</dt><dd class="col-sm-9">{{ format_datetime($visit->visited_at) }}</dd>
    <dt class="col-sm-3">Hasil</dt><dd class="col-sm-9">{{ $visit->result->label() }}</dd>
    @if($visit->notes)<dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">{{ $visit->notes }}</dd>@endif
</dl>
</div></div>
@endsection
