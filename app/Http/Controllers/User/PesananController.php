<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PesananController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'atas_nama' => ['required', 'string', 'max:100'],
            'nomor_meja' => ['required', 'string', 'max:20'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.suhu' => ['nullable', 'string', Rule::in(['Dingin', 'Panas'])],
        ], [
            'atas_nama.required' => 'Nama pemesan wajib diisi.',
            'atas_nama.max' => 'Nama pemesan maksimal 100 karakter.',
            'nomor_meja.required' => 'Nomor meja wajib diisi.',
            'nomor_meja.max' => 'Nomor meja maksimal 20 karakter.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Pilih minimal satu menu.',
            'items.*.id.exists' => 'Menu tidak ditemukan.',
            'items.*.jumlah.min' => 'Jumlah menu minimal 1.',
            'items.*.jumlah.max' => 'Jumlah menu maksimal 99.',
            'items.*.suhu.in' => 'Pilihan suhu minuman tidak valid.',
        ]);

        $menus = Menu::query()
            ->whereIn('id', collect($validated['items'])->pluck('id'))
            ->where('status', 'aktif')
            ->get()
            ->keyBy('id');

        $rincian = [];
        $total = 0;

        foreach ($validated['items'] as $item) {
            $menu = $menus->get((int) $item['id']);

            if (! $menu) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu menu tidak tersedia atau sudah nonaktif.',
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

        $pengguna = $this->currentPengguna($request);

        $pesanan = DB::transaction(function () use ($validated, $rincian, $total, $pengguna): Pesanan {
            $pesanan = Pesanan::create([
                'kode_pesanan' => Pesanan::buatKodePesanan(),
                'id_pengguna' => $pengguna ? (string) $pengguna->id : null,
                'nomor_meja' => trim($validated['nomor_meja']),
                'nama_pemesan' => trim($validated['atas_nama']),
                'catatan' => $validated['catatan'] ?? null,
                'total' => $total,
                'status' => Pesanan::STATUS_BARU,
            ]);

            $pesanan->details()->createMany($rincian);

            return $pesanan;
        });

        return back()->with('success', "Pesanan {$pesanan->kode_pesanan} berhasil dikirim ke kasir.");
    }

    private function currentPengguna(Request $request): ?Pengguna
    {
        $id = $request->session()->get('pengguna_id');

        return $id ? Pengguna::query()->find($id) : null;
    }

    public function riwayat(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);
        $status = trim((string) $request->query('status', ''));
        $idPengguna = (string) $pengguna?->id;

        $pesanans = Pesanan::query()
            ->with(['details' => fn ($query) => $query->orderBy('id')])
            ->where('id_pengguna', $idPengguna)
            ->when(
                in_array($status, Pesanan::STATUS_VALID, true),
                fn ($query) => $query->where('status', $status),
            )
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = Pesanan::query()
            ->where('id_pengguna', $idPengguna)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return Inertia::render('User/RiwayatPesanan', [
            'pengguna' => $pengguna,
            'pesanans' => $pesanans,
            'ringkasan' => [
                'total' => (int) $ringkasan->sum(),
                'baru' => (int) ($ringkasan[Pesanan::STATUS_BARU] ?? 0),
                'diproses' => (int) ($ringkasan[Pesanan::STATUS_DIPROSES] ?? 0),
                'selesai' => (int) ($ringkasan[Pesanan::STATUS_SELESAI] ?? 0),
                'dibatalkan' => (int) ($ringkasan[Pesanan::STATUS_DIBATALKAN] ?? 0),
            ],
            'filters' => [
                'status' => $status,
            ],
        ]);
    }
}
