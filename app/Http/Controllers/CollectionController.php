<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Dusun;
use App\Models\Invoice;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index()
    {
        $dusunList = Dusun::orderBy('name')->get();

        $stats = $dusunList->map(function ($dusun) {
            $customers = Customer::where('dusun', $dusun->name)
                ->with(['invoices' => fn ($q) => $q->unsettled()])
                ->get();

            $arrearsCount = $customers->filter(fn ($c) => $c->invoices->isNotEmpty())->count();
            $totalArrears = 0;
            foreach ($customers as $c) {
                foreach ($c->invoices as $i) {
                    $totalArrears += $i->balance();
                }
            }

            return (object) [
                'id' => $dusun->id,
                'name' => $dusun->name,
                'notes' => $dusun->notes,
                'total' => $customers->count(),
                'active' => $customers->where('status', 'active')->count(),
                'arrears_count' => $arrearsCount,
                'total_arrears' => $totalArrears,
            ];
        });

        $totalArrearsAll = (int) Invoice::unsettled()->sum('amount') - (int) Invoice::unsettled()->sum('paid_amount');

        return view('collections.index', compact('stats', 'totalArrearsAll'));
    }

    public function show(Dusun $dusun, Request $request)
    {
        $customers = Customer::where('dusun', $dusun->name)
            ->with(['package', 'invoices' => fn ($q) => $q->unsettled()->orderBy('billing_period')])
            ->when($request->get('filter') === 'arrears', function ($q) {
                $q->whereHas('invoices', fn ($sub) => $sub->unsettled());
            })
            ->orderBy('name')
            ->get();

        $totalArrears = 0;
        foreach ($customers as $c) {
            foreach ($c->invoices as $i) {
                $totalArrears += $i->balance();
            }
        }
        $withArrears = $customers->filter(fn ($c) => $c->invoices->isNotEmpty())->count();

        return view('collections.show', compact('dusun', 'customers', 'totalArrears', 'withArrears'));
    }
}
