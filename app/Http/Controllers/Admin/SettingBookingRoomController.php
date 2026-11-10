<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settingbookingroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingBookingRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $settingBookingRooms = Settingbookingroom::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('pembayaran', 'like', "%{$search}%")
                        ->orWhere('jumlah_user', 'like', "%{$search}%")
                        ->orWhere('minimal_user', 'like', "%{$search}%")
                        ->orWhere('maksimal_user', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/SettingBookingRoom/Index', [
            'settingBookingRooms' => $settingBookingRooms,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pembayaran' => ['required', 'string', Rule::in(['all', 'one'])],
            'jumlah_user' => ['required', 'integer', 'min:1'],
            'minimal_user' => ['required', 'integer', 'min:1'],
            'maksimal_user' => ['required', 'integer', 'min:1', 'gte:minimal_user'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
        ]);

        Settingbookingroom::create($validated);

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil ditambahkan.');
    }

    public function update(Request $request, Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $validated = $request->validate([
            'pembayaran' => ['required', 'string', Rule::in(['all', 'one'])],
            'jumlah_user' => ['required', 'integer', 'min:1'],
            'minimal_user' => ['required', 'integer', 'min:1'],
            'maksimal_user' => ['required', 'integer', 'min:1', 'gte:minimal_user'],
            'status' => ['required', 'string', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $settingBookingRoom->update($validated);

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil diperbarui.');
    }

    public function updateStatus(Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $settingBookingRoom->update([
            'status' => $settingBookingRoom->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return back()->with('success', 'Status setting room booking berhasil diperbarui.');
    }

    public function destroy(Settingbookingroom $settingBookingRoom): RedirectResponse
    {
        $settingBookingRoom->delete();

        return redirect()
            ->route('admin.setting-booking-rooms.index')
            ->with('success', 'Data setting room booking berhasil dihapus.');
    }
}
