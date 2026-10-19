@extends('layouts.app')
@section('title', isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-plus"></i> {{ isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h4>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">@csrf @isset($user)@method('PUT')@endisset
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control" value="{{ old('username', $user->username ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Kata Sandi {{ isset($user) ? '(kosongkan jika tidak diubah)' : '*' }}</label>
        <input type="password" name="password" class="form-control" @if(!isset($user))required @endif>
    </div>
    <div class="col-md-6">
        <label class="form-label">Ulangi Kata Sandi</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-select" required>@foreach($roles as $r)<option value="{{ $r->value }}" @selected(old('role', $user->role->value ?? 'collector')===$r->value)>{{ $r->label() }}</option>@endforeach</select>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="ua" {{ old('is_active', isset($user) ? $user->is_active : true) ? 'checked' : '' }}><label class="form-check-label" for="ua">Aktif</label></div>
    </div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button></div>
</div>
</form>
</div></div>
@endsection
