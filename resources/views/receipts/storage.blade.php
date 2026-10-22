@extends('layouts.app')
@section('title', 'Penyimpanan Bukti')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-hdd-stack"></i> Penyimpanan Bukti Pembayaran</h4>
        <p class="text-muted small mb-0">Kelola gambar bukti pembayaran yang tersimpan di server.</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cash-stack"></i> Transaksi</a>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="text-muted small">File Gambar</div>
            <div class="stat-value">{{ number_format($file_count, 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="text-muted small">Ukuran Total</div>
            <div class="stat-value">{{ $total_label }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="text-muted small">Transaksi Berbukti</div>
            <div class="stat-value">{{ number_format($payments_with_image, 0, ',', '.') }} <span class="fs-6 text-muted">/ {{ number_format($payments_total, 0, ',', '.') }}</span></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100"><div class="card-body py-3">
            <div class="text-muted small">File Hilang</div>
            <div class="stat-value {{ $missing_files > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($missing_files, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

@if($file_count > 0)
<div class="card mb-3"><div class="card-body py-2 small text-muted">
    <i class="bi bi-info-circle"></i>
    File terlama: {{ $oldest ? format_datetime(\Carbon\Carbon::createFromTimestamp($oldest)) : '-' }}
    · terbaru: {{ $newest ? format_datetime(\Carbon\Carbon::createFromTimestamp($newest)) : '-' }}
</div></div>
@endif

@if($missing_files > 0)
<div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div><i class="bi bi-exclamation-triangle"></i> {{ $missing_files }} transaksi menunjuk ke file gambar yang sudah tidak ada.</div>
    <form method="POST" action="{{ route('receipts.storage.regenerate') }}" onsubmit="return confirm('Buat ulang bukti yang hilang?')">
        @csrf
        <input type="hidden" name="limit" value="200">
        <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-repeat"></i> Buat Ulang yang Hilang</button>
    </form>
</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-white"><strong><i class="bi bi-arrow-repeat"></i> Regenerasi Bukti</strong></div>
            <div class="card-body">
                <p class="small text-muted">Membuat ulang gambar bukti untuk transaksi yang gambarnya belum ada atau hilang. Berguna setelah reset penyimpanan.</p>
                <form method="POST" action="{{ route('receipts.storage.regenerate') }}" id="regenerate-form">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-7">
                            <label class="form-label small mb-1">Maksimal transaksi</label>
                            <input type="number" name="limit" class="form-control" value="100" min="1" max="500">
                        </div>
                        <div class="col-5 d-grid">
                            <button class="btn btn-outline-primary" id="regenerate-btn"><i class="bi bi-magic"></i> Regenerasi</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-danger">
            <div class="card-header bg-white text-danger"><strong><i class="bi bi-trash3"></i> Reset Penyimpanan</strong></div>
            <div class="card-body">
                <div class="alert alert-danger py-2 small mb-3">
                    <strong>Perhatian.</strong> Reset akan menghapus file gambar bukti secara permanen dari server. Link bukti publik yang sudah dibagikan ke pelanggan akan berhenti berfungsi.
                </div>
                <form method="POST" action="{{ route('receipts.storage.reset') }}" id="reset-form">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small mb-1">Cakupan reset</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope" id="scope-images" value="images" checked>
                            <label class="form-check-label small" for="scope-images">
                                Hapus file gambar saja — data transaksi & tanggal kirim tetap
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope" id="scope-all" value="all">
                            <label class="form-check-label small" for="scope-all">
                                Hapus file + reset penanda bukti (tanggal kirim & petugas) + cabut link publik
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Catatan (opsional)</label>
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="255" placeholder="mis. bersihkan penyimpanan bulanan">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirm" id="confirm-reset" value="1">
                        <label class="form-check-label small" for="confirm-reset">
                            Saya paham file bukti akan dihapus permanen
                        </label>
                    </div>
                    <button class="btn btn-danger w-100 btn-touch" id="reset-btn" disabled><i class="bi bi-trash3"></i> Reset Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const confirmBox = document.getElementById('confirm-reset');
    const resetBtn = document.getElementById('reset-btn');
    const resetForm = document.getElementById('reset-form');
    const regenerateForm = document.getElementById('regenerate-form');
    const regenerateBtn = document.getElementById('regenerate-btn');

    if (confirmBox) {
        confirmBox.addEventListener('change', function () {
            resetBtn.disabled = !confirmBox.checked;
        });
    }

    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            if (!confirmBox.checked) {
                e.preventDefault();
                return;
            }

            resetBtn.disabled = true;
            resetBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menghapus…';
        });
    }

    if (regenerateForm) {
        regenerateForm.addEventListener('submit', function () {
            regenerateBtn.disabled = true;
            regenerateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses…';
        });
    }
})();
</script>
@endpush
