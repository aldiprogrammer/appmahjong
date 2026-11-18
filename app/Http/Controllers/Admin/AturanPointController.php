<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aturanpointvenue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AturanPointController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $aturanPoints = Aturanpointvenue::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_aturan', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/AturanPoint/Index', [
            'aturanPoints' => $aturanPoints,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['slug'] = $this->slugUnik($validated['nama_aturan'], $validated['slug'] ?? null);

        Aturanpointvenue::create($validated);

        return redirect()
            ->route('admin.aturan-points.index')
            ->with('success', 'Aturan point berhasil ditambahkan.');
    }

    public function update(Request $request, Aturanpointvenue $aturanPoint): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['slug'] = $this->slugUnik(
            $validated['nama_aturan'],
            $validated['slug'] ?? null,
            (int) $aturanPoint->id,
        );

        $aturanPoint->update($validated);

        return redirect()
            ->route('admin.aturan-points.index')
            ->with('success', 'Aturan point berhasil diperbarui.');
    }

    public function destroy(Aturanpointvenue $aturanPoint): RedirectResponse
    {
        $aturanPoint->delete();

        return redirect()
            ->route('admin.aturan-points.index')
            ->with('success', 'Aturan point berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(): array
    {
        return [
            'nama_aturan' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'deskripsi' => ['required', 'string', 'max:200'],
            'min_faan' => ['required', 'integer', 'min:0'],
            'max_faan' => ['required', 'integer', 'min:0', 'gte:min_faan'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'nama_aturan.required' => 'Nama aturan wajib diisi.',
            'nama_aturan.max' => 'Nama aturan maksimal 200 karakter.',
            'slug.max' => 'Slug maksimal 200 karakter.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.max' => 'Deskripsi maksimal 200 karakter.',
            'min_faan.required' => 'Minimal faan wajib diisi.',
            'min_faan.integer' => 'Minimal faan harus berupa angka bulat.',
            'min_faan.min' => 'Minimal faan tidak boleh kurang dari 0.',
            'max_faan.required' => 'Maksimal faan wajib diisi.',
            'max_faan.integer' => 'Maksimal faan harus berupa angka bulat.',
            'max_faan.min' => 'Maksimal faan tidak boleh kurang dari 0.',
            'max_faan.gte' => 'Maksimal faan tidak boleh lebih kecil dari minimal faan.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status aturan point tidak valid.',
        ];
    }

    /**
     * Slug diisi otomatis dari nama aturan bila kosong, lalu dibuat unik.
     */
    private function slugUnik(string $namaAturan, ?string $slug, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($slug !== null && $slug !== '' ? $slug : $namaAturan);

        if ($dasar === '') {
            $dasar = 'aturan-point';
        }

        $slugUnik = $dasar;
        $nomor = 2;

        while (Aturanpointvenue::query()
            ->where('slug', $slugUnik)
            ->when($abaikanId !== null, fn ($query) => $query->whereKeyNot($abaikanId))
            ->exists()) {
            $slugUnik = $dasar.'-'.$nomor;
            $nomor++;
        }

        return $slugUnik;
    }
}
