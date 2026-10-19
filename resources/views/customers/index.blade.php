@extends('layouts.app')
@section('title', 'Data Pelanggan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-people"></i> Data Pelanggan</h4>
    <div class="btn-group">
        <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary" title="Kelola daftar dusun"><i class="bi bi-geo-alt"></i> Kelola Dusun</a>
        <a href="{{ route('customers.by-dusun') }}" class="btn btn-outline-secondary"><i class="bi bi-diagram-3"></i> Per Dusun</a>
        <a href="{{ route('customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Pelanggan</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Kode / nama / telepon">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Paket</label>
                <select name="package_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($packages as $p)
                        <option value="{{ $p->id }}" @selected(($filters['package_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Dusun</label>
                <select name="dusun" class="form-select">
                    <option value="">Semua</option>
                    @foreach($dusunList as $d)
                        <option value="{{ $d }}" @selected(($filters['dusun'] ?? '') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th><th>Nama</th><th>Paket</th><th>Tarif</th><th>Jatuh Tempo</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $c)
                <tr>
                    <td><strong>{{ $c->customer_code }}</strong></td>
                    <td>{{ $c->name }}<div class="small text-muted">{{ $c->phone }}</div></td>
                    <td>{{ $c->package?->name ?? '-' }}</td>
                    <td>{{ format_rupiah($c->monthly_fee) }}</td>
                    <td>Tgl {{ $c->due_day }}</td>
                    <td><span class="badge {{ $c->status === 'active' ? 'bg-success-subtle text-success-emphasis' : ($c->status === 'suspended' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary-emphasis') }}">{{ $c->status->label() }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('customers.show', $c) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('customers.card', $c) }}" class="btn btn-sm btn-outline-primary" title="Kartu QR"><i class="bi bi-qr-code"></i></a>
                        <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('customers.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data pelanggan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white">{{ $customers->links() }}</div>
    @endif
</div>
@endsection
