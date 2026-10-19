<form method="POST" action="{{ isset($visit) ? route('visits.update', $visit) : route('visits.store') }}">@csrf @isset($visit)@method('PUT')@endisset
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
        <select name="customer_id" class="form-select" required>
            <option value="">— Pilih —</option>
            @foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id', isset($visit) ? $visit->customer_id : null)==$c->id)>{{ $c->customer_code }} — {{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Waktu <span class="text-danger">*</span></label>
        <input type="datetime-local" name="visited_at" class="form-control" value="{{ old('visited_at', isset($visit) ? $visit->visited_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Hasil <span class="text-danger">*</span></label>
        <select name="result" class="form-select" required>@foreach($results as $r)<option value="{{ $r->value }}" @selected(old('result', isset($visit) ? $visit->result->value : null)===$r->value)>{{ $r->label() }}</option>@endforeach</select>
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $visit->notes ?? '') }}</textarea>
    </div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button> <a href="{{ route('visits.index') }}" class="btn btn-outline-secondary">Batal</a></div>
</div>
</form>
