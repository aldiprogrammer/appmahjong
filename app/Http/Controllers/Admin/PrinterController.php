<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\Models\Vanue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class PrinterController extends Controller
{
    use VanueScope;

    /**
     * Halaman pengaturan printer struk Bluetooth per venue.
     *
     * Koneksi printer dilakukan langsung oleh browser (Web Bluetooth),
     * sehingga halaman ini mengelola daftar printer yang terdaftar per venue.
     */
    public function index(Request $request): Response
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        return inertia('Admin/Printer/Index', [
            'printers' => Printer::query()
                ->with('vanue:id,nama')
                ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
                ->latest('id')
                ->get(['id', 'vanue_id', 'nama', 'device_id']),
            'vanues' => $this->daftarVanueTersedia($request)
                ->map(fn (Vanue $vanue) => ['id' => $vanue->id, 'nama' => $vanue->nama])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vanue_id' => ['required', Rule::exists('vanues', 'id')],
            'nama' => ['nullable', 'string', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        Printer::create($validated);

        return back()->with('success', 'Printer berhasil didaftarkan ke venue.');
    }

    public function destroy(Request $request, Printer $printer): RedirectResponse
    {
        $this->batasiAksesVanue($request, (int) $printer->vanue_id);

        $printer->delete();

        return back()->with('success', 'Printer berhasil dihapus dari venue.');
    }
}
