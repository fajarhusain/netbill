@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Kode Pelanggan</label>
        <input type="text" name="customer_code" class="form-control" value="{{ old('customer_code', $customer->customer_code ?? $nextCode ?? '') }}" placeholder="Otomatis jika dikosongkan">
        <div class="form-text small">Contoh: RTW-0001. Kosongkan untuk dibuat otomatis.</div>
        @error('customer_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Nomor WhatsApp</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone ?? '') }}">
        @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Paket Internet</label>
        <select name="package_id" class="form-select" id="package_id">
            <option value="">— Pilih —</option>
            @foreach($packages as $p)
                <option value="{{ $p->id }}" data-price="{{ $p->monthly_price }}" @selected((int)old('package_id', $customer->package_id ?? 0) === $p->id)>{{ $p->name }} ({{ format_rupiah($p->monthly_price) }})</option>
            @endforeach
        </select>
        @error('package_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Tarif Bulanan (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="monthly_fee" id="monthly_fee" class="form-control" value="{{ old('monthly_fee', $customer->monthly_fee ?? '') }}" min="0" required>
        <div class="form-text small">Diisi sesuai tarif berlaku. Perubahan paket tidak mengubah tagihan lama.</div>
        @error('monthly_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" name="started_at" class="form-control" value="{{ old('started_at', isset($customer) ? $customer->started_at?->format('Y-m-d') : '') }}">
        @error('started_at') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Jatuh Tempo (tgl) <span class="text-danger">*</span></label>
        <input type="number" name="due_day" class="form-control" value="{{ old('due_day', $customer->due_day ?? 10) }}" min="1" max="28" required>
        @error('due_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select">
            @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $customer->status->value ?? 'active') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Alamat</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $customer->notes ?? '') }}</textarea>
        @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>
<script>
document.getElementById('package_id')?.addEventListener('change', function() {
    const price = this.options[this.selectedIndex].dataset.price;
    const fee = document.getElementById('monthly_fee');
    if (price && fee && !fee.dataset.userEdited) fee.value = price;
});
document.getElementById('monthly_fee')?.addEventListener('input', function() { this.dataset.userEdited = '1'; });
</script>
