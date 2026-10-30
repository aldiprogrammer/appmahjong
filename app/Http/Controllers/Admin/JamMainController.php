<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\jam_main;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JamMainController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $jamMains = jam_main::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('jam_mulai', 'like', "%{$search}%")
                        ->orWhere('jam_selesai', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/JamMain/Index', [
            'jamMains' => $jamMains,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        jam_main::create($validated);

        return redirect()
            ->route('admin.jam-mains.index')
            ->with('success', 'Data jam main berhasil ditambahkan.');
    }

    public function update(Request $request, jam_main $jamMain): RedirectResponse
    {
        $validated = $request->validate([
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $jamMain->update($validated);

        return redirect()
            ->route('admin.jam-mains.index')
            ->with('success', 'Data jam main berhasil diperbarui.');
    }

    public function destroy(jam_main $jamMain): RedirectResponse
    {
        $jamMain->delete();

        return redirect()
            ->route('admin.jam-mains.index')
            ->with('success', 'Data jam main berhasil dihapus.');
    }
}
