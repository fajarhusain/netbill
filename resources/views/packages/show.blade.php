@extends('layouts.app')
@section('title', $package->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $package->name }}</h4>
    <a href="{{ route('packages.edit', $package) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
</div>
<div class="card"><div class="card-body">
<dl class="row mb-0">
    <dt class="col-sm-3">Kecepatan</dt><dd class="col-sm-9">{{ $package->speed ?: '-' }}</dd>
    <dt class="col-sm-3">Tarif Bulanan</dt><dd class="col-sm-9">{{ format_rupiah($package->monthly_price) }}</dd>
    <dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($package->is_active)<span class="badge bg-success-subtle text-success-emphasis">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>@endif</dd>
    <dt class="col-sm-3">Jumlah Pelanggan</dt><dd class="col-sm-9">{{ $package->customers_count }}</dd>
    @if($package->description)<dt class="col-sm-3">Deskripsi</dt><dd class="col-sm-9">{{ $package->description }}</dd>@endif
</dl>
</div></div>
@endsection
