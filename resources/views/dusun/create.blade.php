@extends('layouts.app')
@section('title', 'Tambah Dusun')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-plus-lg"></i> Tambah Dusun</h4>
    <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('dusun.store') }}">@csrf @include('dusun._form')</form>
</div></div>
@endsection