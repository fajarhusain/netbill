<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Dusun;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DusunController extends Controller
{
    public function index()
    {
        $dusun = Dusun::withCount('customers')->orderBy('name')->paginate(20);

        return view('dusun.index', compact('dusun'));
    }

    public function create()
    {
        return view('dusun.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('dusun', 'name')],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dusun = Dusun::create($validated);
        AuditLog::record(auth()->id(), 'dusun.created', 'dusun', $dusun->id, null, $validated);

        return redirect()->route('dusun.index')->with('success', 'Dusun ditambahkan.');
    }

    public function edit(Dusun $dusun)
    {
        return view('dusun.edit', compact('dusun'));
    }

    public function update(Request $request, Dusun $dusun)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('dusun', 'name')->ignore($dusun->id)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $old = $dusun->toArray();
        $oldName = $dusun->name;

        $dusun->update($validated);

        if ($validated['name'] !== $oldName) {
            Customer::where('dusun', $oldName)->update(['dusun' => $validated['name']]);
        }

        AuditLog::record(auth()->id(), 'dusun.updated', 'dusun', $dusun->id, $old, $validated);

        return redirect()->route('dusun.index')->with('success', 'Dusun diperbarui.');
    }

    public function destroy(Dusun $dusun)
    {
        if (Customer::where('dusun', $dusun->name)->exists()) {
            return back()->with('error', 'Dusun masih dipakai pelanggan. Pindahkan atau ubah dusun pelanggan terlebih dahulu.');
        }

        $dusun->delete();
        AuditLog::record(auth()->id(), 'dusun.deleted', 'dusun', $dusun->id);

        return redirect()->route('dusun.index')->with('success', 'Dusun dihapus.');
    }
}
