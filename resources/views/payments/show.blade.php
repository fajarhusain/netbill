@extends('layouts.app')
@section('title', $payment->transaction_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0">{{ $payment->transaction_number }}</h4>
        <p class="text-muted small mb-0">{{ format_datetime($payment->paid_at) }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-outline-primary"><i class="bi bi-receipt"></i> Lihat Bukti</a>
        <a href="{{ route('payments.pdf', $payment) }}" class="btn btn-outline-secondary"><i class="bi bi-file-pdf"></i> PDF</a>
        @if(auth()->user()->isAdmin() && !$payment->isCancelled())
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle"></i> Batalkan</button>
        @endif
    </div>
</div>

@if($payment->isCancelled())
<div class="alert alert-danger"><strong>Dibatalkan.</strong> {{ $payment->cancellation_reason }} <div class="small">oleh {{ $payment->cancelledBy?->name }} pada {{ format_datetime($payment->cancelled_at) }}</div></div>
@endif

<div class="row g-3">
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Detail Transaksi</strong></div><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-5">No. Transaksi</dt><dd class="col-7">{{ $payment->transaction_number }}</dd>
            <dt class="col-5">Pelanggan</dt><dd class="col-7">{{ $payment->customer->name }} ({{ $payment->customer->customer_code }})</dd>
            <dt class="col-5">Petugas</dt><dd class="col-7">{{ $payment->collector->name }}</dd>
            <dt class="col-5">Waktu</dt><dd class="col-7">{{ format_datetime($payment->paid_at) }}</dd>
            <dt class="col-5">Nominal</dt><dd class="col-7">{{ format_rupiah($payment->amount_received) }}</dd>
            <dt class="col-5">Metode</dt><dd class="col-7">{{ $payment->payment_method->label() }}</dd>
            <dt class="col-5">Status</dt><dd class="col-7"><span class="badge {{ $payment->status->badgeClass() }}">{{ $payment->status->label() }}</span></dd>
            @if($payment->notes)<dt class="col-5">Catatan</dt><dd class="col-7">{{ $payment->notes }}</dd>@endif
        </dl>
    </div></div>
</div>
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Alokasi ke Tagihan</strong></div>
    <div class="table-responsive">
    @if($payment->allocations->isEmpty())
        <div class="p-4 text-center text-muted small">Tidak ada alokasi.</div>
    @else
        <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Periode</th><th>Nominal Tagihan</th><th>Dialokasikan</th><th>Sisa</th></tr></thead>
        <tbody>
        @foreach($payment->allocations as $a)
        <tr>
            <td>{{ format_period($a->invoice->billing_period) }}</td>
            <td>{{ format_rupiah($a->invoice->amount) }}</td>
            <td class="fw-semibold">{{ format_rupiah($a->amount_allocated) }}</td>
            <td>{{ format_rupiah($a->invoice->balance()) }}</td>
        </tr>
        @endforeach
        </tbody>
        </table>
    @endif
    </div></div>
</div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ route('payments.cancel', $payment) }}">@csrf
<div class="modal-header"><h5 class="modal-title">Batalkan Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="alert alert-warning small">Pembatalan akan mengembalikan status tagihan seperti semula. Tindakan ini tercatat di audit log.</div>
    <label class="form-label">Alasan <span class="text-danger">*</span></label>
    <textarea name="reason" class="form-control" rows="3" required minlength="5"></textarea>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button class="btn btn-danger">Batalkan Transaksi</button></div>
</form></div></div></div>
@endsection
