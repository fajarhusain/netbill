@extends('layouts.app')
@section('title', 'Buat Tagihan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Buat Tagihan Bulanan</h4>
    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

<div class="card"><div class="card-body">
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bulk" type="button">Massal (semua pelanggan aktif)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#single" type="button">Pelanggan Tertentu</button></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="bulk">
    <form method="POST" action="{{ route('invoices.store') }}">@csrf
        <input type="hidden" name="mode" value="bulk">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Periode <span class="text-danger">*</span></label>
                <input type="month" name="period" value="{{ old('period', $currentPeriod) }}" class="form-control" required>
            </div>
            <div class="col-md-8">
                <div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle"></i> Tagihan hanya dibuat untuk pelanggan aktif yang mulai berlangganan sebelum akhir periode. Tagihan yang sudah ada pada periode tersebut akan dilewati.</div>
            </div>
        </div>
        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Buat Tagihan Massal</button>
    </form>
</div>

<div class="tab-pane fade" id="single">
    <form method="POST" action="{{ route('invoices.store') }}" id="single-invoice-form">@csrf
        <input type="hidden" name="mode" value="single">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                <div class="position-relative" id="customer-picker">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="customer-search" class="form-control" placeholder="Cari kode / nama / telepon / dusun…"
                               autocomplete="off" role="combobox" aria-expanded="false" aria-controls="customer-results" aria-autocomplete="list">
                        <button type="button" class="btn btn-outline-secondary d-none" id="customer-clear" title="Hapus pilihan"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <input type="hidden" name="customer_id" id="customer-id" value="{{ old('customer_id') }}">
                    <div id="customer-results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index:1055; max-height:320px; overflow-y:auto;"></div>
                </div>
                <div class="form-text" id="customer-hint">Ketik untuk mencari pelanggan aktif.</div>
                <div id="customer-selected" class="mt-2"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Periode <span class="text-danger">*</span></label>
                <input type="month" name="period" value="{{ old('period', $currentPeriod) }}" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-primary mt-3" id="single-submit"><i class="bi bi-check-lg"></i> Buat Tagihan</button>
    </form>
</div>
</div>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
    const customers = @json($customerOptions);

    const form = document.getElementById('single-invoice-form');
    if (!form) return;

    const search = document.getElementById('customer-search');
    const results = document.getElementById('customer-results');
    const hidden = document.getElementById('customer-id');
    const clearBtn = document.getElementById('customer-clear');
    const selectedBox = document.getElementById('customer-selected');
    const hint = document.getElementById('customer-hint');
    const submitBtn = document.getElementById('single-submit');

    let activeIndex = -1;
    let filtered = [];

    const rupiah = (v) => 'Rp' + Number(v || 0).toLocaleString('id-ID');
    const label = (c) => c.code + ' — ' + c.name;
    const haystack = (c) => [c.code, c.name, c.phone, c.dusun].filter(Boolean).join(' ').toLowerCase();

    function findCustomer(id) {
        return customers.find(c => String(c.id) === String(id));
    }

    function renderSelected(customer) {
        if (!customer) {
            selectedBox.innerHTML = '';
            clearBtn.classList.add('d-none');
            hint.textContent = 'Ketik untuk mencari pelanggan aktif.';
            return;
        }

        clearBtn.classList.remove('d-none');
        hint.textContent = 'Pelanggan dipilih.';
        selectedBox.innerHTML = `
            <div class="border rounded p-2 bg-light">
                <div class="fw-semibold">${customer.name}</div>
                <div class="small text-muted">${customer.code}${customer.phone ? ' · ' + customer.phone : ''}${customer.dusun ? ' · ' + customer.dusun : ''}</div>
                <div class="small">${customer.package ? customer.package + ' · ' : ''}${rupiah(customer.fee)}/bulan</div>
            </div>`;
    }

    function renderResults(list) {
        filtered = list;
        activeIndex = -1;

        if (!list.length) {
            results.innerHTML = '<div class="list-group-item small text-muted">Pelanggan tidak ditemukan.</div>';
            results.classList.remove('d-none');
            search.setAttribute('aria-expanded', 'true');
            return;
        }

        results.innerHTML = list.map((c, i) => `
            <button type="button" class="list-group-item list-group-item-action py-2" data-index="${i}">
                <div class="d-flex justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate">${c.name}</div>
                        <div class="small text-muted text-truncate">${c.code}${c.phone ? ' · ' + c.phone : ''}${c.dusun ? ' · ' + c.dusun : ''}</div>
                    </div>
                    <div class="small text-nowrap">${rupiah(c.fee)}</div>
                </div>
            </button>`).join('');

        results.classList.remove('d-none');
        search.setAttribute('aria-expanded', 'true');
    }

    function hideResults() {
        results.classList.add('d-none');
        search.setAttribute('aria-expanded', 'false');
    }

    function selectCustomer(customer) {
        hidden.value = customer.id;
        search.value = label(customer);
        renderSelected(customer);
        hideResults();
    }

    function resetCustomer() {
        hidden.value = '';
        search.value = '';
        renderSelected(null);
        hideResults();
        search.focus();
    }

    function filter() {
        const q = search.value.trim().toLowerCase();
        const matches = q === ''
            ? customers.slice(0, 20)
            : customers.filter(c => haystack(c).includes(q)).slice(0, 20);

        renderResults(matches);
    }

    function highlight(index) {
        const items = results.querySelectorAll('[data-index]');
        items.forEach(el => el.classList.remove('active'));
        if (index >= 0 && items[index]) {
            items[index].classList.add('active');
            items[index].scrollIntoView({ block: 'nearest' });
        }
    }

    search.addEventListener('focus', filter);
    search.addEventListener('input', function () {
        hidden.value = '';
        renderSelected(null);
        filter();
    });

    search.addEventListener('keydown', function (e) {
        if (results.classList.contains('d-none')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
            highlight(activeIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlight(activeIndex);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && filtered[activeIndex]) {
                e.preventDefault();
                selectCustomer(filtered[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            hideResults();
        }
    });

    results.addEventListener('click', function (e) {
        const item = e.target.closest('[data-index]');
        if (!item) return;
        const customer = filtered[parseInt(item.dataset.index, 10)];
        if (customer) selectCustomer(customer);
    });

    clearBtn.addEventListener('click', resetCustomer);

    document.addEventListener('click', function (e) {
        if (!document.getElementById('customer-picker').contains(e.target)) hideResults();
    });

    form.addEventListener('submit', function (e) {
        if (!hidden.value) {
            e.preventDefault();
            hint.textContent = 'Pilih pelanggan terlebih dahulu.';
            search.focus();
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses…';
    });

    const initial = findCustomer(hidden.value);
    if (initial) {
        search.value = label(initial);
        renderSelected(initial);
    }
})();
</script>
@endpush
