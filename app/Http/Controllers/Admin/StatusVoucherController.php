<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Statusvoucher;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StatusVoucherController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $statusVouchers = Statusvoucher::query()
            ->with('vanue:id,nama')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('status_voucher', 'like', "%{$search}%")
                        ->orWhere('jumah_booking', 'like', "%{$search}%")
                        ->orWhere('jumah_jam', 'like', "%{$search}%")
                        ->orWhereHas('vanue', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/StatusVoucher/Index', [
            'statusVouchers' => $statusVouchers,
            'vanues' => $this->daftarVanueTersedia($request)
                ->map(fn (Vanue $vanue) => ['id' => $vanue->id, 'nama' => $vanue->nama])
                ->values(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vanue_id' => ['required', Rule::exists('vanues', 'id')],
            'status_voucher' => ['required', 'string', 'max:50'],
            'jumah_booking' => ['required', 'string', 'max:50'],
            'jumah_jam' => ['required', 'string', 'max:50'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        Statusvoucher::create($validated);

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil ditambahkan.');
    }

    public function update(Request $request, Statusvoucher $statusVoucher): RedirectResponse
    {
        $validated = $request->validate([
            'vanue_id' => ['required', Rule::exists('vanues', 'id')],
            'status_voucher' => ['required', 'string', 'max:50'],
            'jumah_booking' => ['required', 'string', 'max:50'],
            'jumah_jam' => ['required', 'string', 'max:50'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        $statusVoucher->update($validated);

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil diperbarui.');
    }

    public function destroy(Request $request, Statusvoucher $statusVoucher): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $statusVoucher->vanue_id);

        $statusVoucher->delete();

        return redirect()
            ->route('admin.status-vouchers.index')
            ->with('success', 'Data status voucher berhasil dihapus.');
    }
}
