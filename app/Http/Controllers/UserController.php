<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('users.index', ['users' => $users, 'roles' => Role::cases()]);
    }

    public function create()
    {
        return view('users.create', ['roles' => Role::cases()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'collector'])],
            'is_active' => ['boolean'],
        ]);
        $validated['password'] = $request->input('password');
        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['password_confirmation']);

        $user = User::create($validated);
        AuditLog::record(auth()->id(), 'user.created', 'user', $user->id, null, ['username' => $user->username, 'role' => $user->role->value]);

        return redirect()->route('users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function show(User $user)
    {
        $user->loadCount(['payments', 'collectionVisits', 'cashSettlements']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'collector'])],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        $validated['is_active'] = $request->boolean('is_active', false);
        unset($validated['password_confirmation']);

        $old = $user->toArray();
        $user->update($validated);
        AuditLog::record(auth()->id(), 'user.updated', 'user', $user->id, $old, $validated);

        return redirect()->route('users.show', $user)->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }
        if ($user->payments()->exists()) {
            return back()->with('error', 'Pengguna memiliki transaksi terkait. Nonaktifkan saja.');
        }

        $user->delete();
        AuditLog::record(auth()->id(), 'user.deleted', 'user', $user->id);

        return redirect()->route('users.index')->with('success', 'Pengguna dihapus.');
    }
}
