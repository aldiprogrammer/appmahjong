<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Kategorimenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Inertia\Inertia;
use Inertia\Response;

class KategoriMenuController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $kategoriMenus = Kategorimenu::query()
            ->with('vanue')
            ->withCount('menus')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('kategori', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/KategoriMenu/Index', [
            'kategoriMenus' => $kategoriMenus,
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

        $aturan = ['kategori' => ['required', 'string', 'max:50', $this->unikPerVanue($vanueTarget)]];

        if ($vanueTerbatas === null) {
            $aturan['vanue_id'] = ['nullable', 'integer', Rule::exists('vanues', 'id')];
        }

        $validated = $request->validate($aturan, $this->pesanValidasi());

        if ($vanueTerbatas !== null) {
            $validated['vanue_id'] = $vanueTerbatas;
        }

        Kategorimenu::create($validated);

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function update(Request $request, Kategorimenu $kategoriMenu): RedirectResponse
    {
        $this->batasiAksesVanue($request, $kategoriMenu->vanue_id !== null ? (int) $kategoriMenu->vanue_id : null);

        // Venue kategori tidak berubah saat edit, sehingga aturan unik memakai venue saat ini.
        $validated = $request->validate(
            ['kategori' => ['required', 'string', 'max:50', $this->unikPerVanue($kategoriMenu->vanue_id, $kategoriMenu)]],
            $this->pesanValidasi(),
        );

        $kategoriMenu->update($validated);

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Request $request, Kategorimenu $kategoriMenu): RedirectResponse
    {
        $this->batasiAksesVanue($request, $kategoriMenu->vanue_id !== null ? (int) $kategoriMenu->vanue_id : null);

        if ($kategoriMenu->menus()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki menu.');
        }

        $kategoriMenu->delete();

        return redirect()
            ->route('admin.kategori-menus.index')
            ->with('success', 'Kategori menu berhasil dihapus.');
    }

    /**
     * Venue tujuan saat menambah kategori, null bila kategori tanpa venue.
     */
    private function vanueDariInput(Request $request): ?int
    {
        return $request->filled('vanue_id') ? (int) $request->input('vanue_id') : null;
    }

    /**
     * Nama kategori hanya unik di dalam venue yang sama.
     */
    private function unikPerVanue(?int $vanueTarget, ?Kategorimenu $kategoriMenu = null): Unique
    {
        $rule = Rule::unique('kategorimenus', 'kategori');

        $rule = $vanueTarget === null
            ? $rule->whereNull('vanue_id')
            : $rule->where('vanue_id', $vanueTarget);

        if ($kategoriMenu !== null) {
            $rule->ignore($kategoriMenu->id);
        }

        return $rule;
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'kategori.required' => 'Nama kategori wajib diisi.',
            'kategori.max' => 'Nama kategori maksimal 50 karakter.',
            'kategori.unique' => 'Nama kategori sudah digunakan.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
        ];
    }
}
