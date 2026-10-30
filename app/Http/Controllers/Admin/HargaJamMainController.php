<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\hargajammain;
use App\Models\jam_main;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HargaJamMainController extends Controller
{
    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $hargaJamMains = hargajammain::query()
            ->with('jamMain')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('hari', 'like', "%{$search}%")
                        ->orWhere('harga', 'like', "%{$search}%")
                        ->orWhere('harga_akhir', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/HargaJamMain/Index', [
            'hargaJamMains' => $hargaJamMains,
            'jamMains' => jam_main::query()
                ->orderBy('jam_mulai')
                ->get(['id', 'jam_mulai', 'jam_selesai', 'status']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $jamMainIds = $validated['jam_main_ids'];
        $hariList = $validated['hari_list'];

        foreach ($jamMainIds as $jamMainId) {
            foreach ($hariList as $hari) {
                hargajammain::create([
                    'jam_main_id' => $jamMainId,
                    'hari' => $hari,
                    'harga' => $validated['harga'],
                    'diskon' => $validated['diskon'],
                    'harga_akhir' => $validated['harga_akhir'],
                ]);
            }
        }

        $jumlah = count($jamMainIds) * count($hariList);
        $pesan = $jumlah === 1
            ? 'Data harga jam main berhasil ditambahkan.'
            : "Data harga berhasil ditambahkan untuk {$jumlah} kombinasi hari dan jam main.";

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', $pesan);
    }

    public function update(Request $request, hargajammain $hargaJamMain): RedirectResponse
    {
        $validated = $this->validated($request);

        $hargaJamMain->update([
            'jam_main_id' => $validated['jam_main_ids'][0],
            'hari' => $validated['hari_list'][0],
            'harga' => $validated['harga'],
            'diskon' => $validated['diskon'],
            'harga_akhir' => $validated['harga_akhir'],
        ]);

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', 'Data harga jam main berhasil diperbarui.');
    }

    public function destroy(hargajammain $hargaJamMain): RedirectResponse
    {
        $hargaJamMain->delete();

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', 'Data harga jam main berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'jam_main_ids' => ['required', 'array', 'min:1'],
            'jam_main_ids.*' => ['required', 'string', 'distinct', 'exists:jam_mains,id'],
            'hari_list' => ['required', 'array', 'min:1'],
            'hari_list.*' => ['required', 'string', 'distinct', Rule::in(self::HARI)],
            'harga' => ['required', 'numeric', 'min:0'],
            'diskon' => ['required', 'numeric', 'min:0', 'lte:harga'],
        ]);

        $validated['harga_akhir'] = (string) max(
            0,
            (float) $validated['harga'] - (float) $validated['diskon']
        );

        return $validated;
    }
}
