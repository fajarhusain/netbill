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
        <a href="#bukti" class="btn btn-success"><i class="bi bi-whatsapp"></i> Kirim Bukti</a>
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

@php
    $publicUrl = $payment->publicReceiptUrl();
    $waUrl = $payment->isCancelled() ? null : $payment->whatsappReceiptUrl($publicUrl);
@endphp

<div class="card mt-3" id="bukti">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong><i class="bi bi-image"></i> Bukti Pembayaran (Gambar)</strong>
        <div class="btn-group">
            @if($payment->receipt_image_path)
            <a href="{{ route('payments.receipt.image', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> Buka</a>
            @if($publicUrl)
            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-link="{{ $publicUrl }}"><i class="bi bi-link-45deg"></i> Salin Link</button>
            @endif
            <a href="{{ route('payments.receipt.image', ['payment' => $payment, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Unduh</a>
            @if($waUrl)
            <button type="button" class="btn btn-sm btn-success" id="share-image" data-image-url="{{ route('payments.receipt.image', $payment) }}" data-filename="bukti-{{ $payment->transaction_number }}.png" data-wa="{{ $waUrl }}"><i class="bi bi-whatsapp"></i> Bagikan Gambar</button>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success"><i class="bi bi-chat-text"></i> Kirim Link</a>
            @endif
            @if(auth()->user()->isAdmin())
            <form method="POST" action="{{ route('payments.reset-receipt', $payment) }}" class="d-inline" onsubmit="return confirm('Hapus gambar bukti transaksi ini? Link publik akan dicabut.')">
                @csrf
                <button class="btn btn-sm btn-outline-danger" title="Reset bukti transaksi ini"><i class="bi bi-trash3"></i></button>
            </form>
            @endif
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($payment->receipt_image_path)
        <a href="{{ route('payments.receipt.image', $payment) }}" target="_blank" rel="noopener">
            <img src="{{ route('payments.receipt.image', $payment) }}" alt="Bukti pembayaran {{ $payment->transaction_number }}" class="img-fluid rounded border">
        </a>
        <p class="small text-muted mt-2 mb-0">
            Dibuat {{ format_datetime($payment->receipt_sent_at) }}
            @if($payment->receiptSentBy) oleh {{ $payment->receiptSentBy->name }} @endif
            @if($publicUrl)
            <br>Link publik: <span class="font-monospace" style="word-break:break-all">{{ $publicUrl }}</span>
            @endif
        </p>
        @else
        <div class="text-center text-muted py-4">
            <i class="bi bi-image fs-1 d-block mb-2 opacity-50"></i>
            <p class="small mb-2">Bukti gambar belum tersedia untuk transaksi ini.</p>
            <button type="button" class="btn btn-success btn-sm" id="generate-receipt"><i class="bi bi-magic"></i> Buat Bukti Sekarang</button>
        </div>
        @endif
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

@push('scripts')
<script>
(function () {
    const sendUrl = @json(route('payments.send-receipt', $payment));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const generateBtn = document.getElementById('generate-receipt');

    document.querySelectorAll('[data-copy-link]').forEach(btn => {
        btn.addEventListener('click', async function () {
            const url = btn.dataset.copyLink;
            if (!url) return;
            try {
                await navigator.clipboard.writeText(url);
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
                setTimeout(() => { btn.innerHTML = original; }, 1800);
            } catch (err) {
                window.prompt('Salin link bukti:', url);
            }
        });
    });

    const shareBtn = document.getElementById('share-image');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function () {
            const imageUrl = shareBtn.dataset.imageUrl;
            const filename = shareBtn.dataset.filename;

            try {
                const blob = await fetch(imageUrl, { credentials: 'same-origin' }).then(r => r.blob());
                const file = new File([blob], filename, { type: 'image/png' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        files: [file],
                        title: 'Bukti Pembayaran',
                        text: 'Bukti pembayaran JRC Media',
                    });
                    return;
                }

                const objectUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = filename;
                link.click();
                URL.revokeObjectURL(objectUrl);

                window.open(shareBtn.dataset.wa, '_blank', 'noopener');
            } catch (err) {
                if (err && err.name === 'AbortError') return;
                window.open(shareBtn.dataset.wa, '_blank', 'noopener');
            }
        });
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', async function () {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Membuat…';

            try {
                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Gagal membuat bukti.');
                window.location.reload();
            } catch (err) {
                alert(err.message);
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="bi bi-magic"></i> Buat Bukti Sekarang';
            }
        });
    }
})();
</script>
@endpush
