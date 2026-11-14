<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VanueScope;
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
    use VanueScope;

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $vanueIdFilter = $this->vanueFilter($request);
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $mejas = Meja::query()
            ->with('vanue')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_meja', 'like', "%{$search}%")
                        ->orWhere('nama_meja', 'like', "%{$search}%");
                });
            })
            ->when($vanueIdFilter !== null, fn ($query) => $query->where('vanue_id', $vanueIdFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Meja/Index', [
            'mejas' => $mejas,
            'vanues' => $this->daftarVanueTersedia($request),
            'filters' => [
                'search' => $search,
                'vanue_id' => $vanueIdFilter,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        $validated['kode_meja'] = $this->buatKodeMeja();

        Meja::create($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil ditambahkan.');
    }

    public function update(Request $request, Meja $meja): RedirectResponse
    {
        $this->batasiAksesVanue($request, $meja->vanue_id !== null ? (int) $meja->vanue_id : null);

        $validated = $request->validate($this->aturanValidasi($meja), $this->pesanValidasi());

        $this->batasiAksesVanue($request, (int) $validated['vanue_id']);

        $meja->update($validated);

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil diperbarui.');
    }

    public function destroy(Request $request, Meja $meja): RedirectResponse
    {
        $this->batasiAksesVanue($request, $meja->vanue_id !== null ? (int) $meja->vanue_id : null);

        $meja->delete();

        return redirect()
            ->route('admin.mejas.index')
            ->with('success', 'Data meja berhasil dihapus.');
    }

    public function qrCodeImage(Request $request, Meja $meja): \Illuminate\Http\Response
    {
        $this->batasiAksesVanue($request, $meja->vanue_id !== null ? (int) $meja->vanue_id : null);

        return response($this->renderQrPng($meja->kode_meja), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function downloadQrCode(Request $request, Meja $meja): \Symfony\Component\HttpFoundation\Response
    {
        $this->batasiAksesVanue($request, $meja->vanue_id !== null ? (int) $meja->vanue_id : null);

        $pdf = Pdf::loadView('pdf.qr-meja', [
            'meja' => $meja,
            'qr' => $this->renderQrDataUri($meja->kode_meja),
        ])->setPaper([0, 0, 320, 420], 'portrait');

        return $pdf->download("qr-meja-{$meja->kode_meja}.pdf");
    }

    public function downloadAllQrCodes(Request $request): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $vanueTerbatas = $this->vanueTerbatas($this->adminAktif($request));

        $mejas = Meja::query()
            ->with('vanue')
            ->when($vanueTerbatas !== null, fn ($query) => $query->where('vanue_id', $vanueTerbatas))
            ->orderBy('nama_meja')
            ->get();

        if ($mejas->isEmpty()) {
            return redirect()
                ->route('admin.mejas.index')
                ->with('error', 'Belum ada data meja untuk dibuatkan QR code.');
        }

        $items = $mejas->map(fn (Meja $meja): array => [
            'kode_meja' => $meja->kode_meja,
            'nama_meja' => $meja->nama_meja,
            'vanue' => $meja->vanue?->nama,
            'qr' => $this->renderQrDataUri($meja->kode_meja),
        ]);

        $pdf = Pdf::loadView('pdf.qr-meja-semua', [
            'items' => $items,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('qr-code-semua-meja.pdf');
    }

    /**
     * Kode meja unik dibuat otomatis, format MJ-X9X9X9 (huruf dan angka berselang).
     */
    private function buatKodeMeja(): string
    {
        $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $angka = '0123456789';

        do {
            $acak = '';
            $posisiHuruf = 0;
            $posisiAngka = 0;

            for ($i = 0; $i < 6; $i++) {
                $acak .= $i % 2 === 0 ? $huruf[$posisiHuruf++ % 24] : $angka[$posisiAngka++ % 10];
            }

            // Acak urutan karakter agar kombinasi tiap meja bervariasi.
            $acak = str_shuffle($acak);
            $kode = 'MJ-'.$acak;
        } while (Meja::query()->where('kode_meja', $kode)->exists());

        return $kode;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function aturanValidasi(?Meja $meja = null): array
    {
        return [
            'kode_meja' => [
                // Kode dibuat otomatis saat tambah meja baru; saat edit boleh diisi manual.
                $meja === null ? 'nullable' : 'required',
                'string',
                'max:50',
                Rule::unique(Meja::class, 'kode_meja')->ignore($meja?->id),
            ],
            'nama_meja' => ['required', 'string', 'max:50'],
            'vanue_id' => ['required', 'integer', Rule::exists('vanues', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'kode_meja.required' => 'Kode meja wajib diisi.',
            'kode_meja.max' => 'Kode meja maksimal 50 karakter.',
            'kode_meja.unique' => 'Kode meja sudah digunakan, gunakan kode lain.',
            'nama_meja.required' => 'Nama meja wajib diisi.',
            'nama_meja.max' => 'Nama meja maksimal 50 karakter.',
            'vanue_id.required' => 'Venue wajib dipilih untuk setiap meja.',
            'vanue_id.integer' => 'Venue yang dipilih tidak valid.',
            'vanue_id.exists' => 'Venue yang dipilih tidak ditemukan.',
        ];
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
