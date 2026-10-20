<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }} — Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #6366f1;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-blobs { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .8;
        }
        .blob-1 { width: 480px; height: 480px; background: #ec4899; top: -120px; left: -100px; }
        .blob-2 { width: 420px; height: 420px; background: #8b5cf6; bottom: -100px; right: -80px; }
        .blob-3 { width: 360px; height: 360px; background: #0ea5e9; top: 40%; left: 50%; transform: translate(-50%,-50%); opacity:.6; }

        .glass-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            margin: 1rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        }

        .glass-card .logo-circle {
            width: 56px; height: 56px;
            border-radius: 16px;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #fff;
            margin: 0 auto .75rem;
        }
        .glass-card h1 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0 0 .25rem; text-align:center; }
        .glass-card .subtitle { color: rgba(255,255,255,.8); font-size: .85rem; text-align:center; margin: 0 0 1.75rem; }

        .glass-label { color: rgba(255,255,255,.75); font-size: .8rem; font-weight: 600; margin-bottom: .3rem; display:block; }
        .glass-input-wrap {
            position: relative;
            margin-bottom: 1.25rem;
        }
        .glass-input-wrap .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,.6); font-size: 1.05rem; z-index: 2;
        }
        .glass-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: .8rem 1rem .8rem 2.75rem;
            color: #fff;
            font-size: .95rem;
            transition: all .2s;
            outline: none;
        }
        .glass-input::placeholder { color: rgba(255,255,255,.5); }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 3px rgba(255,255,255,.08);
        }
        .glass-input.is-invalid { border-color: #fca5a5; }

        .glass-btn {
            width: 100%;
            background: #fff;
            color: #6366f1;
            border: 0;
            border-radius: 12px;
            padding: .85rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
        }
        .glass-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.15); }
        .glass-btn:active { transform: translateY(0); }

        .glass-check { color: rgba(255,255,255,.8); font-size: .85rem; }
        .glass-check input { margin-right: .35rem; }

        .glass-alert {
            background: rgba(239, 68, 68, .15);
            border: 1px solid rgba(239, 68, 68, .3);
            color: #fecaca;
            border-radius: 12px;
            padding: .6rem 1rem;
            font-size: .85rem;
            margin-bottom: 1.25rem;
        }

        .glass-error-msg { color: #fca5a5; font-size: .8rem; margin-top: .25rem; }

        @media (max-width: 480px) {
            .glass-card { padding: 2rem 1.25rem; border-radius: 20px; }
        }
    </style>
</head>
<body>
<div class="bg-blobs">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <div class="bg-blob blob-3"></div>
</div>

<div class="glass-card">
    <div class="logo-circle"><i class="bi bi-receipt"></i></div>
    <h1>NetBill Collector</h1>
    <p class="subtitle">Aplikasi Penagihan RT/RW Net</p>

    @if(session('error'))
        <div class="glass-alert"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="glass-input-wrap">
            <label class="glass-label">Username</label>
            <i class="bi bi-person input-icon"></i>
            <input type="text" name="username" class="glass-input @error('username') is-invalid @enderror" placeholder="Masukkan username" value="{{ old('username') }}" autofocus required>
            @error('username') <div class="glass-error-msg">{{ $message }}</div> @enderror
        </div>
        <div class="glass-input-wrap">
            <label class="glass-label">Kata Sandi</label>
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="password" class="glass-input @error('password') is-invalid @enderror" placeholder="Masukkan kata sandi" required>
            @error('password') <div class="glass-error-msg">{{ $message }}</div> @enderror
        </div>
        <div class="glass-check mb-3">
            <label>
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Ingat saya
            </label>
        </div>
        <button type="submit" class="glass-btn"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</button>
    </form>
</div>
</body>
</html>