<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aturanmahjong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AturanMahjongController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $aturanMahjongs = Aturanmahjong::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama_aturan', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AturanMahjong/Index', [
            'aturanMahjongs' => $aturanMahjongs,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function kodeBaru(): JsonResponse
    {
        return response()->json([
            'kode' => $this->kodeMahjongBaru(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['kode'] = $this->kodeMahjongBaru();
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($validated): void {
            if ($validated['is_active']) {
                Aturanmahjong::query()->where('is_active', true)->update(['is_active' => false]);
            }

            Aturanmahjong::create($validated);
        });

        return redirect()
            ->route('admin.aturan-mahjongs.index')
            ->with('success', 'Aturan mahjong berhasil ditambahkan.');
    }

    public function update(Request $request, Aturanmahjong $aturanMahjong): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($validated, $aturanMahjong): void {
            if ($validated['is_active']) {
                Aturanmahjong::query()
                    ->where('is_active', true)
                    ->where('id', '!=', $aturanMahjong->id)
                    ->update(['is_active' => false]);
            }

            $aturanMahjong->update($validated);
        });

        return redirect()
            ->route('admin.aturan-mahjongs.index')
            ->with('success', 'Aturan mahjong berhasil diperbarui.');
    }

    public function destroy(Aturanmahjong $aturanMahjong): RedirectResponse
    {
        $aturanMahjong->delete();

        return redirect()
            ->route('admin.aturan-mahjongs.index')
            ->with('success', 'Aturan mahjong berhasil dihapus.');
    }

    /**
     * Kode aturan dibuat berurutan dan tetap unik.
     */
    private function kodeMahjongBaru(): string
    {
        $nomor = 1;

        do {
            $kode = sprintf('AM%03d', $nomor);
            $nomor++;
        } while (Aturanmahjong::query()->where('kode', $kode)->exists());

        return $kode;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'nama_aturan' => ['required', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'nama_aturan.required' => 'Nama aturan wajib diisi.',
            'nama_aturan.string' => 'Nama aturan harus berupa teks.',
            'nama_aturan.max' => 'Nama aturan maksimal 200 karakter.',
            'is_active.boolean' => 'Penanda aturan aktif tidak valid.',
        ];
    }
}
