@extends('layouts.app')
@section('title', 'Pelanggan per Dusun')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Pelanggan per Dusun</h4>
    <div class="btn-group">
        <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary"><i class="bi bi-geo-alt"></i> Kelola Dusun</a>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Tampilan Daftar</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Kode / nama / telepon / alamat">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Dusun</label>
                <select name="dusun" class="form-select">
                    <option value="">Semua Dusun</option>
                    @foreach($dusunList as $d)
                        <option value="{{ $d }}" @selected(($filters['dusun'] ?? '') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

@if($dusunList->isNotEmpty())
    <div class="row g-2 mb-3">
        @foreach($dusunList as $d)
            @php $info = $summary[$d] ?? ['total' => 0, 'active' => 0]; @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('customers.by-dusun', ['dusun' => $d]) }}" class="text-decoration-none">
                    <div class="card stat-card h-100 {{ ($filters['dusun'] ?? '') === $d ? 'border-primary border-2' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="small text-muted">Dusun</div>
                                <i class="bi bi-geo-alt text-primary"></i>
                            </div>
                            <div class="stat-value text-truncate" title="{{ $d }}">{{ $d }}</div>
                            <div class="small">
                                <span class="badge text-bg-primary">{{ $info['total'] }} pelanggan</span>
                                <span class="badge text-bg-success">{{ $info['active'] }} aktif</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif

@if($grouped->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-4">Belum ada data pelanggan.</div></div>
@else
    <div class="accordion" id="dusunAccordion">
        @foreach($grouped as $dusun => $customers)
            @php
                $active = $customers->where('status', 'active')->count();
                $collapseId = 'dusun-'.md5($dusun);
                $open = (count($grouped) === 1) || (($filters['dusun'] ?? '') !== '');
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" @if(!$open) aria-expanded="false" @endif>
                        <span class="me-2"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                        <strong>{{ $dusun }}</strong>
                        <span class="badge text-bg-secondary ms-2">{{ $customers->count() }}</span>
                        <span class="badge text-bg-success ms-1">{{ $active }} aktif</span>
                    </button>
                </h2>
                <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}" data-bs-parent="#dusunAccordion">
                    <div class="accordion-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Kode</th><th>Nama</th><th>Telepon</th><th>Paket</th><th>Tarif</th><th>Status</th><th class="text-end">Aksi</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $c)
                                        <tr>
                                            <td><strong>{{ $c->customer_code }}</strong></td>
                                            <td>{{ $c->name }}<div class="small text-muted text-truncate" style="max-width:220px">{{ $c->address }}</div></td>
                                            <td>{{ $c->phone ?: '-' }}</td>
                                            <td>{{ $c->package?->name ?? '-' }}</td>
                                            <td>{{ format_rupiah($c->monthly_fee) }}</td>
                                            <td><span class="badge {{ $c->status === 'active' ? 'bg-success-subtle text-success-emphasis' : ($c->status === 'suspended' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary-emphasis') }}">{{ $c->status->label() }}</span></td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('customers.show', $c) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                                <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
