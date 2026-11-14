<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VanueController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $vanues = Vanue::query()
            ->when($vanueTerbatas !== null, fn ($query) => $query->whereKey($vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Vanue/Index', [
            'vanues' => $vanues,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->vanueTerbatas($this->adminAktif($request)) !== null) {
            abort(403, 'Admin venue hanya dapat mengelola venue miliknya sendiri.');
        }

        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['logo'] = $request->file('logo')->store('vanues', 'public');

        Vanue::create($this->bersihkanFasilitas($validated));

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil ditambahkan.');
    }

    public function update(Request $request, Vanue $vanue): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $vanue->id);

        $validated = $request->validate($this->aturanValidasi(false), $this->pesanValidasi());

        if ($request->hasFile('logo')) {
            if ($vanue->logo) {
                Storage::disk('public')->delete($vanue->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vanues', 'public');
        } else {
            unset($validated['logo']);
        }

        $vanue->update($this->bersihkanFasilitas($validated));

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil diperbarui.');
    }

    public function destroy(Request $request, Vanue $vanue): RedirectResponse
    {
        if ($this->vanueTerbatas($this->adminAktif($request)) !== null) {
            abort(403, 'Admin venue tidak dapat menghapus venue.');
        }

        if ($vanue->logo) {
            Storage::disk('public')->delete($vanue->logo);
        }

        $vanue->delete();

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil dihapus.');
    }

    /**
     * Rapikan daftar fasilitas: buang kosong dan duplikat.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function bersihkanFasilitas(array $validated): array
    {
        if (! array_key_exists('fasilitas', $validated)) {
            return $validated;
        }

        $validated['fasilitas'] = collect($validated['fasilitas'] ?? [])
            ->map(fn ($fasilitas) => trim((string) $fasilitas))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $validated;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(bool $logoWajib = true): array
    {
        return [
            'nama' => ['required', 'string', 'max:50'],
            'alamat' => ['required', 'string', 'max:255'],
            'fasilitas' => ['nullable', 'array', 'max:15'],
            'fasilitas.*' => ['required', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
            'logo' => [$logoWajib ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'nama.required' => 'Nama venue wajib diisi.',
            'nama.max' => 'Nama venue maksimal 50 karakter.',
            'alamat.required' => 'Alamat venue wajib diisi.',
            'alamat.max' => 'Alamat venue maksimal 255 karakter.',
            'fasilitas.max' => 'Fasilitas maksimal 15 item.',
            'fasilitas.*.max' => 'Nama fasilitas maksimal 50 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus di antara -90 sampai 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus di antara -180 sampai 180.',
            'status.in' => 'Status venue tidak valid.',
            'logo.required' => 'Logo venue wajib diunggah.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 5 MB.',
        ];
    }
}
