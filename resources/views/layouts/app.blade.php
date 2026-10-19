<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NetBill Collector')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .navbar-brand { font-weight:700; }
        .card { border:0; box-shadow:0 1px 3px rgba(0,0,0,.06); }
        .stat-card { border-radius:.75rem; }
        .stat-card .stat-value { font-size:1.5rem; font-weight:700; }
        .table th { white-space:nowrap; }
        @media (max-width: 575.98px) {
            .stat-card .stat-value { font-size:1.15rem; }
            .btn-lg { font-size:1rem; }
        }
        .print-only { display:none; }
        @media print {
            .no-print { display:none !important; }
            .print-only { display:block !important; }
            body { background:#fff; }
            .card { box-shadow:none; border:1px solid #dee2e6; }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-receipt"></i> NetBill
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                @if(auth()->user()?->isAdmin())
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people"></i> Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('packages.*') ? 'active' : '' }}" href="{{ route('packages.index') }}"><i class="bi bi-boxes"></i> Paket</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><i class="bi bi-file-text"></i> Tagihan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-cash-stack"></i> Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}" href="{{ route('visits.index') }}"><i class="bi bi-geo-alt"></i> Kunjungan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('settlements.*') ? 'active' : '' }}" href="{{ route('settlements.index') }}"><i class="bi bi-bank"></i> Setoran</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') || request()->routeIs('users.*') || request()->routeIs('audit.*') ? 'active' : '' }}" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><i class="bi bi-bar-chart"></i> Lainnya</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart"></i> Laporan</a></li>
                            <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="bi bi-person-gear"></i> Pengguna</a></li>
                            <li><a class="dropdown-item" href="{{ route('audit.index') }}"><i class="bi bi-clock-history"></i> Audit Log</a></li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('scan.*') ? 'active' : '' }}" href="{{ route('scan.index') }}"><i class="bi bi-qr-code-scan"></i> Scan Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('payments.index') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-clock-history"></i> Riwayat Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('visits.index') ? 'active' : '' }}" href="{{ route('visits.index') }}"><i class="bi bi-geo-alt"></i> Kunjungan</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('settlements.index') ? 'active' : '' }}" href="{{ route('settlements.index') }}"><i class="bi bi-wallet2"></i> Setoran Saya</a></li>
                @endif
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                        <i class="bi bi-person-circle"></i> {{ auth()->user()?->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()?->role?->label() }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                    </ul>
                </li>
            </ul>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
</nav>

<main class="container-fluid py-3 py-md-4">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
