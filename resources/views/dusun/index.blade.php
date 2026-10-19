@extends('layouts.app')
@section('title', 'Data Dusun')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-geo-alt"></i> Data Dusun</h4>
    <div class="btn-group">
        <a href="{{ route('customers.by-dusun') }}" class="btn btn-outline-secondary"><i class="bi bi-diagram-3"></i> Lihat per Dusun</a>
        <a href="{{ route('dusun.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Dusun</a>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nama Dusun</th><th class="text-center">Jumlah Pelanggan</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($dusun as $d)
                <tr>
                    <td><strong>{{ $d->name }}</strong></td>
                    <td class="text-center">
                        <a href="{{ route('customers.index', ['dusun' => $d->name]) }}" class="badge text-bg-primary text-decoration-none">{{ $d->customers_count }} pelanggan</a>
                    </td>
                    <td>{{ $d->notes ?: '-' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('dusun.edit', $d) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('dusun.destroy', $d) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dusun ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada dusun. <a href="{{ route('dusun.create') }}">Tambah dusun</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($dusun->hasPages())<div class="card-footer bg-white">{{ $dusun->links() }}</div>@endif
</div>
@endsection