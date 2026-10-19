<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $query = Payment::query()->with(['customer', 'collector']);

        $user = $request->user();
        if ($user->isCollector()) {
            $query->where('collector_id', $user->id);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    });
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->get('method')) {
            $query->where('payment_method', $method);
        }
        if ($collectorId = $request->get('collector_id')) {
            $query->where('collector_id', $collectorId);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        $payments = $query->latest('paid_at')->paginate(15)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
            'methods' => PaymentMethod::cases(),
            'collectors' => $user->isAdmin() ? User::where('role', 'collector')->orderBy('name')->get() : collect(),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice', 'cancelledBy']);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice']);

        return view('payments.receipt', compact('payment'));
    }

    public function pdf(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice']);

        $pdf = Pdf::loadView('payments.receipt', compact('payment'))
            ->setPaper('a6', 'portrait');

        return $pdf->download('bukti-'.$payment->transaction_number.'.pdf');
    }

    public function cancel(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $this->paymentService->cancel($payment, $request->user(), $validated['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', $payment)->with('success', 'Transaksi dibatalkan. Status tagihan dikembalikan.');
    }
}
