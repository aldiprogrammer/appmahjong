<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PembayaranController extends Controller
{
    use VanueScope;

    /**
     * Halaman pengaturan metode pembayaran (Transfer dan QRIS) per venue.
     *
     * Metode pembayaran diambil dari tabel `pembayarans`. Untuk metode
     * `qris`, admin wajib mengunggah gambar QRIS; sedangkan metode
     * `transfer` diisi data bank, nama pemilik rekening, dan nomor rekening.
     */
    public function index(Request $request): Response
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $pembayarans = Pembayaran::query()
            ->with('vanue:id,nama')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('id_venue', $vanueTerbatas))
            ->latest('id')
            ->get(['id', 'id_venue', 'metode_pembayaran', 'bank', 'atas_nama', 'norek', 'qris_image']);

        return Inertia::render('Admin/Pembayaran/Index', [
            'pembayarans' => $pembayarans,
            'vanues' => $this->daftarVanueTersedia($request)
                ->map(fn (Vanue $vanue) => ['id' => $vanue->id, 'nama' => $vanue->nama])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_venue' => ['required', Rule::exists('vanues', 'id')],
            'metode_pembayaran' => ['required', Rule::in(['transfer', 'qris'])],
            'bank' => ['required_if:metode_pembayaran,transfer', 'nullable', 'string', 'max:30'],
            'atas_nama' => ['required', 'string', 'max:30'],
            'norek' => ['required_if:metode_pembayaran,transfer', 'nullable', 'string', 'max:30'],
            'qris_image' => ['required_if:metode_pembayaran,qris', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['id_venue']);

        if (($validated['metode_pembayaran'] ?? '') === 'qris') {
            if (! $request->hasFile('qris_image')) {
                return back()
                    ->withErrors(['qris_image' => 'Upload gambar QRIS untuk metode QRIS.'])
                    ->withInput();
            }

            $validated['qris_image'] = $request->file('qris_image')->store('pembayarans', 'public');
        }

        Pembayaran::create($validated);

        return back()
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function destroy(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $pembayaran->id_venue);

        $pembayaran->delete();

        return back()
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
