<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
@verbatim
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
<x:ExcelWorksheet><x:Name>Ringkasan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>
</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
@endverbatim
</head>
<body>
<table border="1">
    <tr><td colspan="4" style="font-weight:bold;font-size:14px;">Laporan Keuangan Penagihan</td></tr>
    <tr><td colspan="4">Periode {{ format_date($dateFrom) }} s/d {{ format_date($dateTo) }}@if($dusun) · Dusun {{ $dusun }}@endif</td></tr>
    <tr><td colspan="4"></td></tr>
    <tr style="font-weight:bold;background:#e8f0fe;">
        <td>Total Penerimaan</td><td>Total Transaksi</td><td>Total Tunggakan</td><td>Pelanggan Aktif</td>
    </tr>
    <tr>
        <td>{{ format_rupiah($totalReceived, false) }}</td>
        <td>{{ $totalTransactions }}</td>
        <td>{{ format_rupiah($totalArrears, false) }}</td>
        <td>{{ $customerStats['active'] }}/{{ $customerStats['total'] }}</td>
    </tr>
</table>

<br>
<table border="1">
    <tr style="font-weight:bold;background:#f8f9fa;"><td colspan="3">Per Petugas</td></tr>
    <tr style="font-weight:bold;"><td>Petugas</td><td>Transaksi</td><td>Total</td></tr>
    @forelse($byCollector as $r)
    <tr><td>{{ $r->collector->name }}</td><td>{{ $r->tx_count }}</td><td>{{ format_rupiah($r->total, false) }}</td></tr>
    @empty
    <tr><td colspan="3">Tidak ada data.</td></tr>
    @endforelse
</table>

<br>
<table border="1">
    <tr style="font-weight:bold;background:#f8f9fa;"><td colspan="3">Per Metode Pembayaran</td></tr>
    <tr style="font-weight:bold;"><td>Metode</td><td>Transaksi</td><td>Total</td></tr>
    @forelse($byMethod as $r)
    <tr><td>{{ $r->method->label() }}</td><td>{{ $r->tx_count }}</td><td>{{ format_rupiah($r->total, false) }}</td></tr>
    @empty
    <tr><td colspan="3">Tidak ada data.</td></tr>
    @endforelse
</table>

<br>
<table border="1">
    <tr style="font-weight:bold;background:#f8f9fa;"><td colspan="9">Rincian Transaksi</td></tr>
    <tr style="font-weight:bold;"><td>No Transaksi</td><td>Tanggal</td><td>Pelanggan</td><td>Kode</td><td>Dusun</td><td>Petugas</td><td>Metode</td><td>Nominal</td><td>Status</td></tr>
    @forelse($payments as $p)
    <tr>
        <td>{{ $p->transaction_number }}</td>
        <td>{{ format_datetime($p->paid_at) }}</td>
        <td>{{ $p->customer->name }}</td>
        <td>{{ $p->customer->customer_code }}</td>
        <td>{{ $p->customer->dusun ?: '' }}</td>
        <td>{{ $p->collector->name }}</td>
        <td>{{ $p->payment_method->label() }}</td>
        <td>{{ format_rupiah($p->amount_received, false) }}</td>
        <td>{{ $p->status->label() }}</td>
    </tr>
    @empty
    <tr><td colspan="9">Tidak ada transaksi.</td></tr>
    @endforelse
</table>

<br>
<table border="1">
    <tr style="font-weight:bold;background:#fce4e4;"><td colspan="5">Pelanggan Menunggak</td></tr>
    <tr style="font-weight:bold;"><td>Pelanggan</td><td>Kode</td><td>Dusun</td><td>Periode</td><td>Sisa</td></tr>
    @forelse($arrears as $inv)
    <tr>
        <td>{{ $inv->customer->name }}</td>
        <td>{{ $inv->customer->customer_code }}</td>
        <td>{{ $inv->customer->dusun ?: '' }}</td>
        <td>{{ format_period($inv->billing_period) }}</td>
        <td>{{ format_rupiah($inv->balance(), false) }}</td>
    </tr>
    @empty
    <tr><td colspan="5">Tidak ada tunggakan.</td></tr>
    @endforelse
</table>

<br>
<table border="1">
    <tr style="font-weight:bold;background:#e4fce8;"><td colspan="5">Pelanggan Lunas</td></tr>
    <tr style="font-weight:bold;"><td>Pelanggan</td><td>Kode</td><td>Dusun</td><td>Periode</td><td>Dibayar</td></tr>
    @forelse($paidInvoices as $inv)
    <tr>
        <td>{{ $inv->customer->name }}</td>
        <td>{{ $inv->customer->customer_code }}</td>
        <td>{{ $inv->customer->dusun ?: '' }}</td>
        <td>{{ format_period($inv->billing_period) }}</td>
        <td>{{ format_rupiah($inv->paid_amount, false) }}</td>
    </tr>
    @empty
    <tr><td colspan="5">Belum ada pelanggan lunas.</td></tr>
    @endforelse
</table>
</body>
</html>