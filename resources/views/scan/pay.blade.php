@extends('layouts.app')
@section('title', 'Konfirmasi Pembayaran')

@push('styles')
<style>.invoice-row.selected{ background:#e7f1ff; }</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-cash-coin"></i> Konfirmasi Pembayaran</h4>
        <p class="text-muted small mb-0">{{ $customer->name }} &middot; {{ $customer->customer_code }}</p>
    </div>
    <a href="{{ route('scan.customer', $customer) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

@if(session('error'))
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('scan.pay.store', $customer) }}" id="pay-form">
@csrf
<input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
<input type="hidden" name="payment_method" value="cash">

<div class="row g-3">
    <div class="col-md-7">
        <div class="card"><div class="card-header bg-white"><strong>Pilih Tagihan yang Dibayar</strong></div>
        <div class="card-body p-0">
        @if($invoices->isEmpty())
            <div class="p-4 text-center text-muted">Tidak ada tagihan aktif.</div>
        @else
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th></th><th>Periode</th><th>Nominal</th><th>Sisa</th></tr></thead>
            <tbody>
            @foreach($invoices as $inv)
            <tr class="invoice-row" data-balance="{{ $inv->balance() }}" data-id="{{ $inv->id }}">
                <td><input type="checkbox" name="invoices[]" value="{{ $inv->id }}" class="form-check-input invoice-check" {{ in_array($inv->id, old('invoices', [])) ? 'checked' : '' }}></td>
                <td>{{ format_period($inv->billing_period) }}</td>
                <td>{{ format_rupiah($inv->amount) }}</td>
                <td class="text-danger">{{ format_rupiah($inv->balance()) }}</td>
            </tr>
            @endforeach
            </tbody>
            </table>
            </div>
        @endif
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card mb-3"><div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nominal Diterima (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="amount_received" id="amount_received" class="form-control form-control-lg" value="{{ old('amount_received') }}" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Hasil Kunjungan</label>
                <select name="visit_result" class="form-select">
                    <option value="paid" @selected(old('visit_result')==='paid')>Berhasil menerima pembayaran</option>
                    <option value="not_home" @selected(old('visit_result')==='not_home')>Pelanggan tidak di rumah</option>
                    <option value="recall_requested" @selected(old('visit_result')==='recall_requested')>Minta kunjungan ulang</option>
                    <option value="cannot_pay" @selected(old('visit_result')==='cannot_pay')>Belum dapat membayar</option>
                    <option value="address_not_found" @selected(old('visit_result')==='address_not_found')>Alamat tidak ditemukan</option>
                    <option value="other" @selected(old('visit_result')==='other')>Kendala lain</option>
                </select>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-7">Total Sisa Dipilih</dt><dd class="col-5 text-end" id="selected-total">Rp0</dd>
                <dt class="col-7">Nominal Diterima</dt><dd class="col-7 text-end" id="received-display">Rp0</dd>
                <dt class="col-7">Status</dt><dd class="col-5 text-end"><span id="status-badge" class="badge bg-secondary">—</span></dd>
            </dl>
            <div id="warning" class="alert alert-danger py-2 small mt-2 d-none"></div>
            <button type="submit" class="btn btn-success btn-lg w-100 mt-2" id="submit-btn"><i class="bi bi-check-lg"></i> Konfirmasi Pembayaran</button>
        </div></div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const checks = document.querySelectorAll('.invoice-check');
const amountInput = document.getElementById('amount_received');
const selectedTotal = document.getElementById('selected-total');
const receivedDisplay = document.getElementById('received-display');
const statusBadge = document.getElementById('status-badge');
const warning = document.getElementById('warning');
const submitBtn = document.getElementById('submit-btn');
const rows = document.querySelectorAll('.invoice-row');

function fmt(v){ return 'Rp'+Number(v||0).toLocaleString('id-ID'); }

function recalc(){
    let total=0;
    checks.forEach(c=>{
        const row=c.closest('.invoice-row');
        row.classList.toggle('selected', c.checked);
        if(c.checked) total += parseInt(row.dataset.balance,10);
    });
    selectedTotal.textContent = fmt(total);
    const received = parseInt(amountInput.value||0,10);
    receivedDisplay.textContent = fmt(received);
    if(received<=0){
        statusBadge.textContent='—'; statusBadge.className='badge bg-secondary';
        warning.classList.add('d-none'); submitBtn.disabled=false; return;
    }
    if(received > total){
        statusBadge.textContent='Kelebihan'; statusBadge.className='badge bg-danger';
        warning.textContent='Nominal melebihi sisa tagihan. Kelebihan bayar belum diizinkan.';
        warning.classList.remove('d-none'); submitBtn.disabled=true;
    } else if(received === total){
        statusBadge.textContent='Lunas'; statusBadge.className='badge bg-success';
        warning.classList.add('d-none'); submitBtn.disabled=false;
    } else {
        statusBadge.textContent='Sebagian'; statusBadge.className='badge bg-warning text-dark';
        warning.classList.add('d-none'); submitBtn.disabled=false;
    }
}
checks.forEach(c=>c.addEventListener('change', recalc));
amountInput.addEventListener('input', recalc);
recalc();

document.getElementById('pay-form').addEventListener('submit', function(e){
    const anyChecked = Array.from(checks).some(c=>c.checked);
    if(!anyChecked){ e.preventDefault(); alert('Pilih minimal satu tagihan.'); return; }
    if(parseInt(amountInput.value||0,10)<=0){ e.preventDefault(); alert('Nominal harus > 0.'); return; }
    submitBtn.disabled=true; submitBtn.innerHTML='<span class="spinner-border spinner-border-sm"></span> Memproses…';
});
</script>
@endpush
