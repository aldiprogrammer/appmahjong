<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aturandasarfaan;
use App\Models\Aturanmahjong;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AturanDasarFaanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $aturanDasarFaans = Aturandasarfaan::query()
            ->with('aturanMahjong:id,kode,nama_aturan')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('minimal_faan', 'like', "%{$search}%")
                        ->orWhere('batas_faan', 'like', "%{$search}%")
                        ->orWhereHas('aturanMahjong', function ($query) use ($search) {
                            $query->where('kode', 'like', "%{$search}%")
                                ->orWhere('nama_aturan', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AturanDasarFaan/Index', [
            'aturanDasarFaans' => $aturanDasarFaans,
            'aturanMahjongs' => Aturanmahjong::query()
                ->orderBy('nama_aturan')
                ->get(['id', 'kode', 'nama_aturan']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        Aturandasarfaan::create($validated);

        return redirect()
            ->route('admin.aturan-dasar-faans.index')
            ->with('success', 'Aturan dasar faan berhasil ditambahkan.');
    }

    public function update(Request $request, Aturandasarfaan $aturanDasarFaan): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $aturanDasarFaan->update($validated);

        return redirect()
            ->route('admin.aturan-dasar-faans.index')
            ->with('success', 'Aturan dasar faan berhasil diperbarui.');
    }

    public function destroy(Aturandasarfaan $aturanDasarFaan): RedirectResponse
    {
        $aturanDasarFaan->delete();

        return redirect()
            ->route('admin.aturan-dasar-faans.index')
            ->with('success', 'Aturan dasar faan berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'id_aturanmahjong' => ['required', 'integer', Rule::exists('aturanmahjongs', 'id')],
            'minimal_faan' => ['required', 'integer', 'min:0'],
            'batas_faan' => ['required', 'integer', 'min:0'],
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
            'minimal_faan.required' => 'Minimal faan wajib diisi.',
            'minimal_faan.integer' => 'Minimal faan harus berupa angka bulat.',
            'minimal_faan.min' => 'Minimal faan tidak boleh kurang dari 0.',
            'batas_faan.required' => 'Batas faan wajib diisi.',
            'batas_faan.integer' => 'Batas faan harus berupa angka bulat.',
            'batas_faan.min' => 'Batas faan tidak boleh kurang dari 0.',
        ];
    }
}
