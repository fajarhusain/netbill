@extends('layouts.app')
@section('title', $invoice->invoice_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0">{{ $invoice->invoice_number }}</h4>
        <p class="text-muted small mb-0">Periode {{ format_period($invoice->billing_period) }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('customers.show', $invoice->customer) }}" class="btn btn-outline-secondary btn-sm">Lihat Pelanggan</a>
        @if(in_array($invoice->status->value, ['unpaid','partial']))
        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle"></i> Batalkan</button>
        @endif
    </div>
</div>

<div class="row g-3">
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Detail Tagihan</strong></div><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-5">Pelanggan</dt><dd class="col-7">{{ $invoice->customer->name }} ({{ $invoice->customer->customer_code }})</dd>
            <dt class="col-5">Paket</dt><dd class="col-7">{{ $invoice->customer->package?->name ?? '-' }}</dd>
            <dt class="col-5">Diterbitkan</dt><dd class="col-7">{{ format_date($invoice->issued_at) }}</dd>
            <dt class="col-5">Jatuh Tempo</dt><dd class="col-7">{{ format_date($invoice->due_date) }}</dd>
            <dt class="col-5">Nominal</dt><dd class="col-7">{{ format_rupiah($invoice->amount) }}</dd>
            <dt class="col-5">Dibayar</dt><dd class="col-7">{{ format_rupiah($invoice->paid_amount) }}</dd>
            <dt class="col-5">Sisa</dt><dd class="col-7 fw-semibold text-danger">{{ format_rupiah($invoice->balance()) }}</dd>
            <dt class="col-5">Status</dt><dd class="col-7"><span class="badge {{ $invoice->status->badgeClass() }}">{{ $invoice->status->label() }}</span></dd>
        </dl>
    </div></div>
</div>
<div class="col-md-6">
    <div class="card"><div class="card-header bg-white"><strong>Alokasi Pembayaran</strong></div><div class="card-body p-0">
    @if($invoice->allocations->isEmpty())
        <div class="p-4 text-center text-muted small">Belum ada pembayaran.</div>
    @else
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>No. Transaksi</th><th>Petugas</th><th>Nominal</th><th>Tanggal</th></tr></thead>
            <tbody>
            @foreach($invoice->allocations as $a)
                <tr>
                    <td><a href="{{ route('payments.show', $a->payment) }}">{{ $a->payment->transaction_number }}</a></td>
                    <td>{{ $a->payment->collector->name }}</td>
                    <td>{{ format_rupiah($a->amount_allocated) }}</td>
                    <td class="small">{{ format_date($a->payment->paid_at) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    </div></div>
</div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('invoices.cancel', $invoice) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">Batalkan Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="alert alert-warning small">Tagihan dengan pembayaran tidak dapat dibatalkan. Batalkan transaksi pembayaran terlebih dahulu.</div>
        <label class="form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control" rows="3" required minlength="5"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Batalkan Tagihan</button></div>
</form></div></div>
</div>
@endsection
