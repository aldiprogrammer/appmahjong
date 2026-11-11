<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KasirController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $pesanans = Pesanan::query()
            ->with('details')
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
            'baru' => Pesanan::query()->where('status', Pesanan::STATUS_BARU)->count(),
            'diproses' => Pesanan::query()->where('status', Pesanan::STATUS_DIPROSES)->count(),
            'selesai' => Pesanan::query()->where('status', Pesanan::STATUS_SELESAI)->count(),
            'dibatalkan' => Pesanan::query()->where('status', Pesanan::STATUS_DIBATALKAN)->count(),
        ];

        return Inertia::render('Admin/Kasir/Index', [
            'pesanans' => $pesanans,
            'ringkasan' => $ringkasan,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Endpoint ringan untuk pengecekan pesanan baru oleh halaman kasir.
     */
    public function notifikasi(): JsonResponse
    {
        $pesananBaru = Pesanan::query()
            ->where('status', Pesanan::STATUS_BARU)
            ->latest('id')
            ->take(10)
            ->get(['id', 'kode_pesanan', 'nomor_meja', 'tipe_pesanan', 'nama_pemesan']);

        return response()->json([
            'jumlah_baru' => Pesanan::query()->where('status', Pesanan::STATUS_BARU)->count(),
            'pesanan' => $pesananBaru,
        ]);
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
