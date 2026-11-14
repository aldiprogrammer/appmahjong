<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KasirController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));
        $vanueIdFilter = $this->vanueFilter($request);

        // Venue yang membatasi daftar: venue milik admin venue, atau pilihan
        // filter venue bagi super admin.
        $pesananVanueId = $vanueTerbatas ?? $vanueIdFilter;

        $pesanans = Pesanan::query()
            ->with('details')
            ->when($pesananVanueId !== null, fn ($query) => $this->batasiPesananVanue($query, $pesananVanueId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_pesanan', 'like', "%{$search}%")
                        ->orWhere('nomor_meja', 'like', "%{$search}%")
                        ->orWhere('nama_pemesan', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $ringkasan = [
            'baru' => $this->hitungPesanan($request, $pesananVanueId, Pesanan::STATUS_BARU),
            'diproses' => $this->hitungPesanan($request, $pesananVanueId, Pesanan::STATUS_DIPROSES),
            'selesai' => $this->hitungPesanan($request, $pesananVanueId, Pesanan::STATUS_SELESAI),
            'dibatalkan' => $this->hitungPesanan($request, $pesananVanueId, Pesanan::STATUS_DIBATALKAN),
        ];

        return Inertia::render('Admin/Kasir/Index', [
            'pesanans' => $pesanans,
            'ringkasan' => $ringkasan,
            'vanues' => $this->daftarVanueTersedia($request),
            'bolehPilihVanue' => $vanueTerbatas === null,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'vanue_id' => $vanueIdFilter,
            ],
        ]);
    }

    /**
     * Endpoint ringan untuk pengecekan pesanan baru oleh halaman kasir.
     */
    public function notifikasi(Request $request): JsonResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $pesananQuery = Pesanan::query()
            ->where('status', Pesanan::STATUS_BARU)
            ->when($vanueTerbatas !== null, fn ($query) => $this->batasiPesananVanue($query, $vanueTerbatas));

        $pesananBaru = (clone $pesananQuery)
            ->latest('id')
            ->take(10)
            ->get(['id', 'kode_pesanan', 'nomor_meja', 'tipe_pesanan', 'nama_pemesan']);

        return response()->json([
            'jumlah_baru' => $pesananQuery->count(),
            'pesanan' => $pesananBaru,
        ]);
    }

    /**
     * Batasi pesanan pada venue tertentu: dine in dicocokkan lewat nomor meja,
     * take away lewat menu yang dipesan.
     */
    private function batasiPesananVanue($query, int $vanueId): mixed
    {
        $kodeMejas = Meja::query()
            ->where('vanue_id', $vanueId)
            ->pluck('kode_meja')
            ->filter()
            ->values()
            ->all();

        $idMenus = Menu::query()
            ->where('vanue_id', $vanueId)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        return $query->where(function ($query) use ($kodeMejas, $idMenus) {
            $query
                ->when($kodeMejas !== [], fn ($q) => $q->whereIn('nomor_meja', $kodeMejas))
                ->when($idMenus !== [], fn ($q) => $q->orWhereHas('details', fn ($q) => $q->whereIn('id_menu', $idMenus)));
        });
    }

    private function hitungPesanan(Request $request, ?int $vanueId, string $status): int
    {
        $query = Pesanan::query()->where('status', $status);

        if ($vanueId !== null) {
            $this->batasiPesananVanue($query, $vanueId);
        }

        return $query->count();
    }

    public function updateStatus(Request $request, Pesanan $pesanan): RedirectResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    Pesanan::STATUS_BARU,
                    Pesanan::STATUS_DIPROSES,
                    Pesanan::STATUS_SELESAI,
                    Pesanan::STATUS_DIBATALKAN,
                ]),
            ],
        ], [
            'status.required' => 'Status pesanan wajib dipilih.',
            'status.in' => 'Status pesanan tidak valid.',
        ]);

        $pesanan->update(['status' => $validated['status']]);

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
