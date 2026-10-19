@extends('layouts.app')
@section('title', $user->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $user->name }}</h4>
    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
</div>
<div class="card"><div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-3">Username</dt><dd class="col-sm-9">{{ $user->username }}</dd>
    <dt class="col-sm-3">Role</dt><dd class="col-sm-9"><span class="badge {{ $user->isAdmin() ? 'bg-primary' : 'bg-info text-dark' }}">{{ $user->role->label() }}</span></dd>
    <dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($user->is_active)<span class="badge bg-success-subtle text-success-emphasis">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>@endif</dd>
    <dt class="col-sm-3">Transaksi</dt><dd class="col-sm-9">{{ $user->payments_count }}</dd>
    <dt class="col-sm-3">Kunjungan</dt><dd class="col-sm-9">{{ $user->collection_visits_count }}</dd>
    <dt class="col-sm-3">Setoran</dt><dd class="col-sm-9">{{ $user->cash_settlements_count }}</dd>
</dl>
</div></div>
@endsection
