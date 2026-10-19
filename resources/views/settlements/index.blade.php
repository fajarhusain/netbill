@extends('layouts.app')
@section('title', 'Setoran Petugas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-wallet2"></i> {{ auth()->user()->isAdmin() ? 'Setoran Petugas' : 'Setoran Saya' }}</h5>
    <a href="{{ route('settlements.create') }}" class="btn btn-primary btn-touch"><i class="bi bi-plus-lg"></i> Catat</a>
</div>

<div class="card @if($pendingCash > 0) bg-warning-subtle border-warning @else bg-success-subtle border-success @endif mb-3">
    <div class="card-body d-flex align-items-center">
        <i class="bi @if($pendingCash > 0) bi-exclamation-triangle @else bi-check-circle @endif fs-3 me-3"></i>
        <div class="flex-grow-1">
            <div class="small text-muted">Kas tunai belum disetor</div>
            <div class="fw-bold fs-5">{{ format_rupiah($pendingCash) }}</div>
        </div>
    </div>
</div>

@if($settlements->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-bank fs-1 d-block mb-2 opacity-50"></i>
        Belum ada setoran.
    </div></div>
@else
    <div class="d-md-none">
        @foreach($settlements as $s)
        <a href="{{ route('settlements.show', $s) }}" class="nb-list-card">
            <div class="nb-avatar" style="background:#198754;"><i class="bi bi-bank"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ format_date($s->settlement_date) }}</p>
                <p class="nb-sub">Diterima: {{ format_rupiah($s->received_amount) }} @if($s->difference_amount != 0) · <span class="{{ $s->difference_amount < 0 ? 'text-danger' : 'text-success' }}">{{ format_rupiah($s->difference_amount) }}</span>@endif</p>
            </div>
            <div class="nb-end">
                @if($s->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif
            </div>
        </a>
        @endforeach
    </div>

    <div class="card d-none d-md-block">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Tanggal</th>@if(auth()->user()->isAdmin())<th>Petugas</th>@endif<th>Diharapkan</th><th>Diterima</th><th>Selisih</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @foreach($settlements as $s)
            <tr>
                <td>{{ format_date($s->settlement_date) }}</td>
                @if(auth()->user()->isAdmin())<td>{{ $s->collector->name }}</td>@endif
                <td>{{ format_rupiah($s->expected_amount) }}</td>
                <td>{{ format_rupiah($s->received_amount) }}</td>
                <td class="{{ $s->difference_amount < 0 ? 'text-danger' : ($s->difference_amount > 0 ? 'text-success' : '') }}">{{ format_rupiah($s->difference_amount) }}</td>
                <td>@if($s->isConfirmed())<span class="badge bg-success-subtle text-success-emphasis">Dikonfirmasi</span>@else<span class="badge bg-warning-subtle text-warning-emphasis">Menunggu</span>@endif</td>
                <td class="text-end"><a href="{{ route('settlements.show', $s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

@if($settlements->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $settlements->links() }}</div>
@endif
@endsection