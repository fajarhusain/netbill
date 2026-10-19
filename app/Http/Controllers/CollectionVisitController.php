<?php

namespace App\Http\Controllers;

use App\Enums\VisitResult;
use App\Models\CollectionVisit;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;

class CollectionVisitController extends Controller
{
    public function index(Request $request)
    {
        $query = CollectionVisit::query()->with(['customer', 'collector']);

        if (! $request->user()->isAdmin()) {
            $query->where('collector_id', $request->user()->id);
        } elseif ($collectorId = $request->get('collector_id')) {
            $query->where('collector_id', $collectorId);
        }
        if ($result = $request->get('result')) {
            $query->where('result', $result);
        }
        if ($search = $request->get('q')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        $visits = $query->latest('visited_at')->paginate(15)->withQueryString();

        return view('visits.index', [
            'visits' => $visits,
            'results' => VisitResult::cases(),
            'collectors' => $request->user()->isAdmin() ? User::where('role', 'collector')->orderBy('name')->get() : collect(),
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'customer_code', 'name']);

        return view('visits.create', [
            'customers' => $customers,
            'results' => VisitResult::cases(),
            'customerId' => request('customer_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'visited_at' => ['required', 'date'],
            'result' => ['required', 'in:paid,not_home,recall_requested,cannot_pay,address_not_found,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $visit = CollectionVisit::create(array_merge($validated, [
            'collector_id' => $request->user()->id,
            'visited_at' => $validated['visited_at'],
        ]));

        return redirect()->route('visits.show', $visit)->with('success', 'Kunjungan dicatat.');
    }

    public function show(CollectionVisit $visit)
    {
        $this->authorizeVisit($visit);
        $visit->load(['customer', 'collector']);

        return view('visits.show', compact('visit'));
    }

    public function edit(CollectionVisit $visit)
    {
        $this->authorizeVisit($visit);
        $customers = Customer::orderBy('name')->get(['id', 'customer_code', 'name']);

        return view('visits.edit', compact('visit', 'customers'));
    }

    public function update(Request $request, CollectionVisit $visit)
    {
        $this->authorizeVisit($visit);
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'visited_at' => ['required', 'date'],
            'result' => ['required', 'in:paid,not_home,recall_requested,cannot_pay,address_not_found,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $visit->update($validated);

        return redirect()->route('visits.show', $visit)->with('success', 'Kunjungan diperbarui.');
    }

    public function destroy(CollectionVisit $visit)
    {
        $this->authorizeVisit($visit);
        $visit->delete();

        return redirect()->route('visits.index')->with('success', 'Catatan kunjungan dihapus.');
    }

    private function authorizeVisit(CollectionVisit $visit): void
    {
        if (! request()->user()->isAdmin() && $visit->collector_id !== request()->user()->id) {
            abort(403);
        }
    }
}
