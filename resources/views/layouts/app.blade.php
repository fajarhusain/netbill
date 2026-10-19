<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="NetBill">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', 'NetBill Collector')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --nb-primary: #0d6efd;
            --nb-primary-dark: #0b5ed7;
            --nb-bg: #f4f6f9;
            --nb-card-radius: 1rem;
            --nb-shadow: 0 2px 8px rgba(0,0,0,.06);
            --nb-shadow-lg: 0 8px 24px rgba(0,0,0,.10);
            --nb-bottom-nav-h: 64px;
        }
        * { -webkit-tap-highlight-color: transparent; }
        body {
            background: var(--nb-bg);
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* ---------- Top app bar ---------- */
        .nb-topbar {
            background: linear-gradient(135deg, var(--nb-primary), #6610f2);
            color: #fff;
            padding: .5rem .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            padding-top: calc(.5rem + env(safe-area-inset-top));
        }
        .nb-topbar .brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .nb-topbar .page-title { font-weight: 600; font-size: 1rem; margin: 0; }
        .nb-topbar .spacer { flex: 1; }
        .nb-topbar .icon-btn {
            color: #fff; background: rgba(255,255,255,.15); border: 0;
            width: 38px; height: 38px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.05rem; cursor: pointer;
        }
        .nb-topbar .icon-btn:hover { background: rgba(255,255,255,.25); }

        /* ---------- Desktop top nav ---------- */
        .nb-desktop-nav { display: none; }
        @media (min-width: 992px) {
            .nb-topbar { border-radius: 0 0 1rem 1rem; margin: 0 .5rem .5rem; }
            .nb-desktop-nav {
                display: flex; align-items: center; gap: .25rem;
                margin-left: 1rem;
            }
            .nb-desktop-nav a {
                color: rgba(255,255,255,.85); text-decoration: none;
                padding: .4rem .75rem; border-radius: .5rem; font-size: .92rem;
                display: inline-flex; align-items: center; gap: .35rem;
            }
            .nb-desktop-nav a:hover, .nb-desktop-nav a.active { background: rgba(255,255,255,.18); color: #fff; }
        }

        /* ---------- Main content ---------- */
        main.nb-main {
            padding: .75rem;
            padding-bottom: calc(var(--nb-bottom-nav-h) + 1rem + env(safe-area-inset-bottom));
        }
        @media (min-width: 992px) {
            main.nb-main { padding: 1.5rem 2rem; padding-bottom: 2rem; max-width: 1400px; margin: 0 auto; }
        }

        /* ---------- Cards ---------- */
        .card { border: 0; border-radius: var(--nb-card-radius); box-shadow: var(--nb-shadow); }
        .stat-card { border-radius: var(--nb-card-radius); }
        .stat-card .stat-value { font-size: 1.5rem; font-weight: 700; }

        /* ---------- Mobile list cards ---------- */
        .nb-list-card {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem; background: #fff; border-radius: .75rem;
            box-shadow: var(--nb-shadow); margin-bottom: .5rem;
            text-decoration: none; color: inherit; transition: transform .08s;
        }
        .nb-list-card:hover { background: #f8f9fa; }
        .nb-list-card:active { transform: scale(.99); }
        .nb-list-card .nb-avatar {
            width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: .95rem;
        }
        .nb-list-card .nb-body { flex: 1; min-width: 0; }
        .nb-list-card .nb-title { font-weight: 600; font-size: .95rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nb-list-card .nb-sub { font-size: .8rem; color: #6c757d; margin: 0; }
        .nb-list-card .nb-end { text-align: right; flex-shrink: 0; }

        /* ---------- Bottom tab nav (mobile) ---------- */
        .nb-bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #fff; border-top: 1px solid #e9ecef;
            display: flex; justify-content: space-around; align-items: center;
            height: calc(var(--nb-bottom-nav-h) + env(safe-area-inset-bottom));
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 1030;
            box-shadow: 0 -2px 12px rgba(0,0,0,.06);
        }
        .nb-bottom-nav a {
            flex: 1; text-align: center; text-decoration: none; color: #8a94a6;
            padding: .4rem 0; font-size: .68rem; display: flex; flex-direction: column;
            align-items: center; gap: .15rem; font-weight: 500;
        }
        .nb-bottom-nav a i { font-size: 1.25rem; }
        .nb-bottom-nav a.active { color: var(--nb-primary); }
        .nb-bottom-nav a.active i { transform: scale(1.1); }
        .nb-bottom-nav .nb-scan-fab {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, var(--nb-primary), #6610f2);
            color: #fff; border: 4px solid #fff;
            box-shadow: var(--nb-shadow-lg);
            margin-top: -24px; font-size: 1.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .nb-bottom-nav .nb-scan-fab i { font-size: 1.4rem; }
        @media (min-width: 992px) { .nb-bottom-nav { display: none; } }

        /* ---------- Misc ---------- */
        .table th { white-space: nowrap; }
        .nb-hero {
            background: linear-gradient(135deg, var(--nb-primary), #6610f2);
            color: #fff; border-radius: var(--nb-card-radius);
            padding: 1.25rem; box-shadow: var(--nb-shadow-lg);
        }
        .nb-chip {
            display: inline-flex; align-items: center; gap: .25rem;
            padding: .15rem .5rem; border-radius: 50rem; font-size: .72rem; font-weight: 600;
        }
        .btn-touch { min-height: 44px; }
        .nb-amount-btn {
            border: 1px solid var(--nb-primary); color: var(--nb-primary);
            background: #fff; border-radius: 50rem; padding: .35rem .9rem;
            font-size: .82rem; font-weight:600; cursor:pointer;
        }
        .nb-amount-btn:hover { background: var(--nb-primary); color:#fff; }
        @media (max-width: 575.98px) {
            .stat-card .stat-value { font-size: 1.15rem; }
            .btn-lg { font-size: 1rem; }
        }
        .print-only { display:none; }
        @media print {
            .no-print { display:none !important; }
            .print-only { display:block !important; }
            body { background:#fff; }
            .card { box-shadow:none; border:1px solid #dee2e6; }
            .nb-topbar, .nb-bottom-nav { display:none !important; }
            main.nb-main { padding: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $isAdmin = auth()->user()?->isAdmin();
    $isCollector = ! $isAdmin;
    $routeName = request()->route()?->getName() ?? '';
@endphp

<nav class="nb-topbar no-print">
    <a class="brand" href="{{ route('dashboard') }}">
        <i class="bi bi-receipt"></i> <span class="d-none d-sm-inline">NetBill</span>
    </a>

    @if($isAdmin)
    <div class="nb-desktop-nav">
        <a href="{{ route('dashboard') }}" class="{{ str_starts_with($routeName, 'dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="{{ route('customers.index') }}" class="{{ str_starts_with($routeName, 'customers.') ? 'active' : '' }}"><i class="bi bi-people"></i> Pelanggan</a>
        <a href="{{ route('collections.index') }}" class="{{ str_starts_with($routeName, 'collections.') ? 'active' : '' }}"><i class="bi bi-diagram-3"></i> Dusun</a>
        <a href="{{ route('packages.index') }}" class="{{ str_starts_with($routeName, 'packages.') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Paket</a>
        <a href="{{ route('invoices.index') }}" class="{{ str_starts_with($routeName, 'invoices.') ? 'active' : '' }}"><i class="bi bi-file-text"></i> Tagihan</a>
        <a href="{{ route('payments.index') }}" class="{{ str_starts_with($routeName, 'payments.') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Transaksi</a>
        <a href="{{ route('visits.index') }}" class="{{ str_starts_with($routeName, 'visits.') ? 'active' : '' }}"><i class="bi bi-geo-alt"></i> Kunjungan</a>
        <a href="{{ route('settlements.index') }}" class="{{ str_starts_with($routeName, 'settlements.') ? 'active' : '' }}"><i class="bi bi-bank"></i> Setoran</a>
        <a href="{{ route('reports.index') }}" class="{{ str_starts_with($routeName, 'reports.') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Laporan</a>
        <a href="{{ route('users.index') }}" class="{{ str_starts_with($routeName, 'users.') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Pengguna</a>
    </div>
    @endif

    <div class="spacer"></div>

    <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted">{{ auth()->user()?->name }} · {{ auth()->user()?->role?->label() }}</span></li>
            <li><hr class="dropdown-divider"></li>
            @if($isAdmin)
            <li><a class="dropdown-item" href="{{ route('audit.index') }}"><i class="bi bi-clock-history"></i> Audit Log</a></li>
            @endif
            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
        </ul>
    </div>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
</nav>

<main class="nb-main">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-info-circle"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show no-print" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

@if($isCollector)
<nav class="nb-bottom-nav no-print">
    <a href="{{ route('dashboard') }}" class="{{ str_starts_with($routeName, 'dashboard') ? 'active' : '' }}">
        <i class="bi bi-house"></i> Beranda
    </a>
    <a href="{{ route('collections.index') }}" class="{{ str_starts_with($routeName, 'collections.') ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i> Dusun
    </a>
    <a href="{{ route('scan.index') }}">
        <div class="nb-scan-fab"><i class="bi bi-qr-code-scan"></i></div>
    </a>
    <a href="{{ route('payments.index') }}" class="{{ str_starts_with($routeName, 'payments.') ? 'active' : '' }}">
        <i class="bi bi-clock-history"></i> Riwayat
    </a>
    <a href="{{ route('visits.index') }}" class="{{ str_starts_with($routeName, 'visits.') ? 'active' : '' }}">
        <i class="bi bi-geo-alt"></i> Kunjungan
    </a>
</nav>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>