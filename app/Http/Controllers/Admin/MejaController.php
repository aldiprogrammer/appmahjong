<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meja;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MejaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $mejas = Meja::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_meja', 'like', "%{$search}%")
                        ->orWhere('nama_meja', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Meja/Index', [
            'mejas' => $mejas,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_meja' => ['required', 'string', 'max:50', Rule::unique(Meja::class, 'kode_meja')],
            'nama_meja' => ['required', 'string', 'max:50'],
        ]);

        Meja::create($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil ditambahkan.');
    }

    public function update(Request $request, Meja $meja): RedirectResponse
    {
        $validated = $request->validate([
            'kode_meja' => ['required', 'string', 'max:50', Rule::unique(Meja::class, 'kode_meja')->ignore($meja->id)],
            'nama_meja' => ['required', 'string', 'max:50'],
        ]);

        $meja->update($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil diperbarui.');
    }

    public function destroy(Meja $meja): RedirectResponse
    {
        $meja->delete();

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil dihapus.');
    }

    public function qrCodeImage(Meja $meja): \Illuminate\Http\Response
    {
        return response($this->renderQrPng($meja->kode_meja), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function downloadQrCode(Meja $meja): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = Pdf::loadView('pdf.qr-meja', [
            'meja' => $meja,
            'qr' => $this->renderQrDataUri($meja->kode_meja),
        ])->setPaper([0, 0, 320, 420], 'portrait');

        return $pdf->download("qr-meja-{$meja->kode_meja}.pdf");
    }

    public function downloadAllQrCodes(): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $mejas = Meja::query()->orderBy('nama_meja')->get();

        if ($mejas->isEmpty()) {
            return redirect()
                ->route('admin.mejas.index')
                ->with('error', 'Belum ada data meja untuk dibuatkan QR code.');
        }

        $items = $mejas->map(fn (Meja $meja): array => [
            'kode_meja' => $meja->kode_meja,
            'nama_meja' => $meja->nama_meja,
            'qr' => $this->renderQrDataUri($meja->kode_meja),
        ]);

        $pdf = Pdf::loadView('pdf.qr-meja-semua', [
            'items' => $items,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('qr-code-semua-meja.pdf');
    }

    /**
     * @return non-empty-string
     */
    private function renderQrDataUri(string $kodeMeja): string
    {
        return (new QRCode($this->qrOptions()))->render($kodeMeja);
    }

    private function renderQrPng(string $kodeMeja): string
    {
        return (new QRCode($this->qrOptions(['outputBase64' => false])))->render($kodeMeja);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function qrOptions(array $overrides = []): QROptions
    {
        return new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 8,
            'quietzoneSize' => 2,
            ...$overrides,
        ]);
    }
}
