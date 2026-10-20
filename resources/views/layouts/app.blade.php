<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="JRC Media">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', 'JRC Media')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --nb-primary: #6366f1;
            --nb-primary-dark: #4f46e5;
            --nb-sidebar-bg: #1e1b4b;
            --nb-sidebar-w: 250px;
            --nb-bg: #f8fafc;
            --nb-card-radius: 1rem;
            --nb-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --nb-shadow-lg: 0 10px 30px rgba(0,0,0,.08);
            --nb-bottom-nav-h: 64px;
            --nb-text: #1e293b;
            --nb-muted: #64748b;
        }
        * { -webkit-tap-highlight-color: transparent; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--nb-bg);
            color: var(--nb-text);
            padding-bottom: env(safe-area-inset-bottom);
        }

        /* ---------- ADMIN SIDEBAR (desktop) ---------- */
        .nb-sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--nb-sidebar-w);
            background: var(--nb-sidebar-bg);
            color: #cbd5e1;
            z-index: 1040;
            display: flex; flex-direction: column;
            transition: transform .3s ease;
            overflow-y: auto;
        }
        .nb-sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: .5rem;
            color: #fff; font-weight: 800; font-size: 1.15rem;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .nb-sidebar .sidebar-brand i { font-size: 1.3rem; }
        .nb-sidebar .sidebar-brand img { height: 1.8rem; width: 1.8rem; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
        .nb-sidebar .sidebar-nav { flex: 1; padding: .75rem .5rem; }
        .nb-sidebar .sidebar-section { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; padding: .75rem 1rem .25rem; font-weight: 700; }
        .nb-sidebar .sidebar-link {
            display: flex; align-items: center; gap: .65rem;
            padding: .6rem 1rem; border-radius: .6rem;
            color: #cbd5e1; text-decoration: none; font-size: .9rem; font-weight: 500;
            transition: all .15s; margin-bottom: 2px;
        }
        .nb-sidebar .sidebar-link i { font-size: 1.1rem; width: 22px; text-align:center; }
        .nb-sidebar .sidebar-link:hover { background: rgba(255,255,255,.06); color: #fff; }
        .nb-sidebar .sidebar-link.active { background: var(--nb-primary); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,.35); }
        .nb-sidebar .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,.08); }
        .nb-sidebar .dev-credit { display: flex; align-items: center; justify-content: center; gap: .35rem; padding: .6rem 0 0; color: #64748b; font-size: .7rem; }
        .nb-sidebar .dev-credit img { height: 1rem; width: auto; }
        .nb-sidebar .sidebar-user { display: flex; align-items: center; gap: .65rem; }
        .nb-sidebar .sidebar-user .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--nb-primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem;
        }
        .nb-sidebar .sidebar-user .name { color: #fff; font-size: .85rem; font-weight: 600; }
        .nb-sidebar .sidebar-user .role { color: #64748b; font-size: .75rem; }

        /* ---------- BURGER (mobile admin) ---------- */
        .nb-burger {
            background: var(--nb-primary); color: #fff; border: 0;
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; cursor: pointer;
        }
        .nb-burger:hover { background: var(--nb-primary-dark); }
        .nb-sidebar-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.5);
            z-index: 1035; opacity: 0; visibility: hidden;
            transition: opacity .3s, visibility .3s;
        }
        .nb-sidebar-overlay.show { opacity: 1; visibility: visible; }
        @media (max-width: 991.98px) {
            .nb-sidebar { transform: translateX(-100%); }
            .nb-sidebar.show { transform: translateX(0); }
        }

        /* ---------- TOP BAR ---------- */
        .nb-topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: .65rem 1rem;
            display: flex; align-items: center; gap: .5rem;
            position: sticky; top: 0; z-index: 1030;
            padding-top: calc(.65rem + env(safe-area-inset-top));
        }
        .nb-topbar .brand-mobile { font-weight: 800; font-size: 1.1rem; color: var(--nb-text); text-decoration: none; display: flex; align-items: center; gap: .35rem; }
        .nb-topbar .brand-mobile i { color: var(--nb-primary); }
        .nb-topbar .brand-mobile img { height: 1.5rem; width: 1.5rem; object-fit: cover; border-radius: 5px; }
        .nb-topbar .spacer { flex: 1; }
        .nb-topbar .icon-btn {
            color: var(--nb-muted); background: #f1f5f9; border: 0;
            width: 40px; height: 40px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; cursor: pointer; text-decoration: none;
        }
        .nb-topbar .icon-btn:hover { background: #e2e8f0; color: var(--nb-text); }

        /* ---------- MAIN CONTENT ---------- */
        main.nb-main { padding: 1rem; padding-bottom: calc(var(--nb-bottom-nav-h) + 1rem + env(safe-area-inset-bottom)); }
        @media (min-width: 992px) {
            body.admin-layout main.nb-main { margin-left: var(--nb-sidebar-w); padding: 1.5rem 2rem; padding-bottom: 2rem; }
            body.admin-layout .nb-topbar { margin-left: var(--nb-sidebar-w); }
        }

        /* ---------- CARDS ---------- */
        .card { border: 1px solid #e2e8f0; border-radius: var(--nb-card-radius); box-shadow: var(--nb-shadow); }
        .stat-card { border-radius: var(--nb-card-radius); }
        .stat-card .stat-value { font-size: 1.5rem; font-weight: 800; }

        /* ---------- MOBILE LIST CARDS ---------- */
        .nb-list-card {
            display: flex; align-items: center; gap: .75rem;
            padding: .85rem; background: #fff; border-radius: .85rem;
            border: 1px solid #e2e8f0; margin-bottom: .5rem;
            text-decoration: none; color: inherit; transition: all .12s;
        }
        .nb-list-card:hover { border-color: var(--nb-primary); box-shadow: var(--nb-shadow); }
        .nb-list-card:active { transform: scale(.99); }
        .nb-list-card .nb-avatar {
            width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: .95rem;
        }
        .nb-list-card .nb-body { flex: 1; min-width: 0; }
        .nb-list-card .nb-title { font-weight: 700; font-size: .9rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nb-list-card .nb-sub { font-size: .78rem; color: var(--nb-muted); margin: 0; }
        .nb-list-card .nb-end { text-align: right; flex-shrink: 0; }

        /* ---------- BOTTOM NAV (collector) ---------- */
        .nb-bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #fff; border-top: 1px solid #e2e8f0;
            display: flex; justify-content: space-around; align-items: center;
            height: calc(var(--nb-bottom-nav-h) + env(safe-area-inset-bottom));
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 1030;
        }
        .nb-bottom-nav a {
            flex: 1; text-align: center; text-decoration: none; color: #94a3b8;
            padding: .35rem 0; font-size: .68rem; display: flex; flex-direction: column;
            align-items: center; gap: .12rem; font-weight: 600;
        }
        .nb-bottom-nav a i { font-size: 1.25rem; }
        .nb-bottom-nav a.active { color: var(--nb-primary); }
        .nb-bottom-nav .nb-scan-fab {
            width: 56px; height: 56px; border-radius: 50%;
            background: var(--nb-primary); color: #fff; border: 4px solid #fff;
            box-shadow: var(--nb-shadow-lg);
            margin-top: -24px; font-size: 1.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .nb-bottom-nav .nb-scan-fab i { font-size: 1.4rem; }
        @media (min-width: 992px) { .nb-bottom-nav { display: none; } }

        /* ---------- MISC ---------- */
        .table th { white-space: nowrap; font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; color: var(--nb-muted); }
        .nb-hero {
            background: linear-gradient(135deg, var(--nb-primary), #8b5cf6);
            color: #fff; border-radius: var(--nb-card-radius);
            padding: 1.5rem; box-shadow: var(--nb-shadow-lg); border: 0;
        }
        .btn-touch { min-height: 44px; }
        .nb-amount-btn {
            border: 1px solid var(--nb-primary); color: var(--nb-primary);
            background: #fff; border-radius: 50rem; padding: .35rem .9rem;
            font-size: .82rem; font-weight: 600; cursor: pointer;
        }
        .nb-amount-btn:hover { background: var(--nb-primary); color: #fff; }
        .nb-page-header { margin-bottom: 1.25rem; }
        .nb-page-header h4, .nb-page-header h5 { font-weight: 800; margin: 0; }
        .badge { font-weight: 600; }
        @media (max-width: 575.98px) {
            .stat-card .stat-value { font-size: 1.15rem; }
            .btn-lg { font-size: 1rem; }
        }
        .print-only { display: none; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: #fff; }
            .card { box-shadow: none; border: 1px solid #dee2e6; }
            .nb-sidebar, .nb-bottom-nav, .nb-topbar { display: none !important; }
            main.nb-main { padding: 0; margin: 0 !important; }
        }
    </style>
    @stack('styles')
</head>
@php
    $isAdmin = auth()->user()?->isAdmin();
    $isCollector = ! $isAdmin;
    $routeName = request()->route()?->getName() ?? '';
@endphp
<body @class(["admin-layout" => $isAdmin])>

@if($isAdmin)
<nav class="nb-sidebar no-print" id="adminSidebar">
    <a class="sidebar-brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('jrc-media-logo.png') }}" alt="JRC Media"> <span>JRC Media</span>
    </a>
    <div class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ str_starts_with($routeName, 'dashboard') ? 'active' : '' }}"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="{{ route('customers.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'customers.') ? 'active' : '' }}"><i class="bi bi-people"></i> Pelanggan</a>
        <a href="{{ route('collections.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'collections.') ? 'active' : '' }}"><i class="bi bi-diagram-3"></i> Penagihan per Dusun</a>

        <div class="sidebar-section">Keuangan</div>
        <a href="{{ route('invoices.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'invoices.') ? 'active' : '' }}"><i class="bi bi-file-text"></i> Tagihan</a>
        <a href="{{ route('payments.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'payments.') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Transaksi</a>
        <a href="{{ route('visits.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'visits.') ? 'active' : '' }}"><i class="bi bi-geo-alt"></i> Kunjungan</a>
        <a href="{{ route('settlements.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'settlements.') ? 'active' : '' }}"><i class="bi bi-bank"></i> Setoran</a>

        <div class="sidebar-section">Pengaturan</div>
        <a href="{{ route('packages.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'packages.') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Paket</a>
        <a href="{{ route('dusun.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'dusun.') ? 'active' : '' }}"><i class="bi bi-geo"></i> Dusun</a>
        <a href="{{ route('reports.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'reports.') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Laporan</a>
        <a href="{{ route('users.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'users.') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Pengguna</a>
        <a href="{{ route('audit.index') }}" class="sidebar-link {{ str_starts_with($routeName, 'audit.') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Audit Log</a>
    </div>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">{{ mb_substr(auth()->user()?->name, 0, 1) }}</div>
            <div class="flex-grow-1 min-w-0">
                <div class="name text-truncate">{{ auth()->user()?->name }}</div>
                <div class="role">{{ auth()->user()?->role?->label() }}</div>
            </div>
            <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="icon-btn" style="background:rgba(255,255,255,.1);color:#fff;" title="Keluar"><i class="bi bi-box-arrow-right"></i></a>
        </div>
        <div class="dev-credit">Dikembangkan oleh <img src="{{ asset('patitech-logo.png') }}" alt="PATITECH.ID"> PATITECH.ID</div>
    </div>
</nav>
<div class="nb-sidebar-overlay no-print" id="sidebarOverlay" onclick="closeSidebar()"></div>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
@endif

<nav class="nb-topbar no-print">
    @if($isAdmin)
    <button class="nb-burger d-lg-none" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <a class="brand-mobile d-lg-none" href="{{ route('dashboard') }}"><img src="{{ asset('jrc-media-logo.png') }}" alt="JRC Media"> JRC Media</a>
    @else
    <a class="brand-mobile" href="{{ route('dashboard') }}"><img src="{{ asset('jrc-media-logo.png') }}" alt="JRC Media"> JRC Media</a>
    @endif

    <div class="spacer"></div>

    @if($isAdmin)
    <a href="{{ route('scan.index') }}" class="icon-btn d-none d-lg-inline-flex" title="Scan"><i class="bi bi-qr-code-scan"></i></a>
    @else
    <a href="{{ route('scan.index') }}" class="icon-btn" title="Scan"><i class="bi bi-qr-code-scan"></i></a>
    @endif

    <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted">{{ auth()->user()?->name }} · {{ auth()->user()?->role?->label() }}</span></li>
            <li><hr class="dropdown-divider"></li>
            @if($isAdmin)
            <li class="d-lg-none"><a class="dropdown-item" href="{{ route('audit.index') }}"><i class="bi bi-clock-history"></i> Audit Log</a></li>
            @endif
            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
        </ul>
    </div>
    @if(! $isAdmin)
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    @endif
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
<script>
function openSidebar() {
    document.getElementById('adminSidebar').classList.add('show');
    document.getElementById('sidebarOverlay').classList.add('show');
}
function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('show');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
@stack('scripts')
</body>
</html>