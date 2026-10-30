<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MejaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $mejas = Meja::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_meja', 'like', "%{$search}%")
                        ->orWhere('nama_meja', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Meja/Index', [
            'mejas' => $mejas,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_meja' => ['required', 'string', 'max:50', Rule::unique(Meja::class, 'kode_meja')],
            'nama_meja' => ['required', 'string', 'max:50'],
        ]);

        Meja::create($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil ditambahkan.');
    }

    public function update(Request $request, Meja $meja): RedirectResponse
    {
        $validated = $request->validate([
            'kode_meja' => ['required', 'string', 'max:50', Rule::unique(Meja::class, 'kode_meja')->ignore($meja->id)],
            'nama_meja' => ['required', 'string', 'max:50'],
        ]);

        $meja->update($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil diperbarui.');
    }

    public function destroy(Meja $meja): RedirectResponse
    {
        $meja->delete();

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil dihapus.');
    }
}
