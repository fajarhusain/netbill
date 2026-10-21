@extends('layouts.app')
@section('title', $customer->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="mb-0">{{ $customer->name }}</h4>
        <p class="text-muted small mb-0">{{ $customer->customer_code }} &middot; <span class="badge {{ $customer->status === 'active' ? 'bg-success-subtle text-success-emphasis' : ($customer->status === 'suspended' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary-emphasis') }}">{{ $customer->status->label() }}</span></p>
    </div>
    <div class="btn-group">
        <a href="{{ route('customers.card', $customer) }}" class="btn btn-outline-primary"><i class="bi bi-qr-code"></i> Kartu</a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('scan.customer', $customer) }}" class="btn btn-success"><i class="bi bi-cash"></i> Tagih/Bayar</a>
        <form action="{{ route('customers.destroy', $customer) }}" method="POST" id="deleteForm" class="d-inline">@csrf @method('DELETE')</form>
        <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteCustomer()"><i class="bi bi-trash"></i> Hapus</button>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Informasi Pelanggan</strong></div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="{{ route('customers.qrcode', $customer) }}" alt="QR" class="img-fluid" style="max-width:200px;">
                    <div class="small text-muted mt-1">Token: {{ Illuminate\Support\Str::limit($customer->barcode_token, 12, '…') }}</div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-5">Kode</dt><dd class="col-7">{{ $customer->customer_code }}</dd>
                    <dt class="col-5">WhatsApp</dt><dd class="col-7">{{ $customer->phone ?: '-' }}</dd>
                    <dt class="col-5">Paket</dt><dd class="col-7">{{ $customer->package?->name ?? '-' }}</dd>
                    <dt class="col-5">Tarif</dt><dd class="col-7">{{ format_rupiah($customer->monthly_fee) }}</dd>
                    <dt class="col-5">Mulai</dt><dd class="col-7">{{ format_date($customer->started_at) }}</dd>
                    <dt class="col-5">Jatuh Tempo</dt><dd class="col-7">Tanggal {{ $customer->due_day }}</dd>
                    <dt class="col-5">Alamat</dt><dd class="col-7">{{ $customer->address ?: '-' }}</dd>
                    <dt class="col-5">Dusun</dt><dd class="col-7">{{ $customer->dusun ?: '-' }}</dd>
                    @if($customer->notes)<dt class="col-5">Catatan</dt><dd class="col-7">{{ $customer->notes }}</dd>@endif
                </dl>
                <form action="{{ route('customers.regenerate', $customer) }}" method="POST" onsubmit="return confirm('Buat ulang token barcode? Kartu lama tidak berlaku.')">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-2"><i class="bi bi-arrow-repeat"></i> Buat Ulang Barcode</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-white"><strong>Tagihan</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Periode</th><th>Nominal</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($customer->invoices as $inv)
                        <tr>
                            <td>{{ format_period($inv->billing_period) }}</td>
                            <td>{{ format_rupiah($inv->amount) }}</td>
                            <td>{{ format_rupiah($inv->paid_amount) }}</td>
                            <td class="text-danger">{{ format_rupiah($inv->balance()) }}</td>
                            <td><span class="badge {{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada tagihan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><strong>Riwayat Pembayaran</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>No. Transaksi</th><th>Tanggal</th><th>Nominal</th><th>Metode</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($customer->payments as $p)
                        <tr>
                            <td><a href="{{ route('payments.show', $p) }}">{{ $p->transaction_number }}</a></td>
                            <td class="small">{{ format_datetime($p->paid_at) }}</td>
                            <td>{{ format_rupiah($p->amount_received) }}</td>
                            <td>{{ $p->payment_method->label() }}</td>
                            <td><span class="badge {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada pembayaran.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDeleteCustomer() {
    Swal.fire({
        icon: 'warning',
        title: 'Hapus Pelanggan?',
        html: 'Pelanggan <strong>{{ addslashes($customer->name) }}</strong> akan dihapus permanen.<br>Pelanggan dengan transaksi pembayaran tidak dapat dihapus.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
@endpush
