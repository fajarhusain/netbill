@extends('layouts.app')
@section('title', 'Edit Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Pelanggan</h4>
    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        @include('customers._form')
    </form>
</div></div>
@endsection
