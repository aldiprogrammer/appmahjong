<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin_id')) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::query()->find($request->session()->get('admin_id'));
        $request->attributes->set('admin_aktif', $admin);

        $menu = $this->menuDariRute($request);

        if ($admin !== null && $menu !== null && ! $admin->bolehAksesMenu($menu)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', sprintf(
                    'Anda tidak memiliki hak akses untuk membuka menu "%s".',
                    Admin::MENU_AKSES[$menu],
                ));
        }

        return $next($request);
    }

    /**
     * Segmen menu dari nama rute panel admin, null bila bukan rute menu.
     */
    private function menuDariRute(Request $request): ?string
    {
        $namaRute = (string) $request->route()?->getName();

        if (! str_starts_with($namaRute, 'admin.')) {
            return null;
        }

        return explode('.', substr($namaRute, strlen('admin.')))[0];
    }
}
