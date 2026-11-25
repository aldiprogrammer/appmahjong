<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\PesananDetail;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StokMenuService
{
    /**
     * Potong stok menu yang memakai qty. Dipanggil di dalam transaksi agar
     * pengecekan dan pengurangan terjadi atomik.
     *
     * @param  array<int, array{id_menu:string, jumlah:int, stok_dikurangi?:bool}>  $rincian
     */
    public function kurangiStokDariRincian(array $rincian): void
    {
        $rincianDenganStok = collect($rincian)
            ->filter(fn (array $item): bool => (bool) ($item['stok_dikurangi'] ?? false));

        foreach ($this->jumlahPerMenu($rincianDenganStok) as $idMenu => $jumlah) {
            $menu = Menu::query()->whereKey($idMenu)->lockForUpdate()->first();

            if (! $menu?->pakai_qty) {
                continue;
            }

            $this->pastikanStokCukup($menu, $jumlah);
            $menu->decrement('qty', $jumlah);
        }
    }

    /**
     * Kembalikan stok saat pesanan dibatalkan.
     */
    public function kembalikanStokPesanan(Pesanan $pesanan): void
    {
        $details = $pesanan->details()->where('stok_dikurangi', true)->get();

        foreach ($this->jumlahPerMenu($details) as $idMenu => $jumlah) {
            $menu = Menu::query()->whereKey($idMenu)->lockForUpdate()->first();

            if (! $menu?->pakai_qty) {
                continue;
            }

            $menu->increment('qty', $jumlah);
        }

        if ($details->isNotEmpty()) {
            $pesanan->details()->where('stok_dikurangi', true)->update(['stok_dikurangi' => false]);
        }
    }

    /**
     * Potong kembali stok saat pesanan yang dibatalkan diaktifkan ulang.
     */
    public function potongStokPesanan(Pesanan $pesanan): void
    {
        $details = $pesanan->details()->where('stok_dikurangi', false)->get();

        $idMenuTerpotong = [];

        foreach ($this->jumlahPerMenu($details) as $idMenu => $jumlah) {
            $menu = Menu::query()->whereKey($idMenu)->lockForUpdate()->first();

            if (! $menu?->pakai_qty) {
                continue;
            }

            $this->pastikanStokCukup($menu, $jumlah);
            $menu->decrement('qty', $jumlah);
            $idMenuTerpotong[] = (string) $idMenu;
        }

        if ($idMenuTerpotong !== []) {
            $pesanan->details()
                ->whereIn('id_menu', $idMenuTerpotong)
                ->where('stok_dikurangi', false)
                ->update(['stok_dikurangi' => true]);
        }
    }

    /**
     * @param  Collection<int, array{id_menu:mixed, jumlah:mixed}|PesananDetail>  $items
     * @return Collection<string, int>
     */
    private function jumlahPerMenu(Collection $items): Collection
    {
        return $items
            ->groupBy(fn ($item): string => (string) ($item['id_menu'] ?? ''))
            ->filter(fn (Collection $group, string $idMenu): bool => $idMenu !== '')
            ->map(fn (Collection $group): int => (int) $group->sum(fn ($item): int => (int) $item['jumlah']));
    }

    private function pastikanStokCukup(Menu $menu, int $jumlah): void
    {
        if ((int) $menu->qty < $jumlah) {
            throw ValidationException::withMessages([
                'items' => "Stok {$menu->nama} tidak mencukupi. Tersedia {$menu->qty}.",
            ]);
        }
    }
}
