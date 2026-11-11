<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MahjongScan;
use App\Models\Pengguna;
use App\Models\Rank;
use App\Services\GeminiMahjongDetector;
use App\Services\MahjongCombinationService;
use App\Services\OpenAiMahjongDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PointController extends Controller
{
    public function index(Request $request): Response
    {
        $pengguna = $this->currentPengguna($request);

        $pendingScan = $pengguna
            ? MahjongScan::query()
                ->where('id_pengguna', (string) $pengguna->id)
                ->where('status', 'pending')
                ->latest()
                ->first()
            : null;

        $riwayat = $pengguna
            ? MahjongScan::query()
                ->where('id_pengguna', (string) $pengguna->id)
                ->where('status', 'confirmed')
                ->latest('confirmed_at')
                ->limit(30)
                ->get()
                ->map(fn (MahjongScan $scan): array => $this->payloadRiwayat($scan))
            : collect();

        return Inertia::render('User/Point', [
            'pengguna' => $pengguna,
            'points' => (int) ($pengguna?->points ?? 0),
            'pendingScan' => $pendingScan ? $this->payloadScan($pendingScan) : null,
            'riwayat' => $riwayat,
            'kartu' => app(MahjongCombinationService::class)->daftarKartu(),
            'pointPerHand' => MahjongCombinationService::POINT_PER_HAND,
        ]);
    }

    public function scan(Request $request): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.required' => 'Foto susunan mahjong wajib diambil.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $path = $request->file('image')->store('mahjong-scans', 'public');

        MahjongScan::query()
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status', 'pending')
            ->update(['status' => 'discarded']);

        $absolutePath = Storage::disk('public')->path($path);
        $detector = $this->detektor();
        $tiles = $detector->deteksi($absolutePath);
        $analisa = app(MahjongCombinationService::class)->analisa($tiles);

        MahjongScan::create([
            'id_pengguna' => (string) $pengguna->id,
            'image_path' => $path,
            'tiles' => $analisa['tiles'],
            'combinations' => $analisa['combinations'],
            'unmatched' => $analisa['unmatched'],
            'message' => $analisa['message'],
            'is_winning_hand' => $analisa['is_winning_hand'],
            'points' => $analisa['points'],
            'status' => 'pending',
        ]);

        $pesan = $tiles === []
            ? ($detector->pesan ?? 'Foto berhasil diambil. Deteksi otomatis belum tersedia, silakan tambahkan kartu secara manual.')
            : 'Foto berhasil dipindai. Periksa hasil deteksi sebelum konfirmasi.';

        return redirect()->route('point')->with('success', $pesan);
    }

    public function perbaruiKartu(Request $request, MahjongScan $scan): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        $this->pastikanMilik($pengguna, $scan);

        if ($scan->status !== 'pending') {
            return back()->with('error', 'Scan ini sudah diproses dan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'tiles' => ['array'],
            'tiles.*' => ['string'],
        ]);

        $analisa = app(MahjongCombinationService::class)->analisa($validated['tiles'] ?? []);

        $scan->update([
            'tiles' => $analisa['tiles'],
            'combinations' => $analisa['combinations'],
            'unmatched' => $analisa['unmatched'],
            'message' => $analisa['message'],
            'is_winning_hand' => $analisa['is_winning_hand'],
            'points' => $analisa['points'],
        ]);

        return back()->with('success', $analisa['is_winning_hand']
            ? 'Winning hand terdeteksi. Konfirmasi untuk mengklaim point.'
            : 'Kartu diperbarui. Susunan belum membentuk winning hand.');
    }

    public function konfirmasi(Request $request, MahjongScan $scan): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        $this->pastikanMilik($pengguna, $scan);

        $ditolak = DB::transaction(function () use ($scan, $pengguna): ?string {
            $fresh = MahjongScan::query()->whereKey($scan->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== 'pending') {
                return 'Scan ini sudah diproses sebelumnya.';
            }

            if (! $fresh->is_winning_hand) {
                return 'Susunan belum membentuk winning hand, point tidak dapat diklaim.';
            }

            $fresh->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            Pengguna::query()
                ->whereKey($pengguna->id)
                ->increment('points', $fresh->points);

            Rank::create([
                'kode_user' => $pengguna->kode_user,
                'point' => (string) $fresh->points,
            ]);

            return null;
        });

        if ($ditolak !== null) {
            return back()->with('error', $ditolak);
        }

        return redirect()->route('point')->with(
            'success',
            'Point berhasil ditambahkan: +'.$scan->points.' point.',
        );
    }

    public function batalkan(Request $request, MahjongScan $scan): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        $this->pastikanMilik($pengguna, $scan);

        if ($scan->status === 'pending') {
            $scan->update(['status' => 'discarded']);
        }

        return back()->with('success', 'Scan dibatalkan. Point tidak ditambahkan.');
    }

    /**
     * Pilih layanan deteksi: Gemini dipakai bila GEMINI_API_KEY tersedia,
     * jika tidak fallback ke OpenAI Vision.
     */
    private function detektor(): GeminiMahjongDetector|OpenAiMahjongDetector
    {
        $gemini = app(GeminiMahjongDetector::class);

        return $gemini->tersedia() ? $gemini : app(OpenAiMahjongDetector::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadScan(MahjongScan $scan): array
    {
        return [
            'id' => $scan->id,
            'image_url' => MahjongScan::imageUrl($scan->image_path),
            'tiles' => $scan->tiles ?? [],
            'combinations' => $scan->combinations ?? [],
            'unmatched' => $scan->unmatched ?? [],
            'message' => $scan->message,
            'is_winning_hand' => $scan->is_winning_hand,
            'points' => $scan->points,
            'status' => $scan->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadRiwayat(MahjongScan $scan): array
    {
        return [
            'id' => $scan->id,
            'image_url' => MahjongScan::imageUrl($scan->image_path),
            'tiles' => $scan->tiles ?? [],
            'combinations' => $scan->combinations ?? [],
            'points' => $scan->points,
            'confirmed_at' => $scan->confirmed_at?->toIso8601String(),
        ];
    }

    private function pastikanMilik(?Pengguna $pengguna, MahjongScan $scan): void
    {
        if (! $pengguna || (string) $scan->id_pengguna !== (string) $pengguna->id) {
            abort(404);
        }
    }

    private function currentPengguna(Request $request): ?Pengguna
    {
        $id = $request->session()->get('pengguna_id');

        return $id ? Pengguna::query()->find($id) : null;
    }
}
