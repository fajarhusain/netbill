<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama Paket <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $package->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Kecepatan</label>
        <input type="text" name="speed" class="form-control" value="{{ old('speed', $package->speed ?? '') }}" placeholder="mis. 10 Mbps">
    </div>
    <div class="col-md-6">
        <label class="form-label">Tarif Bulanan (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="monthly_price" class="form-control @error('monthly_price') is-invalid @enderror" value="{{ old('monthly_price', $package->monthly_price ?? '') }}" min="0" required>
        <div class="form-text small">Perubahan tarif tidak mengubah nominal tagihan lama.</div>
        @error('monthly_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', isset($package) ? $package->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Aktif</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $package->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>
