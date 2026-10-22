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
        @if(! $payment->isCancelled() && (auth()->user()->isAdmin() || $payment->collector_id === auth()->id()))
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendReceiptModal"><i class="bi bi-image"></i> Kirim Bukti</button>
        @endif
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

@if($payment->receipt_image_path)
<div class="card mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-image"></i> Bukti Pembayaran (Gambar)</strong>
        <a href="{{ route('payments.receipt.image', ['payment' => $payment, 'download' => 1]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Unduh</a>
    </div>
    <div class="card-body">
        <img src="{{ route('payments.receipt.image', $payment) }}" alt="Bukti pembayaran {{ $payment->transaction_number }}" class="img-fluid rounded border">
        <p class="small text-muted mt-2 mb-0">
            Dikirim {{ format_datetime($payment->receipt_sent_at) }}
            @if($payment->receiptSentBy) oleh {{ $payment->receiptSentBy->name }} @endif
        </p>
    </div>
</div>
@endif

<div class="modal fade" id="sendReceiptModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<form method="POST" action="{{ route('payments.send-receipt', $payment) }}" id="send-receipt-form"
      data-existing-image="{{ $payment->receipt_image_path ? route('payments.receipt.image', $payment) : '' }}"
      data-existing-meta="{{ $payment->receipt_image_path ? 'Dikirim '.format_datetime($payment->receipt_sent_at).' oleh '.($payment->receiptSentBy?->name ?? $payment->collector->name) : '' }}"
      data-existing-download="{{ route('payments.receipt.image', ['payment' => $payment, 'download' => 1]) }}"
      data-whatsapp="{{ $payment->whatsappReceiptUrl() ?? '' }}">@csrf
<div class="modal-header"><h5 class="modal-title">Kirim Bukti Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <p class="small text-muted">Bukti akan dibuat sebagai gambar dengan watermark nama penagih dan tanggal pengiriman.</p>
    <div id="receipt-preview" class="text-center d-none mb-3">
        <img src="" alt="Pratinjau bukti" class="img-fluid rounded border" id="receipt-preview-img">
        <p class="small text-muted mt-2 mb-0" id="receipt-preview-meta"></p>
    </div>
    <div id="receipt-preview-loading" class="text-center py-3 d-none">
        <span class="spinner-border spinner-border-sm"></span> <span class="small">Membuat bukti…</span>
    </div>
    <div id="receipt-preview-error" class="alert alert-danger py-2 small d-none"></div>
    <div class="d-flex gap-2">
        <a href="{{ route('payments.receipt.image', ['payment' => $payment, 'download' => 1]) }}" class="btn btn-outline-secondary d-none" id="receipt-download"><i class="bi bi-download"></i> Unduh</a>
        <a href="#" target="_blank" rel="noopener" class="btn btn-outline-success d-none" id="receipt-wa"><i class="bi bi-whatsapp"></i> Kirim via WhatsApp</a>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    <button type="submit" class="btn btn-success" id="send-receipt-submit"><i class="bi bi-send"></i> Kirim Bukti</button>
</div>
</form>
</div></div></div>

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

@push('scripts')
<script>
(function () {
    const form = document.getElementById('send-receipt-form');
    if (!form) return;

    const submitBtn = document.getElementById('send-receipt-submit');
    const loading = document.getElementById('receipt-preview-loading');
    const preview = document.getElementById('receipt-preview');
    const previewImg = document.getElementById('receipt-preview-img');
    const previewMeta = document.getElementById('receipt-preview-meta');
    const errorBox = document.getElementById('receipt-preview-error');
    const downloadBtn = document.getElementById('receipt-download');
    const waBtn = document.getElementById('receipt-wa');

    const existingImage = form.dataset.existingImage;
    if (existingImage) {
        previewImg.src = existingImage;
        previewMeta.textContent = form.dataset.existingMeta;
        downloadBtn.href = form.dataset.existingDownload;
        if (form.dataset.whatsapp) {
            waBtn.href = form.dataset.whatsapp;
            waBtn.classList.remove('d-none');
        }
        preview.classList.remove('d-none');
        downloadBtn.classList.remove('d-none');
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Kirim Ulang';
        submitBtn.classList.replace('btn-success', 'btn-outline-success');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim…';
        loading.classList.remove('d-none');
        errorBox.classList.add('d-none');

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Gagal membuat bukti pembayaran.');

            previewImg.src = data.image_url + '?t=' + Date.now();
            previewMeta.textContent = 'Dikirim ' + data.sent_at + ' oleh ' + data.sent_by;
            downloadBtn.href = data.download_url;

            if (data.whatsapp_url) {
                waBtn.href = data.whatsapp_url;
                waBtn.classList.remove('d-none');
            }

            preview.classList.remove('d-none');
            downloadBtn.classList.remove('d-none');
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Kirim Ulang';
            submitBtn.disabled = false;
            submitBtn.classList.replace('btn-success', 'btn-outline-success');
        } catch (err) {
            errorBox.textContent = err.message;
            errorBox.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send"></i> Kirim Bukti';
        } finally {
            loading.classList.add('d-none');
        }
    });
})();
</script>
@endpush
@endsection
