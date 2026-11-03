<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Statusvoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatusVoucherController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $statusVouchers = Statusvoucher::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('status_voucher', 'like', "%{$search}%")
                        ->orWhere('jumah_booking', 'like', "%{$search}%")
                        ->orWhere('jumah_jam', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StatusVoucher/Index', [
            'statusVouchers' => $statusVouchers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status_voucher' => ['required', 'string', 'max:50'],
            'jumah_booking' => ['required', 'string', 'max:50'],
            'jumah_jam' => ['required', 'string', 'max:50'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        Statusvoucher::create($validated);

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil ditambahkan.');
    }

    public function update(Request $request, Statusvoucher $statusVoucher): RedirectResponse
    {
        $validated = $request->validate([
            'status_voucher' => ['required', 'string', 'max:50'],
            'jumah_booking' => ['required', 'string', 'max:50'],
            'jumah_jam' => ['required', 'string', 'max:50'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $statusVoucher->update($validated);

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil diperbarui.');
    }

    public function destroy(Statusvoucher $statusVoucher): RedirectResponse
    {
        $statusVoucher->delete();

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil dihapus.');
    }
}
