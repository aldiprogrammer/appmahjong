<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    use VanueScope;

    public const ROLES = [
        'staff' => 'Staff',
        'admin' => 'Admin',
        'super_admin' => 'Super Admin',
    ];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');
        $vanueIdFilter = $this->vanueFilter($request);
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $admins = Admin::query()
            ->with('vanue')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%");
            })
            ->when(array_key_exists($role, self::ROLES), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($vanueIdFilter !== null, fn ($query) => $query->where('vanue_id', $vanueIdFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Admin/Index', [
            'admins' => $admins,
            'roles' => self::ROLES,
            'vanues' => $this->daftarVanueTersedia($request),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'vanue_id' => $vanueIdFilter,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi($request), $this->pesanValidasi());

        $validated['hak_akses'] = $this->normalisasiHakAkses($validated['hak_akses'] ?? null);

        $this->batasiAksesVanue($request, isset($validated['vanue_id']) ? (int) $validated['vanue_id'] : null);

        Admin::create($validated);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->batasiAksesVanue($request, $admin->vanue_id !== null ? (int) $admin->vanue_id : null);

        $validated = $request->validate($this->aturanValidasi($request, $admin), $this->pesanValidasi());

        if (array_key_exists('hak_akses', $validated)) {
            $validated['hak_akses'] = $this->normalisasiHakAkses($validated['hak_akses'] ?? null);
        }

        $this->batasiAksesVanue($request, isset($validated['vanue_id']) ? (int) $validated['vanue_id'] : null);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $admin->update($validated);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil diperbarui.');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        $this->batasiAksesVanue($request, $admin->vanue_id !== null ? (int) $admin->vanue_id : null);

        $admin->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Data admin berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(Request $request, ?Admin $admin = null): array
    {
        $wajibPilihVanue = (string) $request->input('role') !== Admin::ROLE_SUPER_ADMIN;

        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admins', 'username')->ignore($admin?->id),
            ],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'vanue_id' => [
                $wajibPilihVanue ? 'required' : 'nullable',
                'integer',
                Rule::exists('vanues', 'id'),
            ],
            'password' => [
                $admin === null ? 'required' : 'nullable',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],
            'hak_akses' => ['nullable', 'array'],
            'hak_akses.*' => ['string', Rule::in(array_keys(Admin::MENU_AKSES))],
        ];
    }

    /**
     * Hak akses disimpan sebagai daftar menu; bila semua menu terpilih disimpan
     * sebagai null (akses penuh, termasuk menu baru berikutnya).
     *
     * @param  array<int, string>|null  $hakAkses
     * @return array<int, string>|null
     */
    private function normalisasiHakAkses(?array $hakAkses): ?array
    {
        if ($hakAkses === null) {
            return null;
        }

        $hakAkses = array_values(array_unique($hakAkses));

        if (! in_array(Admin::MENU_DASHBOARD, $hakAkses, true)) {
            $hakAkses[] = Admin::MENU_DASHBOARD;
        }

        if (count($hakAkses) === count(Admin::MENU_AKSES)) {
            return null;
        }

        sort($hakAkses);

        return $hakAkses;
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'vanue_id.required' => 'Venue wajib dipilih untuk role staff dan admin.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'hak_akses.array' => 'Hak akses menu tidak valid.',
            'hak_akses.*.in' => 'Menu yang dipilih tidak dikenal.',
        ];
    }
}
