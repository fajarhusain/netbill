<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CashSettlement;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class CashSettlementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CashSettlement::query()->with('collector');

        if ($user->isCollector()) {
            $query->where('collector_id', $user->id);
        } elseif ($collectorId = $request->get('collector_id')) {
            $query->where('collector_id', $collectorId);
        }

        $settlements = $query->latest('settlement_date')->paginate(15)->withQueryString();

        $collectors = $user->isAdmin() ? User::where('role', 'collector')->orderBy('name')->get() : collect();

        $pendingCash = Payment::where('status', PaymentStatus::Success)
            ->where('payment_method', PaymentMethod::Cash)
            ->when($user->isCollector(), fn ($q) => $q->where('collector_id', $user->id))
            ->sum('amount_received');
        $settledCash = CashSettlement::whereNotNull('confirmed_at')
            ->when($user->isCollector(), fn ($q) => $q->where('collector_id', $user->id))
            ->sum('received_amount');

        return view('settlements.index', [
            'settlements' => $settlements,
            'collectors' => $collectors,
            'pendingCash' => max(0, $pendingCash - $settledCash),
        ]);
    }

    public function create()
    {
        $user = request()->user();
        $collectors = $user->isAdmin()
            ? User::where('role', 'collector')->where('is_active', true)->orderBy('name')->get()
            : collect([$user]);

        return view('settlements.create', compact('collectors'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'collector_id' => ['required', 'exists:users,id'],
            'settlement_date' => ['required', 'date'],
            'received_amount' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($user->isCollector() && (int) $validated['collector_id'] !== $user->id) {
            abort(403, 'Petugas hanya dapat mencatat setoran untuk dirinya sendiri.');
        }

        $collectorId = (int) $validated['collector_id'];
        $expected = (int) Payment::where('collector_id', $collectorId)
            ->where('status', PaymentStatus::Success)
            ->where('payment_method', PaymentMethod::Cash)
            ->whereDate('paid_at', '<=', $validated['settlement_date'])
            ->sum('amount_received');

        $alreadySettled = (int) CashSettlement::where('collector_id', $collectorId)
            ->whereDate('settlement_date', '<=', $validated['settlement_date'])
            ->sum('received_amount');

        $expectedAmount = max(0, $expected - $alreadySettled);
        $received = (int) $validated['received_amount'];

        $settlement = CashSettlement::create([
            'collector_id' => $collectorId,
            'settlement_date' => $validated['settlement_date'],
            'expected_amount' => $expectedAmount,
            'received_amount' => $received,
            'difference_amount' => $received - $expectedAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('settlements.show', $settlement)->with('success', 'Setoran dicatat.');
    }

    public function show(CashSettlement $settlement)
    {
        $settlement->load(['collector', 'confirmedBy']);

        return view('settlements.show', compact('settlement'));
    }

    public function edit(CashSettlement $settlement)
    {
        if ($settlement->isConfirmed()) {
            return back()->with('error', 'Setoran yang sudah dikonfirmasi tidak dapat diubah.');
        }

        $user = request()->user();
        $collectors = $user->isAdmin()
            ? User::where('role', 'collector')->orderBy('name')->get()
            : collect([$user]);

        return view('settlements.edit', compact('settlement', 'collectors'));
    }

    public function update(Request $request, CashSettlement $settlement)
    {
        if ($settlement->isConfirmed()) {
            return back()->with('error', 'Setoran yang sudah dikonfirmasi tidak dapat diubah.');
        }

        $validated = $request->validate([
            'settlement_date' => ['required', 'date'],
            'received_amount' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $received = (int) $validated['received_amount'];
        $settlement->update([
            'settlement_date' => $validated['settlement_date'],
            'received_amount' => $received,
            'difference_amount' => $received - $settlement->expected_amount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('settlements.show', $settlement)->with('success', 'Setoran diperbarui.');
    }

    public function destroy(CashSettlement $settlement)
    {
        if ($settlement->isConfirmed()) {
            return back()->with('error', 'Setoran yang sudah dikonfirmasi tidak dapat dihapus.');
        }
        $settlement->delete();

        return redirect()->route('settlements.index')->with('success', 'Setoran dihapus.');
    }

    public function confirm(Request $request, CashSettlement $settlement)
    {
        if ($settlement->isConfirmed()) {
            return back()->with('error', 'Setoran sudah dikonfirmasi.');
        }

        $settlement->update([
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Setoran dikonfirmasi oleh admin.');
    }
}
