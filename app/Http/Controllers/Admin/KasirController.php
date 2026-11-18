<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\Printer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'menus' => $this->daftarMenuPemesanan($vanueTerbatas ?? $vanueIdFilter),
            'mejas' => $this->daftarMeja($vanueTerbatas ?? $vanueIdFilter),
            'printers' => Printer::query()
                ->when($pesananVanueId !== null, fn ($query) => $query->where('vanue_id', $pesananVanueId))
                ->get(['id', 'vanue_id', 'nama', 'device_id']),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'vanue_id' => $vanueIdFilter,
            ],
        ]);
    }

    /**
     * Menu aktif untuk form tambah pesanan kasir, dikelompokkan per venue.
     */
    private function daftarMenuPemesanan(?int $vanueId): array
    {
        return Menu::query()
            ->with('kategori:id,kategori')
            ->where('status', 'aktif')
            ->when($vanueId !== null, fn ($query) => $query->where('vanue_id', $vanueId))
            ->orderBy('nama')
            ->get()
            ->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'vanue_id' => $menu->vanue_id,
                'nama' => $menu->nama,
                'kategori' => $menu->kategori?->kategori,
                'dibuat_di' => $menu->dibuat_di,
                'harga' => (int) $menu->harga,
                'harga_dingin' => $menu->harga_dingin !== null ? (int) $menu->harga_dingin : null,
                'harga_panas' => $menu->harga_panas !== null ? (int) $menu->harga_panas : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Daftar kode meja untuk form tambah pesanan kasir, dikelompokkan per venue.
     */
    private function daftarMeja(?int $vanueId): array
    {
        return Meja::query()
            ->when($vanueId !== null, fn ($query) => $query->where('vanue_id', $vanueId))
            ->orderBy('kode_meja')
            ->get()
            ->map(fn (Meja $meja) => [
                'vanue_id' => $meja->vanue_id,
                'kode_meja' => $meja->kode_meja,
                'nama_meja' => $meja->nama_meja,
            ])
            ->values()
            ->all();
    }

    /**
     * Buat pesanan manual oleh kasir (POS sederhana).
     */
    public function store(Request $request): RedirectResponse
    {
        $admin = $this->adminAktif($request);
        $vanueTerbatas = $this->vanueTerbatas($admin);

        $validated = $request->validate([
            'vanue_id' => ['nullable', 'integer', 'exists:vanues,id'],
            'tipe_pesanan' => ['required', Rule::in([Pesanan::TIPE_DINE_IN, Pesanan::TIPE_TAKE_AWAY])],
            'nomor_meja' => ['required', 'string', 'max:20'],
            'atas_nama' => ['required', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.suhu' => ['nullable', 'string', Rule::in(['Dingin', 'Panas'])],
        ], [
            'tipe_pesanan.required' => 'Tipe pesanan wajib dipilih.',
            'tipe_pesanan.in' => 'Tipe pesanan harus Dine In atau Take Away.',
            'nomor_meja.required' => 'Nomor meja wajib diisi.',
            'atas_nama.required' => 'Nama pemesan wajib diisi.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Pilih minimal satu menu.',
        ]);

        $vanueId = $vanueTerbatas ?? (int) ($validated['vanue_id'] ?? 0);

        if ($vanueId === 0) {
            throw ValidationException::withMessages([
                'vanue_id' => 'Pilih venue untuk pesanan ini.',
            ]);
        }

        $menus = Menu::query()
            ->where('vanue_id', $vanueId)
            ->where('status', 'aktif')
            ->whereIn('id', collect($validated['items'])->pluck('id'))
            ->get()
            ->keyBy('id');

        $rincian = [];
        $total = 0;

        foreach ($validated['items'] as $item) {
            $menu = $menus->get((int) $item['id']);

            if (! $menu) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu menu tidak tersedia pada venue ini.',
                ]);
            }

            $jumlah = (int) $item['jumlah'];
            $suhu = $item['suhu'] ?? null;
            $punyaVarianSuhu = $menu->harga_dingin !== null && $menu->harga_panas !== null;

            if ($punyaVarianSuhu) {
                if (! in_array($suhu, ['Dingin', 'Panas'], true)) {
                    throw ValidationException::withMessages([
                        'items' => "Pilih Dingin atau Panas untuk {$menu->nama}.",
                    ]);
                }

                $harga = (int) ($suhu === 'Panas' ? $menu->harga_panas : $menu->harga_dingin);
            } else {
                $suhu = null;
                $harga = (int) $menu->harga;
            }

            $subtotal = $harga * $jumlah;
            $total += $subtotal;

            $rincian[] = [
                'id_menu' => (string) $menu->id,
                'nama_menu' => $menu->nama,
                'suhu' => $suhu,
                'dibuat_di' => $menu->dibuat_di,
                'harga' => $harga,
                'jumlah' => $jumlah,
                'subtotal' => $subtotal,
            ];
        }

        $pesanan = DB::transaction(function () use ($validated, $rincian, $total): Pesanan {
            $pesanan = Pesanan::create([
                'kode_pesanan' => Pesanan::buatKodePesanan(),
                'id_pengguna' => null,
                'nomor_meja' => trim($validated['nomor_meja']),
                'tipe_pesanan' => $validated['tipe_pesanan'],
                'nama_pemesan' => trim($validated['atas_nama']),
                'catatan' => $validated['catatan'] ?? null,
                'total' => $total,
                'status' => Pesanan::STATUS_BARU,
            ]);

            $pesanan->details()->createMany($rincian);

            return $pesanan;
        });

        return back()->with('success', "Pesanan {$pesanan->kode_pesanan} berhasil dibuat oleh kasir.");
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
