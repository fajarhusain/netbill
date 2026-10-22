@extends('layouts.app')
@section('title', 'Riwayat Blast WhatsApp')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Blast WhatsApp</h5>
        <p class="text-muted small mb-0">Catatan pemberitahuan penagihan yang sudah dibuka/ditandai terkirim.</p>
    </div>
    <a href="{{ route('blast.index') }}" class="btn btn-success btn-sm"><i class="bi bi-whatsapp"></i> Blast Baru</a>
</div>

<div class="card mb-3"><div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-8 col-md-4">
        <label class="form-label small mb-1">Dusun</label>
        <select name="dusun" class="form-select">
            <option value="">Semua</option>
            @foreach($dusunList as $d)
            <option value="{{ $d }}" @selected(request('dusun') === $d)>{{ $d }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-4 col-md-2 d-grid">
        <button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
    </div>
</form>
</div></div>

@if($logs->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-whatsapp fs-1 d-block mb-2 opacity-50"></i>
        Belum ada blast tercatat.
    </div></div>
@else
    <div class="d-md-none">
        @foreach($logs as $log)
        <div class="nb-list-card">
            <div class="nb-avatar" style="background:#25D366"><i class="bi bi-whatsapp"></i></div>
            <div class="nb-body">
                <p class="nb-title">{{ $log->customer?->name ?? 'Pelanggan dihapus' }}</p>
                <p class="nb-sub">{{ $log->phone ?? '-' }} · {{ format_datetime($log->sent_at) }}</p>
                <p class="nb-sub">oleh {{ $log->user?->name ?? '-' }}</p>
            </div>
            <div class="nb-end">
                <div class="fw-bold text-danger">{{ format_rupiah($log->total_arrears) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card d-none d-md-block">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Waktu</th><th>Pelanggan</th><th>Nomor</th><th>Tunggakan</th><th>Oleh</th><th>Pesan</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
            <tr>
                <td class="small">{{ format_datetime($log->sent_at) }}</td>
                <td>
                    {{ $log->customer?->name ?? '—' }}
                    <div class="small text-muted">{{ $log->customer?->customer_code }}</div>
                </td>
                <td class="small">{{ $log->phone ?? '-' }}</td>
                <td>{{ format_rupiah($log->total_arrears) }}</td>
                <td class="small">{{ $log->user?->name ?? '-' }}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}"><i class="bi bi-eye"></i></button>
                    <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Pesan ke {{ $log->customer?->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><pre class="bg-light border rounded p-3 small mb-0" style="white-space:pre-wrap">{{ $log->message }}</pre></div>
                    </div></div></div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>

    @if($logs->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $logs->links() }}</div>
    @endif
@endif
@endsection
