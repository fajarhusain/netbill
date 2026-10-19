@extends('layouts.app')
@section('title', 'Manajemen Pengguna')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-gear"></i> Pengguna</h4>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>
<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-6"><label class="form-label small">Cari</label><input type="text" name="q" value="{{ request('q') }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label small">Role</label><select name="role" class="form-select"><option value="">Semua</option>@foreach($roles as $r)<option value="{{ $r->value }}" @selected(request('role')===$r->value)>{{ $r->label() }}</option>@endforeach</select></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary"><i class="bi bi-search"></i></button></div>
</form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
<tbody>
@forelse($users as $u)
<tr>
    <td>{{ $u->name }}</td>
    <td>{{ $u->username }}</td>
    <td><span class="badge {{ $u->isAdmin() ? 'bg-primary' : 'bg-info text-dark' }}">{{ $u->role->label() }}</span></td>
    <td>@if($u->is_active)<span class="badge bg-success-subtle text-success-emphasis">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>@endif</td>
    <td class="text-end"><a href="{{ route('users.show', $u) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a> <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a></td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengguna.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($users->hasPages())<div class="card-footer bg-white">{{ $users->links() }}</div>@endif
</div>
@endsection
