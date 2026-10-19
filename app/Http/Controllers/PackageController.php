<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::latest()->paginate(15);

        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'speed' => ['nullable', 'string', 'max:50'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        $package = Package::create($validated);
        AuditLog::record(auth()->id(), 'package.created', 'package', $package->id, null, $validated);

        return redirect()->route('packages.index')->with('success', 'Paket ditambahkan.');
    }

    public function show(Package $package)
    {
        $package->loadCount('customers');

        return view('packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'speed' => ['nullable', 'string', 'max:50'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', false);
        $old = $package->toArray();
        $package->update($validated);
        AuditLog::record(auth()->id(), 'package.updated', 'package', $package->id, $old, $validated);

        return redirect()->route('packages.index')->with('success', 'Paket diperbarui.');
    }

    public function destroy(Package $package)
    {
        if ($package->customers()->exists()) {
            return back()->with('error', 'Paket masih dipakai pelanggan. Nonaktifkan saja.');
        }
        $package->delete();
        AuditLog::record(auth()->id(), 'package.deleted', 'package', $package->id);

        return redirect()->route('packages.index')->with('success', 'Paket dihapus.');
    }
}
