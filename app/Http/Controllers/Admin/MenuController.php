<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Kategorimenu;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $menus = Menu::query()
            ->with(['kategori', 'vanue'])
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhereHas('kategori', function ($query) use ($search) {
                            $query->where('kategori', 'like', "%{$search}%");
                        });
                });
            })
            ->when($kategori !== '', function ($query) use ($kategori) {
                $query->where('id_kategori', $kategori);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $kategoriMenus = Kategorimenu::query()
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->orderBy('kategori')
            ->get(['id', 'kategori', 'vanue_id']);

        return Inertia::render('Admin/Menu/Index', [
            'menus' => $menus,
            'kategoriMenus' => $kategoriMenus,
            'vanues' => $this->daftarVanueTersedia($request),
            'filters' => [
                'search' => $search,
                'kategori' => $kategori,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $isMinuman = $this->kategoriMinuman($request);

        $validated = $request->validate(
            $this->aturanValidasi($isMinuman, false, $vanueTerbatas ?? $this->vanueDariInput($request)),
            $this->pesanValidasi(),
        );

        $validated = $this->normalisasiHarga($validated, $isMinuman);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $request->file('gambar')->store('menus', 'public');
        }

        $validated['vanue_id'] = $vanueTerbatas ?? $this->vanueDariInput($request);

        Menu::create($validated);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $this->batasiAksesVanue($request, $menu->vanue_id !== null ? (int) $menu->vanue_id : null);

        $isMinuman = $this->kategoriMinuman($request);

        $validated = $request->validate(
            $this->aturanValidasi($isMinuman, true, $menu->vanue_id),
            $this->pesanValidasi(),
        );

        $validated = $this->normalisasiHarga($validated, $isMinuman);

        if ($request->hasFile('gambar')) {
            if ($menu->gambar) {
                Storage::disk('public')->delete($menu->gambar);
            }

            $validated['gambar'] = $request->file('gambar')->store('menus', 'public');
        } elseif ($request->boolean('hapus_gambar') && $menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);

            $validated['gambar'] = null;
        }

        unset($validated['hapus_gambar']);

        // Venue menu tidak berubah saat edit agar tetap senada dengan kategorinya.
        $menu->update($validated);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Menu $menu): RedirectResponse
    {
        $this->batasiAksesVanue($request, $menu->vanue_id !== null ? (int) $menu->vanue_id : null);

        $menu->update([
            'status' => $menu->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('success', 'Status menu berhasil diperbarui.');
    }

    public function destroy(Request $request, Menu $menu): RedirectResponse
    {
        $this->batasiAksesVanue($request, $menu->vanue_id !== null ? (int) $menu->vanue_id : null);

        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }

        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    /**
     * Venue tujuan saat menambah menu, null bila menu tanpa venue.
     */
    private function vanueDariInput(Request $request): ?int
    {
        return $request->filled('vanue_id') ? (int) $request->input('vanue_id') : null;
    }

    private function kategoriMinuman(Request $request): bool
    {
        $kategori = Kategorimenu::query()->find($request->input('id_kategori'));

        return $kategori !== null && Str::contains(Str::lower($kategori->kategori), 'minum');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(bool $isMinuman, bool $untukUpdate, ?int $vanueTarget): array
    {
        $aturan = [
            'id_kategori' => [
                'required',
                'string',
                Rule::exists('kategorimenus', 'id')->where(function ($query) use ($vanueTarget) {
                    $vanueTarget === null
                        ? $query->whereNull('vanue_id')
                        : $query->where('vanue_id', $vanueTarget);
                }),
            ],
            'nama' => ['required', 'string', 'max:50'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'dibuat_di' => ['required', 'string', Rule::in(['Dapur', 'Bar'])],
            'harga' => [$isMinuman ? 'nullable' : 'required', 'numeric', 'min:0'],
            'harga_dingin' => [$isMinuman ? 'required' : 'nullable', 'numeric', 'min:0'],
            'harga_panas' => [$isMinuman ? 'required' : 'nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
        ];

        if ($untukUpdate) {
            $aturan['hapus_gambar'] = ['sometimes', 'boolean'];
        } else {
            $aturan['vanue_id'] = ['nullable', 'integer', Rule::exists('vanues', 'id')];
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'id_kategori.required' => 'Kategori menu wajib dipilih.',
            'id_kategori.exists' => 'Kategori menu tidak ditemukan atau bukan milik venue Anda.',
            'nama.required' => 'Nama menu wajib diisi.',
            'nama.max' => 'Nama menu maksimal 50 karakter.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG, JPEG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 5 MB.',
            'dibuat_di.required' => 'Pilihan dibuat di wajib diisi.',
            'dibuat_di.in' => 'Pilihan dibuat di tidak valid.',
            'harga.required' => 'Harga menu wajib diisi.',
            'harga.numeric' => 'Harga menu harus berupa angka.',
            'harga.min' => 'Harga menu tidak boleh negatif.',
            'harga_dingin.required' => 'Harga minuman dingin wajib diisi.',
            'harga_dingin.numeric' => 'Harga minuman dingin harus berupa angka.',
            'harga_dingin.min' => 'Harga minuman dingin tidak boleh negatif.',
            'harga_panas.required' => 'Harga minuman panas wajib diisi.',
            'harga_panas.numeric' => 'Harga minuman panas harus berupa angka.',
            'harga_panas.min' => 'Harga minuman panas tidak boleh negatif.',
            'status.required' => 'Status menu wajib dipilih.',
            'status.in' => 'Status menu tidak valid.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalisasiHarga(array $validated, bool $isMinuman): array
    {
        if ($isMinuman) {
            $validated['harga'] = (string) $validated['harga_dingin'];
            $validated['harga_dingin'] = (string) $validated['harga_dingin'];
            $validated['harga_panas'] = (string) $validated['harga_panas'];
        } else {
            $validated['harga'] = (string) $validated['harga'];
            $validated['harga_dingin'] = null;
            $validated['harga_panas'] = null;
        }

        unset($validated['hapus_gambar']);

        return $validated;
    }
}
