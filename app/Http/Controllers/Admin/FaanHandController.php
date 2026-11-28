<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aturanmahjong;
use App\Models\Faanhand;
use App\Models\Kategorifaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaanHandController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $faanHands = Faanhand::query()
            ->with('aturanMahjong:id,kode,nama_aturan')
            ->with('kategoriFaan:id,nama')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_faan', 'like', "%{$search}%")
                        ->orWhere('inggris', 'like', "%{$search}%")
                        ->orWhere('faan_asli', 'like', "%{$search}%")
                        ->orWhere('faan', 'like', "%{$search}%")
                        ->orWhereHas('aturanMahjong', function ($query) use ($search) {
                            $query->where('kode', 'like', "%{$search}%")
                                ->orWhere('nama_aturan', 'like', "%{$search}%");
                        })
                        ->orWhereHas('kategoriFaan', function ($query) use ($search) {
                            $query->where('nama', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/FaanHand/Index', [
            'faanHands' => $faanHands,
            'aturanMahjongs' => Aturanmahjong::query()
                ->orderBy('nama_aturan')
                ->get(['id', 'kode', 'nama_aturan']),
            'kategoriFaans' => Kategorifaan::query()
                ->orderBy('nama')
                ->get(['id', 'nama']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        Faanhand::create($validated);

        return redirect()
            ->route('admin.faan-hands.index')
            ->with('success', 'Faan hand berhasil ditambahkan.');
    }

    public function update(Request $request, Faanhand $faanHand): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $faanHand->update($validated);

        return redirect()
            ->route('admin.faan-hands.index')
            ->with('success', 'Faan hand berhasil diperbarui.');
    }

    public function destroy(Faanhand $faanHand): RedirectResponse
    {
        $faanHand->delete();

        return redirect()
            ->route('admin.faan-hands.index')
            ->with('success', 'Faan hand berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'id_aturanmahjong' => ['required', 'integer', Rule::exists('aturanmahjongs', 'id')],
            'id_kategorifaan' => ['required', 'integer', Rule::exists('kategorifaans', 'id')],
            'nama_faan' => ['required', 'string', 'max:50'],
            'inggris' => ['required', 'string', 'max:50'],
            'faan_asli' => ['required', 'integer', 'min:0'],
            'faan' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'id_aturanmahjong.required' => 'Aturan mahjong wajib dipilih.',
            'id_aturanmahjong.integer' => 'Aturan mahjong tidak valid.',
            'id_aturanmahjong.exists' => 'Aturan mahjong yang dipilih tidak ditemukan.',
            'id_kategorifaan.required' => 'Kategori faan wajib dipilih.',
            'id_kategorifaan.integer' => 'Kategori faan tidak valid.',
            'id_kategorifaan.exists' => 'Kategori faan yang dipilih tidak ditemukan.',
            'nama_faan.required' => 'Nama faan wajib diisi.',
            'nama_faan.string' => 'Nama faan harus berupa teks.',
            'nama_faan.max' => 'Nama faan maksimal 50 karakter.',
            'inggris.required' => 'Nama Inggris wajib diisi.',
            'inggris.string' => 'Nama Inggris harus berupa teks.',
            'inggris.max' => 'Nama Inggris maksimal 50 karakter.',
            'faan_asli.required' => 'Faan asli wajib diisi.',
            'faan_asli.integer' => 'Faan asli harus berupa angka bulat.',
            'faan_asli.min' => 'Faan asli tidak boleh kurang dari 0.',
            'faan.required' => 'Faan wajib diisi.',
            'faan.integer' => 'Faan harus berupa angka bulat.',
            'faan.min' => 'Faan tidak boleh kurang dari 0.',
        ];
    }
}
