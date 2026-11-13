<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'adminMenus' => fn () => $request->routeIs('admin.*') ? Admin::MENU_AKSES : null,
            'auth' => [
                'admin' => fn () => $this->adminAktif($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'booking_success' => fn () => $request->session()->get('booking_success'),
                'pesanan_sukses' => fn () => $request->session()->get('pesanan_sukses'),
            ],
        ];
    }

    /**
     * Admin yang sedang masuk pada panel admin, null untuk halaman di luar panel admin.
     *
     * @return array{id: int, username: string, role: string, hak_akses: array<int, string>|null, vanue: array{id: int, nama: string, logo_url: string|null}|null}|null
     */
    private function adminAktif(Request $request): ?array
    {
        if (! $request->routeIs('admin.*')) {
            return null;
        }

        $admin = $request->attributes->get('admin_aktif');

        if ($admin instanceof Admin) {
            $admin->loadMissing('vanue');
        } else {
            $admin = Admin::query()->with('vanue')->find($request->session()->get('admin_id'));
        }

        if ($admin === null) {
            return null;
        }

        return [
            'id' => $admin->id,
            'username' => $admin->username,
            'role' => $admin->role,
            'hak_akses' => $admin->hak_akses,
            'vanue' => $admin->vanue
                ? [
                    'id' => $admin->vanue->id,
                    'nama' => $admin->vanue->nama,
                    'logo_url' => $admin->vanue->logo_url,
                ]
                : null,
        ];
    }
}
