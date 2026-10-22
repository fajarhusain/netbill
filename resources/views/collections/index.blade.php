@extends('layouts.app')
@section('title', 'Penagihan per Dusun')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Penagihan per Dusun</h5>
    <a href="{{ route('blast.index') }}" class="btn btn-success btn-sm"><i class="bi bi-whatsapp"></i> Blast WA</a>
</div>

<div class="card bg-danger-subtle border-danger mb-3"><div class="card-body py-2">
    <div class="d-flex justify-content-between align-items-center">
        <div class="small"><i class="bi bi-exclamation-triangle"></i> Total Tunggakan Semua Dusun</div>
        <div class="fw-bold fs-5 text-danger">{{ format_rupiah($totalArrearsAll) }}</div>
    </div>
</div></div>

@if($stats->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-geo-alt fs-1 d-block mb-2 opacity-50"></i>
        Belum ada dusun terdaftar.
    </div></div>
@else
    <div class="row g-2">
        @foreach($stats as $s)
        <div class="col-12 col-md-6">
            <a href="{{ route('collections.show', $s->id) }}" class="text-decoration-none">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted small"><i class="bi bi-geo-alt"></i> Dusun</div>
                                <div class="stat-value">{{ $s->name }}</div>
                            </div>
                            @if($s->total_arrears > 0)
                            <span class="badge text-bg-danger">{{ format_rupiah($s->total_arrears) }}</span>
                            @else
                            <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> Lunas</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <span class="badge text-bg-primary">{{ $s->total }} pelanggan</span>
                            <span class="badge text-bg-success">{{ $s->active }} aktif</span>
                            @if($s->arrears_count > 0)
                            <span class="badge text-bg-warning">{{ $s->arrears_count }} menunggak</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
@endif
@endsection