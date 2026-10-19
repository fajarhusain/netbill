@extends('layouts.app')
@section('title', 'Catat Kunjungan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-geo-alt-plus"></i> Catat Kunjungan</h4>
    <a href="{{ route('visits.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">@include('visits._form')</div></div>
@endsection
