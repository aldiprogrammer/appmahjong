<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public const ROLES = [
        'staff' => 'Staff',
        'admin' => 'Admin',
        'super_admin' => 'Super Admin',
    ];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $admins = Admin::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%");
            })
            ->when(array_key_exists($role, self::ROLES), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Admin/Index', [
            'admins' => $admins,
            'roles' => self::ROLES,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('admins', 'username')],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        Admin::create($validated);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('admins', 'username')->ignore($admin->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $admin->update($validated);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil diperbarui.');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $admin->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil dihapus.');
    }
}
