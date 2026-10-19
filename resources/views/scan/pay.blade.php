@extends('layouts.app')
@section('title', 'Konfirmasi Pembayaran')

@push('styles')
<style>
.inv-card {
    border: 2px solid #e9ecef; border-radius: .75rem; padding: .75rem;
    margin-bottom: .5rem; cursor: pointer; transition: all .12s;
    background:#fff;
}
.inv-card.selected { border-color: var(--nb-primary); background:#e7f1ff; }
.inv-card input[type=checkbox] { width:1.1rem; height:1.1rem; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="{{ route('scan.customer', $customer) }}" class="btn btn-outline-secondary btn-touch"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-grow-1 min-w-0">
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Konfirmasi Pembayaran</h5>
        <p class="text-muted small mb-0 text-truncate">{{ $customer->name }} · {{ $customer->customer_code }}</p>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('scan.pay.store', $customer) }}" id="pay-form">
@csrf
<input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
<input type="hidden" name="payment_method" value="cash">

<h6 class="mb-2"><i class="bi bi-list-check"></i> Pilih Tagihan Dibayar</h6>
@if($invoices->isEmpty())
    <div class="card mb-3"><div class="card-body text-center text-muted small">Tidak ada tagihan aktif.</div></div>
@else
<div class="mb-3">
@foreach($invoices as $inv)
<label class="inv-card d-flex align-items-center" data-balance="{{ $inv->balance() }}" data-id="{{ $inv->id }}">
    <input type="checkbox" name="invoices[]" value="{{ $inv->id }}" class="form-check-input invoice-check me-2" {{ in_array($inv->id, old('invoices', [])) ? 'checked' : '' }}>
    <div class="flex-grow-1">
        <div class="fw-semibold">{{ format_period($inv->billing_period) }}</div>
        <div class="small text-danger">Sisa: {{ format_rupiah($inv->balance()) }}</div>
    </div>
    <i class="bi bi-check-circle-fill text-primary d-none check-icon"></i>
</label>
@endforeach
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold">Nominal Diterima (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="amount_received" id="amount_received" class="form-control form-control-lg" value="{{ old('amount_received') }}" min="1" inputmode="numeric" required>
        <div class="d-flex gap-2 mt-2 flex-wrap">
            <button type="button" class="nb-amount-btn" id="btn-exact"><i class="bi bi-check2-all"></i> Bayar Penuh</button>
            <button type="button" class="nb-amount-btn" id="btn-50k">+50rb</button>
            <button type="button" class="nb-amount-btn" id="btn-100k">+100rb</button>
            <button type="button" class="nb-amount-btn" id="btn-clear"><i class="bi bi-x"></i> Hapus</button>
        </div>

        <div class="mt-3">
            <label class="form-label small">Catatan</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
        <div class="mt-2">
            <label class="form-label small">Hasil Kunjungan</label>
            <select name="visit_result" class="form-select">
                <option value="paid" @selected(old('visit_result')==='paid')>Berhasil menerima pembayaran</option>
                <option value="not_home" @selected(old('visit_result')==='not_home')>Pelanggan tidak di rumah</option>
                <option value="recall_requested" @selected(old('visit_result')==='recall_requested')>Minta kunjungan ulang</option>
                <option value="cannot_pay" @selected(old('visit_result')==='cannot_pay')>Belum dapat membayar</option>
                <option value="address_not_found" @selected(old('visit_result')==='address_not_found')>Alamat tidak ditemukan</option>
                <option value="other" @selected(old('visit_result')==='other')>Kendala lain</option>
            </select>
        </div>
    </div>
</div>

<div class="card sticky-bottom">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Total Dipilih</span>
            <span class="fw-bold" id="selected-total">Rp0</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Diterima</span>
            <span class="fw-bold text-success" id="received-display">Rp0</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">Status</span>
            <span id="status-badge" class="badge bg-secondary">—</span>
        </div>
        <div id="warning" class="alert alert-danger py-2 small mb-2 d-none"></div>
        <button type="submit" class="btn btn-success btn-lg btn-touch w-100 fw-bold" id="submit-btn"><i class="bi bi-check-lg"></i> Konfirmasi Pembayaran</button>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const checks = document.querySelectorAll('.invoice-check');
const cards = document.querySelectorAll('.inv-card');
const amountInput = document.getElementById('amount_received');
const selectedTotal = document.getElementById('selected-total');
const receivedDisplay = document.getElementById('received-display');
const statusBadge = document.getElementById('status-badge');
const warning = document.getElementById('warning');
const submitBtn = document.getElementById('submit-btn');

function fmt(v){ return 'Rp'+Number(v||0).toLocaleString('id-ID'); }

function selectedBalance() {
    let total = 0;
    checks.forEach(c => { if (c.checked) total += parseInt(c.closest('.inv-card').dataset.balance, 10); });
    return total;
}

function recalc() {
    let total = 0;
    checks.forEach(c => {
        const card = c.closest('.inv-card');
        const icon = card.querySelector('.check-icon');
        card.classList.toggle('selected', c.checked);
        if (icon) icon.classList.toggle('d-none', !c.checked);
        if (c.checked) total += parseInt(card.dataset.balance, 10);
    });
    selectedTotal.textContent = fmt(total);
    const received = parseInt(amountInput.value || 0, 10);
    receivedDisplay.textContent = fmt(received);
    if (received <= 0) {
        statusBadge.textContent='—'; statusBadge.className='badge bg-secondary';
        warning.classList.add('d-none'); submitBtn.disabled = true; return;
    }
    if (received > total) {
        statusBadge.textContent='Kelebihan'; statusBadge.className='badge bg-danger';
        warning.textContent='Nominal melebihi sisa tagihan. Kelebihan bayar belum diizinkan.';
        warning.classList.remove('d-none'); submitBtn.disabled = true;
    } else if (received === total) {
        statusBadge.textContent='Lunas'; statusBadge.className='badge bg-success';
        warning.classList.add('d-none'); submitBtn.disabled = false;
    } else {
        statusBadge.textContent='Sebagian'; statusBadge.className='badge bg-warning text-dark';
        warning.classList.add('d-none'); submitBtn.disabled = false;
    }
}

checks.forEach(c => c.addEventListener('change', recalc));
amountInput.addEventListener('input', recalc);

document.getElementById('btn-exact').addEventListener('click', function() {
    amountInput.value = selectedBalance();
    recalc();
});
document.getElementById('btn-50k').addEventListener('click', function() {
    amountInput.value = (parseInt(amountInput.value || 0, 10)) + 50000;
    recalc();
});
document.getElementById('btn-100k').addEventListener('click', function() {
    amountInput.value = (parseInt(amountInput.value || 0, 10)) + 100000;
    recalc();
});
document.getElementById('btn-clear').addEventListener('click', function() {
    amountInput.value = '';
    recalc();
});

document.getElementById('pay-form').addEventListener('submit', function(e) {
    const anyChecked = Array.from(checks).some(c => c.checked);
    if (!anyChecked) { e.preventDefault(); alert('Pilih minimal satu tagihan.'); return; }
    if (parseInt(amountInput.value || 0, 10) <= 0) { e.preventDefault(); alert('Nominal harus > 0.'); return; }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses…';
});

recalc();
</script>
@endpush