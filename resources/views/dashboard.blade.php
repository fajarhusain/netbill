@extends('layouts.app')
@section('title', 'Dashboard')

@php
$isCollector = ! auth()->user()->isAdmin();
$initials = collect(mb_str_split(auth()->user()->name))->take(2)->join('');

// ===== Fallback data chart (aman jika controller belum kirim) =====
$last7Days = $last7Days ?? [];
$monthlyTrend = $monthlyTrend ?? [];

// Helper: hitung polygon/path sparkline dari array angka
$buildSpark = function (array $values, float $w = 100, float $h = 32, float $pad = 2) {
$values = array_values(array_map('floatval', $values));
$n = count($values);
if ($n < 2) return ['path'=> '', 'area' => '', 'points' => [], 'max' => 0];
    $max = max($values) ?: 1;
    $min = min($values);
    $range = ($max - $min) ?: 1;
    $step = ($w - $pad * 2) / ($n - 1);
    $pts = [];
    foreach ($values as $i => $v) {
    $x = $pad + $i * $step;
    $y = $pad + ($h - $pad * 2) * (1 - (($v - $min) / $range));
    $pts[] = [$x, $y];
    }
    $path = 'M ' . implode(' L ', array_map(fn($p) => "{$p[0]},{$p[1]}", $pts));
    $area = $path . " L {$pts[$n-1][0]},{$h} L {$pts[0][0]},{$h} Z";
    return ['path' => $path, 'area' => $area, 'points' => $pts, 'max' => $max];
    };

    // Helper: bar chart
    $buildBars = function (array $data, float $h = 80) {
    if (empty($data)) return ['bars' => [], 'max' => 0];
    $max = max(array_column($data, 'value')) ?: 1;
    $bars = [];
    foreach ($data as $i => $d) {
    $pct = ($d['value'] / $max) * 100;
    $bars[] = [
    'label' => $d['label'],
    'value' => $d['value'],
    'pct' => max($pct, 2), // minimal 2% agar tetap terlihat
    ];
    }
    return ['bars' => $bars, 'max' => $max];
    };

    $spark7 = $buildSpark(array_column($last7Days, 'value'));
    $bars6 = $buildBars($monthlyTrend);
    @endphp

    @push('styles')
    <style>
    .nb-dash {
        --ink: #0f172a;
        --ink-soft: #475569;
        --ink-mute: #94a3b8;
        --line: #eef2f7;
        --surface: #ffffff;
        --bg-soft: #f8fafc;
        --accent: #4f46e5;
        --accent-soft: #eef2ff;
        --success: #059669;
        --danger: #dc2626;
        --warning: #d97706;
    }

    /* ===== Hero ===== */
    .nb-hero-min {
        position: relative;
        background: var(--ink);
        color: #fff;
        border-radius: 20px;
        padding: 1.75rem;
        overflow: hidden;
    }

    .nb-hero-min::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(120% 80% at 100% 0%, rgba(79, 70, 229, .35) 0%, transparent 55%);
        pointer-events: none;
    }

    .nb-hero-min>* {
        position: relative;
        z-index: 1;
    }

    .nb-avatar-min {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .14);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
    }

    .nb-hero-stat {
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, .1);
    }

    .nb-hero-stat .label {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .55);
        font-weight: 500;
    }

    .nb-hero-stat .value {
        font-size: 1.5rem;
        font-weight: 600;
        letter-spacing: -.02em;
        margin-top: .25rem;
        font-variant-numeric: tabular-nums;
    }

    .btn-hero-min {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        width: 100%;
        padding: .85rem 1rem;
        border-radius: 14px;
        background: #fff;
        color: var(--ink);
        border: none;
        font-weight: 600;
        font-size: .95rem;
        transition: transform .15s ease, opacity .15s ease;
    }

    .btn-hero-min:hover {
        transform: translateY(-1px);
        opacity: .92;
        color: var(--ink);
    }

    /* ===== Header ===== */
    .nb-header-title {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.03em;
        color: var(--ink);
        margin: 0;
    }

    .nb-header-sub {
        font-size: .82rem;
        color: var(--ink-mute);
        margin: .15rem 0 0;
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    /* ===== Stat Cards ===== */
    .nb-stat {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 1.1rem 1.15rem;
        height: 100%;
        transition: border-color .2s ease, transform .2s ease;
        display: flex;
        flex-direction: column;
    }

    .nb-stat:hover {
        border-color: #dbe3ec;
        transform: translateY(-2px);
    }

    .nb-stat .ico {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        background: var(--bg-soft);
        color: var(--ink-soft);
        margin-bottom: .85rem;
    }

    .nb-stat .label {
        font-size: .74rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--ink-mute);
        font-weight: 500;
    }

    .nb-stat .value {
        font-size: 1.4rem;
        font-weight: 600;
        letter-spacing: -.025em;
        color: var(--ink);
        margin-top: .3rem;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }

    .nb-stat .value.success {
        color: var(--success);
    }

    .nb-stat .value.danger {
        color: var(--danger);
    }

    .nb-stat .value.warning {
        color: var(--warning);
    }

    .nb-stat.nb-compact {
        padding: .9rem 1rem;
    }

    .nb-stat.nb-compact .ico {
        display: none;
    }

    .nb-stat.nb-compact .value {
        font-size: 1.15rem;
    }

    .nb-stat.nb-compact .label {
        font-size: .7rem;
    }

    .nb-stat.nb-accent {
        background: var(--accent-soft);
        border-color: transparent;
    }

    .nb-stat.nb-accent .ico {
        background: #fff;
        color: var(--accent);
    }

    .nb-stat.nb-accent .value {
        color: var(--accent);
    }

    /* ===== Sparkline mini (di dalam stat card) ===== */
    .nb-spark {
        margin-top: auto;
        padding-top: .75rem;
        height: 40px;
        position: relative;
    }

    .nb-spark svg {
        width: 100%;
        height: 100%;
        display: block;
        overflow: visible;
    }

    .nb-spark .line {
        fill: none;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .nb-spark .dot {
        r: 2.2;
    }

    .nb-spark .axis {
        display: flex;
        justify-content: space-between;
        font-size: .62rem;
        color: var(--ink-mute);
        margin-top: .35rem;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    /* ===== Chart Card (bar bulanan) ===== */
    .nb-chart-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 1.25rem 1.25rem 1rem;
    }

    .nb-chart-card .head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.15rem;
    }

    .nb-chart-card .head h6 {
        margin: 0;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--ink-soft);
    }

    .nb-chart-card .head .total {
        font-size: .82rem;
        color: var(--ink-mute);
        font-variant-numeric: tabular-nums;
    }

    .nb-chart-card .head .total strong {
        color: var(--ink);
        font-weight: 600;
    }

    .nb-bars {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: .5rem;
        height: 130px;
    }

    .nb-bar-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        position: relative;
    }

    .nb-bar-track {
        width: 100%;
        max-width: 42px;
        background: var(--bg-soft);
        border-radius: 8px;
        height: 100%;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
        position: relative;
    }

    .nb-bar-fill {
        width: 100%;
        background: var(--accent);
        border-radius: 8px;
        transition: height .6s cubic-bezier(.2, .8, .2, 1), background .15s ease;
        min-height: 4px;
    }

    .nb-bar-item:hover .nb-bar-fill {
        background: #4338ca;
    }

    .nb-bar-item .tip {
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        background: var(--ink);
        color: #fff;
        font-size: .68rem;
        padding: .25rem .5rem;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        font-variant-numeric: tabular-nums;
    }

    .nb-bar-item:hover .tip {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .nb-bar-label {
        font-size: .68rem;
        color: var(--ink-mute);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-top: .55rem;
        font-weight: 500;
    }

    /* ===== Section heading ===== */
    .nb-sec {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .85rem;
    }

    .nb-sec h6 {
        margin: 0;
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--ink-soft);
    }

    .nb-sec a {
        font-size: .78rem;
        font-weight: 600;
        color: var(--ink-mute);
        text-decoration: none;
        transition: color .15s ease;
    }

    .nb-sec a:hover {
        color: var(--accent);
    }

    /* ===== List & Table ===== */
    .nb-list-card {
        transition: background .15s ease;
        border-radius: 12px;
    }

    .nb-list-card:hover {
        background: var(--bg-soft);
    }

    .nb-table {
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
        background: var(--surface);
    }

    .nb-table table {
        margin: 0;
    }

    .nb-table thead th {
        background: var(--bg-soft);
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--ink-mute);
        font-weight: 600;
        border: 0;
        padding: .8rem 1rem;
    }

    .nb-table tbody td {
        padding: .85rem 1rem;
        border-color: var(--line);
        font-size: .88rem;
    }

    .nb-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .nb-table tbody tr {
        transition: background .15s ease;
    }

    .nb-table tbody tr:hover {
        background: var(--bg-soft);
    }

    /* ===== Empty ===== */
    .nb-empty {
        border: 1px dashed #e2e8f0;
        border-radius: 16px;
        padding: 3rem 1rem;
        text-align: center;
        color: var(--ink-mute);
        font-size: .85rem;
        background: var(--surface);
    }

    .nb-empty i {
        font-size: 2rem;
        opacity: .35;
        display: block;
        margin-bottom: .5rem;
    }

    /* ===== Animation ===== */
    @keyframes nbFade {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    .nb-fade {
        animation: nbFade .35s ease both;
    }

    .nb-d1 {
        animation-delay: .04s;
    }

    .nb-d2 {
        animation-delay: .08s;
    }

    .nb-d3 {
        animation-delay: .12s;
    }

    .nb-d4 {
        animation-delay: .16s;
    }
    </style>
    @endpush

    @section('content')
    <div class="nb-dash">

        @if($isCollector)
        {{-- ===== HERO ===== --}}
        <div class="nb-hero-min mb-3">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="nb-avatar-min">{{ $initials }}</div>
                <div class="flex-grow-1">
                    <div
                        style="font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.55);font-weight:500;">
                        Selamat datang</div>
                    <div class="fw-semibold" style="font-size:1.1rem;letter-spacing:-.01em;">{{ auth()->user()->name }}
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="nb-hero-stat pt-3">
                        <div class="label">Hari Ini</div>
                        <div class="value">{{ format_rupiah($paidToday) }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="nb-hero-stat pt-3">
                        <div class="label">Belum Disetor</div>
                        <div class="value">{{ format_rupiah($pendingSettlement) }}</div>
                    </div>
                </div>
            </div>

            <a href="{{ route('scan.index') }}" class="btn-hero-min">
                <i class="bi bi-qr-code-scan"></i> Mulai Scan & Tagih
            </a>
        </div>

        {{-- ===== STAT COLLECTOR dengan SPARKLINE ===== --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="nb-stat nb-fade nb-d1">
                    <div class="ico"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="label">Bulan Ini</div>
                    <div class="value success">{{ format_rupiah($paidThisMonth) }}</div>

                    {{-- Sparkline 7 hari --}}
                    @if(!empty($last7Days))
                    <div class="nb-spark">
                        <svg viewBox="0 0 100 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="sparkGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#059669" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#059669" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $spark7['area'] }}" fill="url(#sparkGrad)" />
                            <path d="{{ $spark7['path'] }}" class="line" stroke="#059669" />
                            @if(!empty($spark7['points']))
                            @php $last = end($spark7['points']); @endphp
                            <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" class="dot" fill="#059669" />
                            @endif
                        </svg>
                        <div class="axis">
                            <span>{{ $last7Days[0]['label'] ?? '' }}</span>
                            <span>7 hari</span>
                            <span>{{ end($last7Days)['label'] ?? '' }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="nb-stat nb-fade nb-d2">
                    <div class="ico"><i class="bi bi-geo-alt"></i></div>
                    <div class="label">Kunjungan Hari Ini</div>
                    <div class="value">{{ $visitsToday }}</div>

                    {{-- Sparkline kunjungan (dummy fallback) --}}
                    @php
                    $visitSpark = $visitsSpark ?? [2,4,3,6,5,7,4];
                    $vs = $buildSpark($visitSpark);
                    @endphp
                    <div class="nb-spark">
                        <svg viewBox="0 0 100 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="sparkGrad2" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#4f46e5" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $vs['area'] }}" fill="url(#sparkGrad2)" />
                            <path d="{{ $vs['path'] }}" class="line" stroke="#4f46e5" />
                        </svg>
                        <div class="axis">
                            <span>7 hari</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        {{-- ===== HEADER ADMIN ===== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
            <div>
                <h1 class="nb-header-title">Dashboard</h1>
                <p class="nb-header-sub"><i class="bi bi-clock"></i> {{ format_datetime($now) }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('scan.index') }}" class="btn btn-primary btn-touch"><i class="bi bi-qr-code-scan"></i>
                    Scan</a>
                <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-touch"><i
                        class="bi bi-file-text"></i> Buat Tagihan</a>
            </div>
        </div>

        {{-- ===== 4 STAT UTAMA (dengan sparkline) ===== --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
                <div class="nb-stat nb-fade nb-d1">
                    <div class="ico"><i class="bi bi-people"></i></div>
                    <div class="label">Total Pelanggan</div>
                    <div class="value">{{ number_format($totalCustomers) }}</div>
                    <div class="small text-muted mt-1" style="font-size:.72rem;">{{ $activeCustomers }} aktif</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="nb-stat nb-fade nb-d2">
                    <div class="ico"><i class="bi bi-cash-coin"></i></div>
                    <div class="label">Penerimaan Hari Ini</div>
                    <div class="value success">{{ format_rupiah($paidToday) }}</div>
                    @if(!empty($last7Days))
                    <div class="nb-spark">
                        <svg viewBox="0 0 100 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="sparkGradA" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#059669" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#059669" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $spark7['area'] }}" fill="url(#sparkGradA)" />
                            <path d="{{ $spark7['path'] }}" class="line" stroke="#059669" />
                        </svg>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="nb-stat nb-fade nb-d3">
                    <div class="ico"><i class="bi bi-graph-up"></i></div>
                    <div class="label">Penerimaan Bulan Ini</div>
                    <div class="value">{{ format_rupiah($paidThisMonth) }}</div>
                    @if(!empty($monthlyTrend))
                    <div class="nb-spark">
                        <svg viewBox="0 0 100 32" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="sparkGradB" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.18" />
                                    <stop offset="100%" stop-color="#4f46e5" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            @php
                            $mt = $buildSpark(array_column($monthlyTrend, 'value'));
                            @endphp
                            <path d="{{ $mt['area'] }}" fill="url(#sparkGradB)" />
                            <path d="{{ $mt['path'] }}" class="line" stroke="#4f46e5" />
                        </svg>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="nb-stat nb-accent nb-fade nb-d4">
                    <div class="ico"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="label">Total Tunggakan</div>
                    <div class="value">{{ format_rupiah($arrearsTotal) }}</div>
                </div>
            </div>
        </div>

        {{-- ===== BAR CHART BULANAN ===== --}}
        @if(!empty($monthlyTrend))
        <div class="nb-chart-card mb-3 nb-fade">
            <div class="head">
                <h6>Penerimaan 6 Bulan Terakhir</h6>
                <div class="total">
                    Total: <strong>{{ format_rupiah(array_sum(array_column($monthlyTrend, 'value'))) }}</strong>
                </div>
            </div>
            <div class="nb-bars">
                @foreach($bars6['bars'] as $bar)
                <div class="nb-bar-item">
                    <div class="nb-bar-track">
                        <div class="nb-bar-fill" style="height: {{ $bar['pct'] }}%;"></div>
                    </div>
                    <div class="tip">{{ format_rupiah($bar['value']) }}</div>
                    <div class="nb-bar-label">{{ $bar['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ===== 4 STAT SEKUNDER ===== --}}
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-3">
                <div class="nb-stat nb-compact nb-fade">
                    <div class="label">Tagihan {{ format_period($currentPeriod) }}</div>
                    <div class="value">{{ format_rupiah($totalInvoicedCurrentPeriod) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="nb-stat nb-compact nb-fade nb-d1">
                    <div class="label">Belum Bayar</div>
                    <div class="value warning">{{ number_format($unpaidCustomerCount) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="nb-stat nb-compact nb-fade nb-d2">
                    <div class="label">Kunjungan Hari Ini</div>
                    <div class="value">{{ $visitsToday }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="nb-stat nb-compact nb-fade nb-d3">
                    <div class="label">Kas Belum Disetor</div>
                    <div class="value warning">{{ format_rupiah($pendingSettlement) }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== TRANSAKSI TERBARU ===== --}}
        <div class="nb-sec">
            <h6>Transaksi Terbaru</h6>
            <a href="{{ route('payments.index') }}">Lihat semua →</a>
        </div>

        @if($recentPayments->isEmpty())
        <div class="nb-empty mb-3">
            <i class="bi bi-receipt-cutoff"></i>
            Belum ada transaksi.
        </div>
        @else
        <div class="d-md-none">
            @foreach($recentPayments as $p)
            <a href="{{ route('payments.show', $p) }}" class="nb-list-card">
                <div class="nb-avatar" style="background: var(--nb-primary);">
                    {{ mb_strtoupper(mb_substr($p->customer->name, 0, 1)) }}</div>
                <div class="nb-body">
                    <p class="nb-title">{{ $p->customer->name }}</p>
                    <p class="nb-sub">{{ $p->transaction_number }} · {{ format_datetime($p->paid_at) }}</p>
                </div>
                <div class="nb-end">
                    <div class="fw-bold text-success">{{ format_rupiah($p->amount_received) }}</div>
                    <span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
                </div>
            </a>
            @endforeach
        </div>
        <div class="nb-table d-none d-md-block mb-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Pelanggan</th>
                            <th class="text-end">Nominal</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $p)
                        <tr>
                            <td><a href="{{ route('payments.show', $p) }}" class="text-decoration-none fw-semibold"
                                    style="color:var(--ink);">{{ $p->transaction_number }}</a></td>
                            <td>{{ $p->customer->name }}<div class="small text-muted" style="font-size:.75rem;">
                                    {{ $p->customer->customer_code }}</div>
                            </td>
                            <td class="text-end fw-bold text-success">{{ format_rupiah($p->amount_received) }}</td>
                            <td class="small text-muted">{{ format_datetime($p->paid_at) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ===== TUNGGAKAN (ADMIN) ===== --}}
        @if($isAdmin && $arrears->isNotEmpty())
        <div class="nb-sec mt-4">
            <h6 style="color:var(--danger);">Tunggakan Terbaru</h6>
            <a href="{{ route('invoices.index') }}">Lihat semua →</a>
        </div>
        <div class="d-md-none">
            @foreach($arrears as $inv)
            <a href="{{ route('customers.show', $inv->customer) }}" class="nb-list-card">
                <div class="nb-avatar" style="background:#ef4444;"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="nb-body">
                    <p class="nb-title">{{ $inv->customer->name }}</p>
                    <p class="nb-sub">{{ $inv->customer->customer_code }} · {{ format_period($inv->billing_period) }}
                    </p>
                </div>
                <div class="nb-end">
                    <div class="fw-bold text-danger">{{ format_rupiah($inv->balance()) }}</div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="nb-table d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Periode</th>
                            <th class="text-end">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($arrears as $inv)
                        <tr>
                            <td><a href="{{ route('customers.show', $inv->customer) }}"
                                    class="text-decoration-none fw-semibold"
                                    style="color:var(--ink);">{{ $inv->customer->name }}</a> <span
                                    class="text-muted small">{{ $inv->customer->customer_code }}</span></td>
                            <td>{{ format_period($inv->billing_period) }}</td>
                            <td class="text-end fw-bold text-danger">{{ format_rupiah($inv->balance()) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
    @endsection