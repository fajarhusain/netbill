@extends('layouts.app')
@section('title', 'Paket Internet')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-boxes"></i> Paket Internet</h4>
    <a href="{{ route('packages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Paket</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Kecepatan</th><th>Tarif</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($packages as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->speed ?: '-' }}</td>
                    <td>{{ format_rupiah($p->monthly_price) }}</td>
                    <td>@if($p->is_active)<span class="badge bg-success-subtle text-success-emphasis">Aktif</span>@else<span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('packages.show', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('packages.edit', $p) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('packages.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada paket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($packages->hasPages())<div class="card-footer bg-white">{{ $packages->links() }}</div>@endif
</div>
@endsection
