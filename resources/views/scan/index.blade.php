@extends('layouts.app')
@section('title', 'Scan Pelanggan')

@push('styles')
<style>
    #qr-reader { width:100%; max-width:400px; margin:0 auto; border-radius:.5rem; overflow:hidden; }
    #qr-reader video { border-radius:.5rem; }
    #qr-reader__dashboard section { padding:8px; }
    #qr-reader img[alt="Info icon"]{ display:none; }
    #qr-reader__dashboard button { border-radius:.375rem; }
    #qr-reader__scan_region { background:#000; min-height:180px; }
</style>
@endpush

@section('content')
<div class="text-center mb-3">
    <h4 class="mb-1"><i class="bi bi-qr-code-scan"></i> Scan Pelanggan</h4>
    <p class="text-muted small mb-0">Arahkan kamera ke QR Code pada kartu pelanggan.</p>
</div>

@if(!request()->secure() && !in_array(request()->getHost(), ['localhost','127.0.0.1']))
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i> Kamera memerlukan koneksi <strong>HTTPS</strong>. Saat ini diakses via HTTP. Gunakan input manual di bawah atau akses melalui HTTPS.
</div>
@endif

<div class="row justify-content-center">
    <div class="col-12 col-md-6">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div id="qr-reader" class="mb-2"></div>
                <div id="scan-status" class="small text-muted">Belum aktif.</div>
                <div class="mt-2 d-flex gap-2 justify-content-center">
                    <button type="button" id="btn-start" class="btn btn-primary"><i class="bi bi-camera-video"></i> Mulai Kamera</button>
                    <button type="button" id="btn-stop" class="btn btn-outline-secondary d-none"><i class="bi bi-stop-circle"></i> Stop</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <label class="form-label">Input Manual</label>
                <form method="POST" action="{{ route('scan.lookup') }}">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="code" class="form-control form-control-lg" placeholder="Masukkan kode, mis. RTW-0001" value="{{ old('code') }}" required>
                        <button class="btn btn-primary btn-lg"><i class="bi bi-search"></i> Cari</button>
                    </div>
                </form>
                <div class="form-text small">Gunakan jika kamera tidak tersedia atau QR rusak.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const status = document.getElementById('scan-status');
    const btnStart = document.getElementById('btn-start');
    const btnStop = document.getElementById('btn-stop');
    let reader = null;
    let running = false;

    function setStatus(msg, cls) {
        status.className = 'small ' + (cls || 'text-muted');
        status.innerHTML = msg;
    }

    function isSecure() {
        return location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    }

    async function startCamera() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('Pustaka pemindai gagal dimuat (cek koneksi internet). Gunakan input manual.', 'text-danger');
            return;
        }
        if (!isSecure()) {
            setStatus('Kamera memerlukan HTTPS. Gunakan input manual.', 'text-danger');
            return;
        }
        if (running) return;

        try {
            reader = new Html5Qrcode('qr-reader');
            await reader.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                (decodedText) => {
                    stopCamera(true);
                    setStatus('<span class="text-success"><i class="bi bi-check-circle"></i> Kode terbaca: <strong>' + decodedText + '</strong></span> Mengalihkan…', '');
                    submitCode(decodedText);
                },
                () => {}
            );
            running = true;
            btnStart.classList.add('d-none');
            btnStop.classList.remove('d-none');
            setStatus('<span class="text-muted">Pindai QR Code pelanggan…</span>', '');
        } catch (err) {
            let msg = 'Kamera tidak dapat diaktifkan.';
            if (err && (err.name === 'NotAllowedError' || /permission/i.test(String(err)))) {
                msg = 'Izin kamera ditolak. Aktifkan izin kamera di browser, lalu coba lagi.';
            } else if (err && (err.name === 'NotFoundError' || /not found/i.test(String(err)))) {
                msg = 'Tidak ada kamera ditemukan pada perangkat ini.';
            }
            setStatus(msg + ' Gunakan input manual.', 'text-danger');
        }
    }

    async function stopCamera(silent) {
        if (reader && running) {
            try { await reader.stop(); } catch (e) {}
            try { reader.clear(); } catch (e) {}
            running = false;
        }
        btnStart.classList.remove('d-none');
        btnStop.classList.add('d-none');
        if (!silent) setStatus('Pemindai dihentikan.', 'text-muted');
    }

    function submitCode(code) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('scan.lookup') }}';
        const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='_token'; csrf.value='{{ csrf_token() }}';
        const input = document.createElement('input'); input.type='hidden'; input.name='code'; input.value=code;
        form.appendChild(csrf); form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    btnStart.addEventListener('click', startCamera);
    btnStop.addEventListener('click', () => stopCamera(false));
    window.addEventListener('beforeunload', () => { if (reader && running) { try { reader.stop(); } catch(e){} } });
})();
</script>
@endpush
