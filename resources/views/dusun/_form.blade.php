<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama Dusun <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $dusun->name ?? '') }}" required autofocus>
        <div class="form-text small">Nama dusun akan dipakai sebagai pilihan di form pelanggan.</div>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $dusun->notes ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="{{ route('dusun.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</div>