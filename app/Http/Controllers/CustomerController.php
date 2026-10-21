<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Exports\CustomerTemplateExport;
use App\Imports\CustomerImport;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Dusun;
use App\Models\Package;
use App\Services\CodeGeneratorService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct(
        private CodeGeneratorService $codes,
        private QrCodeService $qr,
    ) {}

    public function index(Request $request)
    {
        $query = Customer::query()->with('package');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('barcode_token', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($packageId = $request->get('package_id')) {
            $query->where('package_id', $packageId);
        }
        if ($dusun = $request->get('dusun')) {
            $query->where('dusun', $dusun);
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'packages' => Package::orderBy('name')->get(),
            'statuses' => CustomerStatus::cases(),
            'dusunList' => Dusun::orderBy('name')->pluck('name'),
            'filters' => $request->only(['q', 'status', 'package_id', 'dusun']),
        ]);
    }

    public function byDusun(Request $request)
    {
        $dusunList = Dusun::orderBy('name')->pluck('name');

        $selectedDusun = $request->get('dusun');

        $grouped = Customer::query()
            ->with('package')
            ->when($selectedDusun, fn ($q) => $q->where('dusun', $selectedDusun))
            ->when($search = $request->get('q'), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('dusun')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($c) => $c->dusun ?: '(Tanpa Dusun)');

        $summary = $dusunList->mapWithKeys(function ($dusun) {
            $count = Customer::where('dusun', $dusun)->count();
            $active = Customer::where('dusun', $dusun)->where('status', 'active')->count();

            return [$dusun => ['total' => $count, 'active' => $active]];
        });

        return view('customers.by-dusun', [
            'grouped' => $grouped,
            'dusunList' => $dusunList,
            'summary' => $summary,
            'filters' => $request->only(['q', 'dusun']),
        ]);
    }

    public function create()
    {
        $nextCode = $this->previewNextCode();

        return view('customers.create', [
            'packages' => Package::where('is_active', true)->orderBy('name')->get(),
            'statuses' => CustomerStatus::cases(),
            'nextCode' => $nextCode,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);
        $validated['customer_code'] = $validated['customer_code'] ?? $this->codes->generateCustomerCode();
        $validated['barcode_token'] = $this->codes->generateBarcodeToken();

        if (empty($validated['monthly_fee']) && ! empty($validated['package_id'])) {
            $package = Package::find($validated['package_id']);
            $validated['monthly_fee'] = $package?->monthly_price ?? 0;
        }

        $customer = Customer::create($validated);
        AuditLog::record(auth()->id(), 'customer.created', 'customer', $customer->id, null, $validated);

        return redirect()->route('customers.show', $customer)->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['package', 'invoices' => fn ($q) => $q->latest('billing_period'), 'payments' => fn ($q) => $q->latest('paid_at')->limit(10)]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer,
            'packages' => Package::orderBy('name')->get(),
            'statuses' => CustomerStatus::cases(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validateCustomer($request, $customer);
        $old = $customer->toArray();
        $customer->update($validated);
        AuditLog::record(auth()->id(), 'customer.updated', 'customer', $customer->id, $old, $validated);

        return redirect()->route('customers.show', $customer)->with('success', 'Data pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->payments()->exists()) {
            return back()->with('error', 'Pelanggan memiliki transaksi pembayaran dan tidak dapat dihapus. Nonaktifkan saja.');
        }

        $customer->delete();
        AuditLog::record(auth()->id(), 'customer.deleted', 'customer', $customer->id);

        return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
    }

    public function regenerateBarcode(Customer $customer)
    {
        $old = ['barcode_token' => $customer->barcode_token];
        $customer->update(['barcode_token' => $this->codes->generateBarcodeToken()]);
        AuditLog::record(auth()->id(), 'customer.regenerate_barcode', 'customer', $customer->id, $old, ['barcode_token' => $customer->barcode_token]);

        return redirect()->route('customers.show', $customer)->with('success', 'Token barcode baru dibuat. Cetak ulang kartu pelanggan.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $import = new CustomerImport(app(CodeGeneratorService::class));
            Excel::import($import, $request->file('file'));

            $created = $import->created;
            $skipped = $import->skipped;

            if ($created === 0 && $skipped > 0) {
                return back()->with('warning', "Tidak ada pelanggan baru ditambahkan. {$skipped} baris dilewati (sudah ada / tidak valid).");
            }

            return back()->with('success', "Import selesai: {$created} pelanggan ditambahkan, {$skipped} dilewati.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = collect($failures)->map(function ($failure) {
                return "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            })->take(10)->implode("\n");

            return back()->with('error', "Validasi gagal:\n".$errors);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengimpor file: '.$e->getMessage());
        }
    }

    public function importTemplate()
    {
        return Excel::download(new CustomerTemplateExport, 'template-import-pelanggan.xlsx');
    }

    public function card(Customer $customer, QrCodeService $qr)
    {
        return view('customers.card', [
            'customer' => $customer,
            'qrSvg' => $qr->generateForCustomer($customer, 220),
        ]);
    }

    public function qrcode(Customer $customer, QrCodeService $qr)
    {
        return response($qr->generateForCustomer($customer, 300))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'inline; filename="qrcode-'.$customer->customer_code.'.svg"');
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        $uniqueCode = Rule::unique('customers', 'customer_code');
        if ($customer) {
            $uniqueCode->ignore($customer->id);
        }

        return $request->validate([
            'customer_code' => ['nullable', 'string', 'max:20', $uniqueCode],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'dusun' => ['nullable', 'string', 'max:100', Rule::exists('dusun', 'name')],
            'package_id' => ['nullable', 'exists:packages,id'],
            'monthly_fee' => ['required', 'integer', 'min:0'],
            'started_at' => ['nullable', 'date'],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function previewNextCode(): string
    {
        $last = Customer::where('customer_code', 'like', 'RTW-%')->latest('id')->first();
        $next = $last ? ((int) Str::after($last->customer_code, 'RTW-')) + 1 : 1;

        return 'RTW-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
