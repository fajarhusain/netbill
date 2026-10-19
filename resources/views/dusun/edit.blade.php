@extends('layouts.app')
@section('title', 'Edit Dusun')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Dusun</h4>
    <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('dusun.update', $dusun) }}">@csrf @method('PUT') @include('dusun._form')</form>
</div></div>
@endsection