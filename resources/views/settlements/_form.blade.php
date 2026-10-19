<form method="POST" action="{{ isset($settlement) ? route('settlements.update', $settlement) : route('settlements.store') }}">@csrf @isset($settlement)@method('PUT')@endisset
<div class="row g-3">
    @if(auth()->user()->isAdmin())
    <div class="col-md-6">
        <label class="form-label">Petugas <span class="text-danger">*</span></label>
        <select name="collector_id" class="form-select" required {{ isset($settlement) ? 'disabled' : '' }}>
            @foreach($collectors as $c)<option value="{{ $c->id }}" @selected(old('collector_id', isset($settlement) ? $settlement->collector_id : null)==$c->id)>{{ $c->name }}</option>@endforeach
        </select>
        @if(isset($settlement))<input type="hidden" name="collector_id" value="{{ $settlement->collector_id }}">@endif
    </div>
    @else
    <input type="hidden" name="collector_id" value="{{ auth()->id() }}">
    @endif
    <div class="col-md-3">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="settlement_date" class="form-control" value="{{ old('settlement_date', isset($settlement) ? $settlement->settlement_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Uang Diserahkan (Rp) <span class="text-danger">*</span></label>
        <input type="number" name="received_amount" class="form-control" value="{{ old('received_amount', isset($settlement) ? $settlement->received_amount : '') }}" min="0" required>
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $settlement->notes ?? '') }}</textarea>
    </div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button> <a href="{{ route('settlements.index') }}" class="btn btn-outline-secondary">Batal</a></div>
</div>
</form>
