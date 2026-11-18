<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aturanpointvenue;
use App\Models\Mahjongfaanrules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MahjongFaanRuleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $mahjongFaanRules = Mahjongfaanrules::query()
            ->with('aturanPoint:id,nama_aturan')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('faan_value', 'like', "%{$search}%")
                        ->orWhereHas('aturanPoint', function ($query) use ($search) {
                            $query->where('nama_aturan', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/MahjongFaanRule/Index', [
            'mahjongFaanRules' => $mahjongFaanRules,
            'aturanPoints' => Aturanpointvenue::query()
                ->orderBy('nama_aturan')
                ->get(['id', 'nama_aturan']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        Mahjongfaanrules::create($validated);

        return redirect()
            ->route('admin.mahjong-faan-rules.index')
            ->with('success', 'Data mahjong faan rule berhasil ditambahkan.');
    }

    public function update(Request $request, Mahjongfaanrules $mahjongFaanRule): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $mahjongFaanRule->update($validated);

        return redirect()
            ->route('admin.mahjong-faan-rules.index')
            ->with('success', 'Data mahjong faan rule berhasil diperbarui.');
    }

    public function destroy(Mahjongfaanrules $mahjongFaanRule): RedirectResponse
    {
        $mahjongFaanRule->delete();

        return redirect()
            ->route('admin.mahjong-faan-rules.index')
            ->with('success', 'Data mahjong faan rule berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'id_aturanpoint' => ['required', 'integer', Rule::exists('aturanpointvenues', 'id')],
            'kode' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:50'],
            'faan_value' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'id_aturanpoint.required' => 'Aturan point wajib dipilih.',
            'id_aturanpoint.integer' => 'Aturan point tidak valid.',
            'id_aturanpoint.exists' => 'Aturan point yang dipilih tidak ditemukan.',
            'kode.required' => 'Kode wajib diisi.',
            'kode.max' => 'Kode maksimal 50 karakter.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 50 karakter.',
            'faan_value.required' => 'Nilai faan wajib diisi.',
            'faan_value.integer' => 'Nilai faan harus berupa angka bulat.',
            'faan_value.min' => 'Nilai faan tidak boleh kurang dari 0.',
        ];
    }
}
