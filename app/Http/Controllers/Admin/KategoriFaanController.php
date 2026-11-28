<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategorifaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KategoriFaanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $kategoriFaans = Kategorifaan::query()
            ->withCount('faanHands')
            ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/KategoriFaan/Index', [
            'kategoriFaans' => $kategoriFaans,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['nama' => ['required', 'string', 'max:50', Rule::unique('kategorifaans', 'nama')]],
            $this->pesanValidasi(),
        );

        Kategorifaan::create($validated);

        return redirect()
            ->route('admin.kategori-faans.index')
            ->with('success', 'Kategori faan berhasil ditambahkan.');
    }

    public function update(Request $request, Kategorifaan $kategoriFaan): RedirectResponse
    {
        $validated = $request->validate(
            ['nama' => ['required', 'string', 'max:50', Rule::unique('kategorifaans', 'nama')->ignore($kategoriFaan->id)]],
            $this->pesanValidasi(),
        );

        $kategoriFaan->update($validated);

        return redirect()
            ->route('admin.kategori-faans.index')
            ->with('success', 'Kategori faan berhasil diperbarui.');
    }

    public function destroy(Kategorifaan $kategoriFaan): RedirectResponse
    {
        if ($kategoriFaan->faanHands()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki faan hand.');
        }

        $kategoriFaan->delete();

        return redirect()
            ->route('admin.kategori-faans.index')
            ->with('success', 'Kategori faan berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.string' => 'Nama kategori harus berupa teks.',
            'nama.max' => 'Nama kategori maksimal 50 karakter.',
            'nama.unique' => 'Nama kategori sudah digunakan.',
        ];
    }
}
