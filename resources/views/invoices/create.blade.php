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
    <form method="POST" action="{{ route('invoices.store') }}">@csrf
        <input type="hidden" name="mode" value="single">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                <select name="customer_id" class="form-select" required>
                    <option value="">— Pilih —</option>
                    @foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id')==$c->id)>{{ $c->customer_code }} — {{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Periode <span class="text-danger">*</span></label>
                <input type="month" name="period" value="{{ old('period', $currentPeriod) }}" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Buat Tagihan</button>
    </form>
</div>
</div>
</div></div>
@endsection
