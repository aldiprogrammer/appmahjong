<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $penggunas = Pengguna::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_user', 'like', "%{$search}%")
                        ->orWhere('nama_depan', 'like', "%{$search}%")
                        ->orWhere('nama_balakang', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nowa', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['aktif', 'nonaktif'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Pengguna/Index', [
            'penggunas' => $penggunas,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function updateStatus(Request $request, Pengguna $pengguna): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $pengguna->update(['status' => $validated['status']]);

        return back()->with('success', "Status pengguna {$pengguna->nama_depan} berhasil diperbarui.");
    }

    public function destroy(Pengguna $pengguna): RedirectResponse
    {
        $pengguna->delete();

        return back()->with('success', "Data pengguna {$pengguna->nama_depan} berhasil dihapus.");
    }
}
