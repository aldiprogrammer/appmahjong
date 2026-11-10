<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategorimenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KategoriMenuController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $kategoriMenus = Kategorimenu::query()
            ->withCount('menus')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('kategori', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/KategoriMenu/Index', [
            'kategoriMenus' => $kategoriMenus,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:50', Rule::unique('kategorimenus', 'kategori')],
        ], [
            'kategori.required' => 'Nama kategori wajib diisi.',
            'kategori.max' => 'Nama kategori maksimal 50 karakter.',
            'kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        Kategorimenu::create($validated);

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function update(Request $request, Kategorimenu $kategoriMenu): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kategorimenus', 'kategori')->ignore($kategoriMenu->id),
            ],
        ], [
            'kategori.required' => 'Nama kategori wajib diisi.',
            'kategori.max' => 'Nama kategori maksimal 50 karakter.',
            'kategori.unique' => 'Nama kategori sudah digunakan.',
        ]);

        $kategoriMenu->update($validated);

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Kategorimenu $kategoriMenu): RedirectResponse
    {
        if ($kategoriMenu->menus()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki menu.');
        }

        $kategoriMenu->delete();

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil dihapus.');
    }
}
