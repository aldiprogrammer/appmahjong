<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuplayerFood;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuplayerFoodController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $suplayerFoods = SuplayerFood::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_suplayer', 'like', "%{$search}%")
                        ->orWhere('nama_suplayer', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%")
                        ->orWhere('nowa', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/SuplayerFood/Index', [
            'suplayerFoods' => $suplayerFoods,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_suplayer' => ['required', 'string', 'max:30', Rule::unique('suplayer_food', 'kode_suplayer')],
            'nama_suplayer' => ['required', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:255'],
            'nowa' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:30', Rule::unique('suplayer_food', 'email')],
            'password' => ['required', 'string', 'max:30'],
            'tangggl_daftar' => ['required', 'date', 'max:30'],
        ]);

        SuplayerFood::create($validated);

        return redirect()
            ->route('admin.suplayer-foods.index')
            ->with('success', 'Data suplayer food berhasil ditambahkan.');
    }

    public function update(Request $request, SuplayerFood $suplayerFood): RedirectResponse
    {
        $validated = $request->validate([
            'kode_suplayer' => ['required', 'string', 'max:30', Rule::unique('suplayer_food', 'kode_suplayer')->ignore($suplayerFood->id)],
            'nama_suplayer' => ['required', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:255'],
            'nowa' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:30', Rule::unique('suplayer_food', 'email')->ignore($suplayerFood->id)],
            'password' => ['required', 'string', 'max:30'],
            'tangggl_daftar' => ['required', 'date', 'max:30'],
        ]);

        $suplayerFood->update($validated);

        return redirect()
            ->route('admin.suplayer-foods.index')
            ->with('success', 'Data suplayer food berhasil diperbarui.');
    }

    public function destroy(SuplayerFood $suplayerFood): RedirectResponse
    {
        $suplayerFood->delete();

        return redirect()
            ->route('admin.suplayer-foods.index')
            ->with('success', 'Data suplayer food berhasil dihapus.');
    }

    public function kodeBaru(): JsonResponse
    {
        return response()->json([
            'kode_suplayer' => $this->kodeSuplayerBaru(),
        ]);
    }

    /**
     * Kode suplayer dibuat otomatis dari kombinasi huruf dan angka, dipastikan unik.
     */
    private function kodeSuplayerBaru(): string
    {
        $karakter = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $kode = 'SPF-'.substr(str_shuffle($karakter), 0, 6);
        } while (SuplayerFood::query()->where('kode_suplayer', $kode)->exists());

        return $kode;
    }
}
