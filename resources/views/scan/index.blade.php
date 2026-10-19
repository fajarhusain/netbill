@extends('layouts.app')
@section('title', 'Scan Pelanggan')

@push('styles')
<style>
    #qr-reader { width:100%; max-width:400px; margin:0 auto; border-radius:1rem; overflow:hidden; }
    #qr-reader video { border-radius:1rem; object-fit:cover; }
    #qr-reader img[alt="Info icon"]{ display:none; }
    #qr-reader__dashboard button { border-radius:.5rem; }
    #qr-reader__scan_region { background:#000; min-height:220px; border-radius:1rem; }
    #qr-reader__status_span { background: rgba(0,0,0,.5); color:#fff !important; border-radius:.4rem; padding:2px 8px; }
    .scan-frame {
        position: relative; border-radius:1rem; overflow:hidden;
    }
    .scan-frame::after {
        content:''; position:absolute; top:50%; left:50%;
        width:65%; height:65%; transform:translate(-50%,-50%);
        border: 3px solid rgba(255,255,255,.8); border-radius:1rem;
        box-shadow: 0 0 0 100vmax rgba(0,0,0,.35);
        pointer-events:none;
    }
</style>
@endpush

@section('content')
<div class="text-center mb-3">
    <h4 class="mb-1"><i class="bi bi-qr-code-scan"></i> Scan Pelanggan</h4>
    <p class="text-muted small mb-0">Arahkan kamera ke QR Code pada kartu pelanggan.</p>
</div>

@if(!request()->secure() && !in_array(request()->getHost(), ['localhost','127.0.0.1']))
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i> Kamera memerlukan <strong>HTTPS</strong>. Gunakan input manual di bawah.
</div>
@endif

<div class="card mb-3">
    <div class="card-body text-center">
        <div class="scan-frame mb-2">
            <div id="qr-reader"></div>
        </div>
        <div id="scan-status" class="small text-muted mb-2">Tekan tombol di bawah untuk memulai kamera.</div>
        <div class="d-flex gap-2 justify-content-center">
            <button type="button" id="btn-start" class="btn btn-primary btn-touch px-4"><i class="bi bi-camera-video"></i> Mulai Kamera</button>
            <button type="button" id="btn-stop" class="btn btn-outline-danger btn-touch d-none"><i class="bi bi-stop-circle"></i> Stop</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-keyboard text-muted me-2"></i>
            <label class="form-label mb-0 fw-semibold">Input Kode Manual</label>
        </div>
        <form method="POST" action="{{ route('scan.lookup') }}">
            @csrf
            <div class="input-group input-group-lg">
                <input type="text" name="code" class="form-control" placeholder="Mis. RTW-0001" value="{{ old('code') }}" required>
                <button class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>
        <div class="form-text small">Gunakan jika kamera tidak tersedia atau QR rusak.</div>
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
        status.className = 'small mb-2 ' + (cls || 'text-muted');
        status.innerHTML = msg;
    }

    function isSecure() {
        return location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    }

    async function startCamera() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('Pustaka pemindai gagal dimuat. Gunakan input manual.', 'text-danger');
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
                { fps: 10, qrbox: { width: 200, height: 200 } },
                (decodedText) => {
                    stopCamera(true);
                    setStatus('<span class="text-success"><i class="bi bi-check-circle"></i> Kode terbaca: <strong>' + decodedText + '</strong></span><br>Mengalihkan…', '');
                    submitCode(decodedText);
                },
                () => {}
            );
            running = true;
            btnStart.classList.add('d-none');
            btnStop.classList.remove('d-none');
            setStatus('<i class="bi bi-camera-video"></i> Memindai… Arahkan QR ke dalam kotak.', 'text-primary');
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