<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\paket_hargajammain;
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
            'paketHargas' => paket_hargajammain::query()
                ->with('vanue:id,nama')
                ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
                ->orderBy('minimal_jam')
                ->latest()
                ->get(),
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

    public function storePaket(Request $request): RedirectResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));
        $vanueTarget = $vanueTerbatas ?? $this->vanueDariInput($request);

        $aturan = $this->aturanValidasiPaket();

        if ($vanueTerbatas === null) {
            $aturan['vanue_id'] = ['nullable', 'integer', Rule::exists('vanues', 'id')];
        }

        $validated = $request->validate($aturan, $this->pesanValidasiPaket());

        foreach ($validated['hari_list'] as $hari) {
            paket_hargajammain::create([
                'vanue_id' => $vanueTarget,
                'hari' => $hari,
                'minimal_jam' => $validated['minimal_jam'],
                'maksimal_jam' => $validated['maksimal_jam'] ?? null,
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'diskon_persen' => $validated['diskon_persen'] ?? null,
                'harga_paket' => $validated['harga_paket'] ?? null,
            ]);
        }

        $jumlah = count($validated['hari_list']);
        $pesan = $jumlah === 1
            ? 'Paket harga berhasil ditambahkan.'
            : "Paket harga berhasil ditambahkan untuk {$jumlah} hari.";

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', $pesan);
    }

    public function updatePaket(Request $request, paket_hargajammain $paket): RedirectResponse
    {
        $this->batasiAksesVanue(
            $request,
            $paket->vanue_id !== null ? (int) $paket->vanue_id : null,
        );

        // Venue paket tidak berubah saat edit, hanya aturannya.
        $validated = $request->validate($this->aturanValidasiPaket(), $this->pesanValidasiPaket());

        $paket->update([
            'hari' => $validated['hari_list'][0],
            'minimal_jam' => $validated['minimal_jam'],
            'maksimal_jam' => $validated['maksimal_jam'] ?? null,
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'diskon_persen' => $validated['diskon_persen'] ?? null,
            'harga_paket' => $validated['harga_paket'] ?? null,
        ]);

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', 'Paket harga berhasil diperbarui.');
    }

    public function destroyPaket(Request $request, paket_hargajammain $paket): RedirectResponse
    {
        $this->batasiAksesVanue(
            $request,
            $paket->vanue_id !== null ? (int) $paket->vanue_id : null,
        );

        $paket->delete();

        return redirect()
            ->route('admin.harga-jam-mains.index')
            ->with('success', 'Paket harga berhasil dihapus.');
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
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasiPaket(): array
    {
        return [
            'hari_list' => ['required', 'array', 'min:1'],
            'hari_list.*' => ['required', 'string', 'distinct', Rule::in(self::HARI)],
            'minimal_jam' => ['required', 'integer', 'min:1', 'max:168'],
            'maksimal_jam' => ['nullable', 'integer', 'min:1', 'max:168', 'gte:minimal_jam'],
            'jam_mulai' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'jam_selesai' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'diskon_persen' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_without:harga_paket'],
            'harga_paket' => ['nullable', 'numeric', 'min:0', 'required_without:diskon_persen'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasiPaket(): array
    {
        return [
            'hari_list.required' => 'Hari wajib dipilih.',
            'hari_list.min' => 'Pilih minimal satu hari.',
            'hari_list.*.required' => 'Hari tidak boleh kosong.',
            'hari_list.*.distinct' => 'Hari tidak boleh dipilih dua kali.',
            'hari_list.*.in' => 'Pilihan hari tidak valid.',
            'minimal_jam.required' => 'Minimal jam wajib diisi.',
            'minimal_jam.integer' => 'Minimal jam harus berupa angka bulat.',
            'minimal_jam.min' => 'Minimal jam minimal 1.',
            'minimal_jam.max' => 'Minimal jam tidak boleh lebih dari 168.',
            'maksimal_jam.integer' => 'Maksimal jam harus berupa angka bulat.',
            'maksimal_jam.min' => 'Maksimal jam minimal 1.',
            'maksimal_jam.max' => 'Maksimal jam tidak boleh lebih dari 168.',
            'maksimal_jam.gte' => 'Maksimal jam harus lebih besar atau sama dengan minimal jam.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.regex' => 'Format jam mulai tidak valid (HH:MM).',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.regex' => 'Format jam selesai tidak valid (HH:MM).',
            'diskon_persen.required' => 'Diskon persen wajib diisi.',
            'diskon_persen.numeric' => 'Diskon persen harus berupa angka.',
            'diskon_persen.min' => 'Diskon persen tidak boleh negatif.',
            'diskon_persen.max' => 'Diskon persen tidak boleh lebih dari 100.',
            'diskon_persen.required_without' => 'Isi diskon persen atau harga paket.',
            'harga_paket.numeric' => 'Harga paket harus berupa angka.',
            'harga_paket.min' => 'Harga paket tidak boleh negatif.',
            'harga_paket.required_without' => 'Isi harga paket atau diskon persen.',
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
