@extends('layouts.app')
@section('title', 'Setoran Petugas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="bi bi-bank"></i> {{ auth()->user()->isAdmin() ? 'Setoran Petugas' : 'Setoran Saya' }}</h4>
    <a href="{{ route('settlements.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Setoran</a>
</div>

<div class="card bg-warning-subtle border-warning mb-3"><div class="card-body d-flex justify-content-between align-items-center">
    <div><i class="bi bi-wallet2"></i> Kas tunai belum disetor</div>
    <div class="fw-bold fs-5">{{ format_rupiah($pendingCash) }}</div>
</div></div>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Tanggal</th>@if(auth()->user()->isAdmin())<th>Petugas</th>@endif<th>Diharapkan</th><th>Diterima</th><th>Selisih</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
<tbody>
@forelse($settlements as $s)
<tr>
    <td>{{ format_date($s->settlement_date) }}</td>
    @if(auth()->user()->isAdmin())<td>{{ $s->collector->name }}</td>@endif
    <td>{{ format_rupiah($s->expected_amount) }}</td>
    <td>{{ format_rupiah($s->received_amount) }}</td>
    <td class="{{ $s->difference_amount < 0 ? 'text-danger' : ($s->difference_amount > 0 ? 'text-success' : '') }}">{{ format_rupiah($s->difference_amount) }}</td>
    <td>@if($s->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif</td>
    <td class="text-end"><a href="{{ route('settlements.show', $s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
</tr>
@empty
<tr><td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="text-center text-muted py-4">Belum ada setoran.</td></tr>
@endforelse
</tbody>
</table>
</div>
@if($settlements->hasPages())<div class="card-footer bg-white">{{ $settlements->links() }}</div>@endif
</div>
@endsection
