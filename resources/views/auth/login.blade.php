<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }} — Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#0d6efd,#6610f2); min-height:100vh; display:flex; align-items:center; }
        .login-card { max-width:420px; margin:0 auto; border:0; border-radius:1rem; box-shadow:0 10px 40px rgba(0,0,0,.2); }
        .brand-icon { width:64px; height:64px; border-radius:1rem; background:#0d6efd; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.75rem; margin:0 auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="brand-icon mb-2"><i class="bi bi-receipt"></i></div>
                <h4 class="mb-1">NetBill Collector</h4>
                <p class="text-muted small mb-0">Aplikasi Penagihan RT/RW Net</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" autofocus required>
                    </div>
                    @error('username') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Kata Sandi</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    </div>
                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">Ingat saya</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
