<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VanueController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $vanues = Vanue::query()
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
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $validated['logo'] = $request->file('logo')->store('vanues', 'public');

        Vanue::create($validated);

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil ditambahkan.');
    }

    public function update(Request $request, Vanue $vanue): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(false), $this->pesanValidasi());

        if ($request->hasFile('logo')) {
            if ($vanue->logo) {
                Storage::disk('public')->delete($vanue->logo);
            }

            $validated['logo'] = $request->file('logo')->store('vanues', 'public');
        } else {
            unset($validated['logo']);
        }

        $vanue->update($validated);

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil diperbarui.');
    }

    public function destroy(Vanue $vanue): RedirectResponse
    {
        if ($vanue->logo) {
            Storage::disk('public')->delete($vanue->logo);
        }

        $vanue->delete();

        return redirect()
            ->route('admin.vanues.index')
            ->with('success', 'Venue berhasil dihapus.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(bool $logoWajib = true): array
    {
        return [
            'nama' => ['required', 'string', 'max:50'],
            'alamat' => ['required', 'string', 'max:255'],
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
            'logo.required' => 'Logo venue wajib diunggah.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 5 MB.',
        ];
    }
}
