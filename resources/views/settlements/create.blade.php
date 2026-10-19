@extends('layouts.app')
@section('title', 'Catat Setoran')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-plus-lg"></i> Catat Setoran</h4>
    <a href="{{ route('settlements.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">@include('settlements._form')</div></div>
@endsection
