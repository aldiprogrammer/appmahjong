<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Settingbookingroom;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingBookingRoomController extends Controller
{
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $settingBookingRooms = Settingbookingroom::query()
            ->with('vanue:id,nama')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('pembayaran', 'like', "%{$search}%")
                        ->orWhere('jumlah_user', 'like', "%{$search}%")
                        ->orWhere('minimal_user', 'like', "%{$search}%")
                        ->orWhere('maksimal_user', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('vanue', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/SettingBookingRoom/Index', [
            'settingBookingRooms' => $settingBookingRooms,
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
            'pembayaran' => ['required', 'string', Rule::in(['all', 'one'])],
            'jumlah_user' => ['required', 'integer', 'min:1'],
            'minimal_user' => ['required', 'integer', 'min:1'],
            'maksimal_user' => ['required', 'integer', 'min:1', 'gte:minimal_user'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        Settingbookingroom::create($validated);

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil ditambahkan.');
    }

    public function update(Request $request, Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $validated = $request->validate([
            'vanue_id' => ['required', Rule::exists('vanues', 'id')],
            'pembayaran' => ['required', 'string', Rule::in(['all', 'one'])],
            'jumlah_user' => ['required', 'integer', 'min:1'],
            'minimal_user' => ['required', 'integer', 'min:1'],
            'maksimal_user' => ['required', 'integer', 'min:1', 'gte:minimal_user'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        $settingBookingRoom->update($validated);

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $settingBookingRoom->vanue_id);

        $settingBookingRoom->update([
            'status' => $settingBookingRoom->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('success', 'Status setting room booking berhasil diperbarui.');
    }

    public function destroy(Request $request, Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $settingBookingRoom->vanue_id);

        $settingBookingRoom->delete();

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil dihapus.');
    }
}
