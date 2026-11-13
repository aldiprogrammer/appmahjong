<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Admin;
use App\Models\Vanue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Pembatasan data panel admin berdasarkan venue milik admin yang sedang masuk.
 */
trait VanueScope
{
    /**
     * Admin yang sedang masuk panel, null bila akunnya sudah tidak ada.
     */
    protected function adminAktif(Request $request): ?Admin
    {
        return Admin::query()
            ->with('vanue')
            ->find($request->session()->get('admin_id'));
    }

    /**
     * Venue yang membatasi admin aktif, null bila admin dapat mengakses semua venue.
     */
    protected function vanueTerbatas(?Admin $admin): ?int
    {
        if ($admin === null || $admin->role === Admin::ROLE_SUPER_ADMIN || $admin->vanue_id === null) {
            return null;
        }

        return (int) $admin->vanue_id;
    }

    /**
     * Tolak akses admin venue terhadap data di luar venue miliknya.
     */
    protected function batasiAksesVanue(Request $request, ?int $vanueId): void
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        if ($vanueTerbatas === null || $vanueId === $vanueTerbatas) {
            return;
        }

        abort(403, 'Anda hanya dapat mengelola data pada venue Anda sendiri.');
    }

    /**
     * Daftar venue yang boleh dipilih admin aktif.
     */
    protected function daftarVanueTersedia(Request $request): Collection
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        return Vanue::query()
            ->when($vanueTerbatas !== null, fn ($query) => $query->whereKey($vanueTerbatas))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Nilai filter venue dari query string yang valid, null bila tidak difilter.
     */
    protected function vanueFilter(Request $request): ?int
    {
        $vanueId = (int) $request->query('vanue_id', 0);

        return $vanueId > 0 ? $vanueId : null;
    }
}
