<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
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
    use VanueScope;

    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $hargaJamMains = hargajammain::query()
            ->with(['jamMain', 'vanue'])
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
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
            'vanues' => $this->daftarVanueTersedia($request),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));
        $vanueTarget = $vanueTerbatas ?? $this->vanueDariInput($request);

        $aturan = $this->aturanValidasi();

        if ($vanueTerbatas === null) {
            $aturan['vanue_id'] = ['nullable', 'integer', Rule::exists('vanues', 'id')];
        }

        $validated = $request->validate($aturan, $this->pesanValidasi());
        $validated = $this->hitungHargaAkhir($validated);

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
                    'vanue_id' => $vanueTarget,
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
        $this->batasiAksesVanue(
            $request,
            $hargaJamMain->vanue_id !== null ? (int) $hargaJamMain->vanue_id : null,
        );

        // Venue harga tidak berubah saat edit, hanya tarifnya.
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());
        $validated = $this->hitungHargaAkhir($validated);

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

    public function destroy(Request $request, hargajammain $hargaJamMain): RedirectResponse
    {
        $this->batasiAksesVanue(
            $request,
            $hargaJamMain->vanue_id !== null ? (int) $hargaJamMain->vanue_id : null,
        );

        $hargaJamMain->delete();

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', 'Data harga jam main berhasil dihapus.');
    }

    /**
     * Venue tujuan saat menambah harga, null bila harga tanpa venue.
     */
    private function vanueDariInput(Request $request): ?int
    {
        return $request->filled('vanue_id') ? (int) $request->input('vanue_id') : null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'jam_main_ids' => ['required', 'array', 'min:1'],
            'jam_main_ids.*' => ['required', 'string', 'distinct', 'exists:jam_mains,id'],
            'hari_list' => ['required', 'array', 'min:1'],
            'hari_list.*' => ['required', 'string', 'distinct', Rule::in(self::HARI)],
            'harga' => ['required', 'numeric', 'min:0'],
            'diskon' => ['required', 'numeric', 'min:0', 'lte:harga'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'jam_main_ids.required' => 'Jam main wajib dipilih.',
            'jam_main_ids.min' => 'Pilih minimal satu jam main.',
            'jam_main_ids.*.required' => 'Jam main tidak boleh kosong.',
            'jam_main_ids.*.distinct' => 'Jam main tidak boleh dipilih dua kali.',
            'jam_main_ids.*.exists' => 'Jam main yang dipilih tidak ditemukan.',
            'hari_list.required' => 'Hari wajib dipilih.',
            'hari_list.min' => 'Pilih minimal satu hari.',
            'hari_list.*.required' => 'Hari tidak boleh kosong.',
            'hari_list.*.distinct' => 'Hari tidak boleh dipilih dua kali.',
            'hari_list.*.in' => 'Pilihan hari tidak valid.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',
            'diskon.required' => 'Diskon wajib diisi.',
            'diskon.numeric' => 'Diskon harus berupa angka.',
            'diskon.min' => 'Diskon tidak boleh negatif.',
            'diskon.lte' => 'Diskon tidak boleh lebih besar dari harga.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function hitungHargaAkhir(array $validated): array
    {
        $validated['harga_akhir'] = (string) max(
            0,
            (float) $validated['harga'] - (float) $validated['diskon']
        );

        return $validated;
    }
}
