<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Dusun;
use App\Models\Invoice;
use App\Models\WaBlastLog;
use App\Services\BillingMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class WaBlastController extends Controller
{
    public function __construct(private BillingMessageService $messages) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->get('q')),
            'dusun' => (string) $request->get('dusun', ''),
            'only_arrears' => $request->has('only_arrears') ? $request->boolean('only_arrears') : true,
            'not_contacted' => $request->boolean('not_contacted'),
        ];

        $template = (string) $request->get('template', '');
        if ($template === '') {
            $template = BillingMessageService::DEFAULT_TEMPLATE;
        }

        $query = Customer::query()
            ->with([
                'package',
                'invoices' => fn ($q) => $q->unsettled()->orderBy('billing_period'),
                'waBlastLogs' => fn ($q) => $q->latest('sent_at')->limit(1),
            ])
            ->where('status', 'active');

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($filters['dusun'] !== '') {
            $query->where('dusun', $filters['dusun']);
        }

        if ($filters['only_arrears']) {
            $query->whereHas('invoices', fn ($q) => $q->unsettled());
        }

        if ($filters['not_contacted']) {
            $query->whereDoesntHave('waBlastLogs', function ($q) {
                $q->where('sent_at', '>=', Carbon::now()->subDays(7));
            });
        }

        $customers = $query->orderBy('name')->get();

        $rows = $customers->map(function (Customer $customer) use ($template, $request) {
            $invoices = $customer->invoices;
            $total = (int) $invoices->sum(fn (Invoice $i) => $i->balance());
            $lastLog = $customer->waBlastLogs->first();
            $message = $this->messages->render($customer, $template, $request->user());

            return [
                'id' => $customer->id,
                'code' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'dusun' => $customer->dusun,
                'package' => $customer->package?->name,
                'alamat' => $customer->address,
                'wa_number' => $this->messages->normalizeNumber($customer->phone),
                'total' => $total,
                'total_label' => format_rupiah($total),
                'periods' => $invoices->map(fn (Invoice $i) => format_period($i->billing_period))->values()->all(),
                'periods_label' => $invoices->map(fn (Invoice $i) => format_period($i->billing_period))->implode(', '),
                'due_date' => $invoices->first()?->due_date ? format_date($invoices->first()->due_date) : '-',
                'last_sent_at' => $lastLog?->sent_at?->toDateTimeString(),
                'last_sent_label' => $lastLog ? format_datetime($lastLog->sent_at) : null,
                'wa_url' => $this->messages->whatsappUrl($customer->phone, $message),
                'message' => $message,
            ];
        })->values();

        $withPhone = $rows->filter(fn (array $row) => $row['wa_url'] !== null)->count();

        return view('blast.index', [
            'rows' => $rows,
            'filters' => $filters,
            'template' => $template,
            'placeholders' => $this->messages->placeholders(),
            'dusunList' => Dusun::orderBy('name')->pluck('name'),
            'summary' => [
                'customers' => $rows->count(),
                'with_phone' => $withPhone,
                'total_arrears' => (int) $rows->sum('total'),
            ],
            'recentLogs' => WaBlastLog::with(['customer', 'user'])->latest('sent_at')->limit(5)->get(),
        ]);
    }

    public function log(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'items.*.message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $now = now();
        $records = [];

        $customers = Customer::with(['invoices' => fn ($q) => $q->unsettled()])
            ->whereIn('id', collect($validated['items'])->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        foreach ($validated['items'] as $item) {
            $customer = $customers->get($item['customer_id']);
            if (! $customer) {
                continue;
            }

            $total = (int) $customer->invoices->sum(fn (Invoice $i) => $i->balance());

            $records[] = [
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'phone' => $customer->phone,
                'message' => $item['message'],
                'total_arrears' => $total,
                'channel' => 'whatsapp',
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($records === []) {
            return response()->json(['message' => 'Tidak ada data yang bisa dicatat.'], 422);
        }

        WaBlastLog::insert($records);

        AuditLog::record(
            userId: $user->id,
            action: 'wa_blast.sent',
            entityType: 'wa_blast',
            entityId: null,
            old: null,
            new: [
                'count' => count($records),
                'customer_ids' => array_column($records, 'customer_id'),
            ],
        );

        return response()->json([
            'logged' => count($records),
            'sent_at' => format_datetime($now),
        ]);
    }

    public function history(Request $request)
    {
        $query = WaBlastLog::with(['customer', 'user'])->latest('sent_at');

        if ($request->user()->isCollector()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($dusun = $request->get('dusun')) {
            $query->whereHas('customer', fn ($q) => $q->where('dusun', $dusun));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('blast.history', [
            'logs' => $logs,
            'dusunList' => Dusun::orderBy('name')->pluck('name'),
        ]);
    }
}
