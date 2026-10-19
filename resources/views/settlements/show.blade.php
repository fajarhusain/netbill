@extends('layouts.app')
@section('title', 'Detail Setoran')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0">Detail Setoran</h4>
    <div class="btn-group">
        @if(!$settlement->isConfirmed())<a href="{{ route('settlements.edit', $settlement) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>@endif
        @if(auth()->user()->isAdmin() && !$settlement->isConfirmed())
        <form method="POST" action="{{ route('settlements.confirm', $settlement) }}">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Konfirmasi</button></form>
        @endif
    </div>
</div>
@if($settlement->difference_amount != 0)
<div class="alert {{ $settlement->difference_amount < 0 ? 'alert-danger' : 'alert-success' }}"><i class="bi bi-info-circle"></i> Selisih kas: <strong>{{ format_rupiah($settlement->difference_amount) }}</strong> @if($settlement->difference_amount < 0)(kurang)@else(lebih)@endif</div>
@endif
<div class="card"><div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-3">Petugas</dt><dd class="col-sm-9">{{ $settlement->collector->name }}</dd>
    <dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">{{ format_date($settlement->settlement_date) }}</dd>
    <dt class="col-sm-3">Diharapkan</dt><dd class="col-sm-9">{{ format_rupiah($settlement->expected_amount) }}</dd>
    <dt class="col-sm-3">Diterima</dt><dd class="col-sm-9">{{ format_rupiah($settlement->received_amount) }}</dd>
    <dt class="col-sm-3">Selisih</dt><dd class="col-sm-9 fw-bold">{{ format_rupiah($settlement->difference_amount) }}</dd>
    <dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($settlement->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi {{ format_datetime($settlement->confirmed_at) }}</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif</dd>
    @if($settlement->notes)<dt class="col-sm-3">Catatan</dt><dd class="col-sm-9">{{ $settlement->notes }}</dd>@endif
</dl>
</div></div>
@endsection
