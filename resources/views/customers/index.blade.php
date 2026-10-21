@extends('layouts.app')
@section('title', 'Data Pelanggan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-people"></i> Data Pelanggan</h4>
    <div class="btn-group">
        <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary" title="Kelola daftar dusun"><i class="bi bi-geo-alt"></i> Kelola Dusun</a>
        <a href="{{ route('customers.by-dusun') }}" class="btn btn-outline-secondary"><i class="bi bi-diagram-3"></i> Per Dusun</a>
        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</button>
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
                        <form action="{{ route('customers.destroy', $c) }}" method="POST" class="d-inline" id="deleteForm-{{ $c->id }}">
                            @csrf @method('DELETE')
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCustomer({{ $c->id }}, '{{ addslashes($c->name) }}')"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="importModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">@csrf
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up"></i> Import Pelanggan dari Excel</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle"></i> Unduh template terlebih dahulu, isi data pelanggan, lalu unggah kembali.
            Format mendukung <strong>.xlsx</strong>, <strong>.xls</strong>, dan <strong>.csv</strong>.
        </div>
        <a href="{{ route('customers.import.template') }}" class="btn btn-outline-primary btn-sm mb-3"><i class="bi bi-download"></i> Unduh Template Excel</a>
        <label class="form-label">Pilih File <span class="text-danger">*</span></label>
        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        <div class="form-text small mt-2">
            Kolom: <strong>nama</strong> (wajib), kode, telepon, alamat, dusun, paket, <strong>tarif</strong> (wajib),
            tanggal_mulai, tanggal_jatuh_tempo, status, catatan.
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="bi bi-upload"></i> Import</button></div>
</form></div></div>
</div>
@endsection

@push('scripts')
<script>
function confirmDeleteCustomer(id, name) {
    Swal.fire({
        icon: 'warning',
        title: 'Hapus Pelanggan?',
        html: 'Pelanggan <strong>' + name + '</strong> akan dihapus permanen.<br>Pelanggan dengan transaksi pembayaran tidak dapat dihapus.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById('deleteForm-' + id).submit();
        }
    });
}
</script>
@endpush
