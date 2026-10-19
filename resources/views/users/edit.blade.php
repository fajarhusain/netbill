@extends('layouts.app')
@section('title', 'Edit Pengguna')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Pengguna</h4>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">@include('users._form')</div></div>
@endsection
