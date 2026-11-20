<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
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
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $suplayerFoods = SuplayerFood::query()
            ->with('vanue')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
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
            'vanues' => $this->daftarVanueTersedia($request),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $aturan = [
            'kode_suplayer' => ['required', 'string', 'max:30', Rule::unique('suplayer_food', 'kode_suplayer')],
            'nama_suplayer' => ['required', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:255'],
            'nowa' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:30', Rule::unique('suplayer_food', 'email')],
            'password' => ['required', 'string', 'max:30'],
            'tangggl_daftar' => ['required', 'date', 'max:30'],
        ];

        if ($vanueTerbatas === null) {
            $aturan['vanue_id'] = ['nullable', 'integer', Rule::exists('vanues', 'id')];
        }

        $validated = $request->validate($aturan, $this->pesanValidasi());

        $validated['vanue_id'] = $vanueTerbatas ?? ($request->filled('vanue_id') ? (int) $request->input('vanue_id') : null);

        SuplayerFood::create($validated);

        return redirect()
            ->route('admin.suplayer-foods.index')
            ->with('success', 'Data suplayer food berhasil ditambahkan.');
    }

    public function update(Request $request, SuplayerFood $suplayerFood): RedirectResponse
    {
        $this->batasiAksesVanue($request, $suplayerFood->vanue_id !== null ? (int) $suplayerFood->vanue_id : null);

        $validated = $request->validate([
            'kode_suplayer' => ['required', 'string', 'max:30', Rule::unique('suplayer_food', 'kode_suplayer')->ignore($suplayerFood->id)],
            'nama_suplayer' => ['required', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:255'],
            'nowa' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:30', Rule::unique('suplayer_food', 'email')->ignore($suplayerFood->id)],
            'password' => ['required', 'string', 'max:30'],
            'tangggl_daftar' => ['required', 'date', 'max:30'],
        ], $this->pesanValidasi());

        // Venue suplayer tidak diubah saat edit agar tetap senada dengan menu yang memakainya.
        $suplayerFood->update($validated);

        return redirect()
            ->route('admin.suplayer-foods.index')
            ->with('success', 'Data suplayer food berhasil diperbarui.');
    }

    public function destroy(Request $request, SuplayerFood $suplayerFood): RedirectResponse
    {
        $this->batasiAksesVanue($request, $suplayerFood->vanue_id !== null ? (int) $suplayerFood->vanue_id : null);

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
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'kode_suplayer.required' => 'Kode suplayer wajib diisi.',
            'kode_suplayer.unique' => 'Kode suplayer sudah digunakan.',
            'nama_suplayer.required' => 'Nama suplayer wajib diisi.',
            'alamat.required' => 'Alamat suplayer wajib diisi.',
            'nowa.required' => 'Nomor WhatsApp wajib diisi.',
            'email.required' => 'Email suplayer wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email suplayer sudah digunakan.',
            'password.required' => 'Password suplayer wajib diisi.',
            'tangggl_daftar.required' => 'Tanggal daftar wajib diisi.',
            'tangggl_daftar.date' => 'Tanggal daftar tidak valid.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
        ];
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
