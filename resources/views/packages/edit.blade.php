@extends('layouts.app')
@section('title', 'Edit Paket')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Paket</h4>
    <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('packages.update', $package) }}">@csrf @method('PUT') @include('packages._form')</form>
</div></div>
@endsection
