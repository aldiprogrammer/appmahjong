<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MahjongScan;
use App\Models\Pengguna;
use App\Models\Rank;
use App\Services\GeminiMahjongDetector;
use App\Services\MahjongCombinationService;
use App\Services\OpenAiMahjongDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PointController extends Controller
{
    /**
     * Toleransi scan point setelah jam main berakhir.
     */
    private const TOLERANSI_SCAN_MENIT = 30;

    /**
     * Jadwal booking disimpan dalam waktu WIB.
     */
    private const ZONA_WAKTU = 'Asia/Jakarta';

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
            'chickenScoring' => 'Chow 1 point · Pung 2 point · Kong 3 point',
            'scoringModes' => [
                [
                    'value' => MahjongCombinationService::MODE_CHICKEN,
                    'label' => 'Chicken Hand',
                    'description' => 'Chow 1 point, Pung 2 point, Kong 3 point. Pair dan bunga tidak dihitung.',
                ],
                [
                    'value' => MahjongCombinationService::MODE_HK,
                    'label' => 'HK Klasik',
                    'description' => 'Penilaian Hong Kong klasik 3-14 faan. Faan menjadi point.',
                ],
            ],
            'hkMinFaan' => MahjongCombinationService::HK_MIN_FAAN,
            'hkMaxFaan' => MahjongCombinationService::HK_MAX_FAAN,
        ]);
    }

    public function scan(Request $request): RedirectResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $request->validate([
            'kode_booking' => ['required', 'string', 'max:50'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mode' => ['nullable', 'string', 'in:chicken,hk'],
            'seat_wind' => ['required', 'string', 'in:z1,z2,z3,z4'],
            'round_wind' => ['nullable', 'string', 'in:z1,z2,z3,z4'],
            'self_draw' => ['nullable', 'boolean'],
            'concealed' => ['nullable', 'boolean'],
            'no_flowers' => ['nullable', 'boolean'],
            'last_tile' => ['nullable', 'boolean'],
            'robbing_kong' => ['nullable', 'boolean'],
            'kong_replacement' => ['nullable', 'boolean'],
        ], [
            'kode_booking.required' => 'Kode booking wajib diisi sebelum scan point.',
            'image.required' => 'Foto susunan mahjong wajib diambil.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran foto maksimal 5 MB.',
            'mode.in' => 'Mode penilaian tidak dikenal.',
            'seat_wind.required' => 'Pilih posisi duduk dulu sebelum scan susunan mahjong.',
            'seat_wind.in' => 'Posisi duduk tidak dikenal.',
            'round_wind.in' => 'Angin meja tidak dikenal.',
        ]);

        $pemeriksaanKode = $this->periksaKodeBooking((string) $request->input('kode_booking'));

        if (! $pemeriksaanKode['valid']) {
            return back()
                ->withErrors(['kode_booking' => $pemeriksaanKode['message']])
                ->withInput();
        }

        $mode = $request->input('mode') === MahjongCombinationService::MODE_HK
            ? MahjongCombinationService::MODE_HK
            : MahjongCombinationService::MODE_CHICKEN;

        $opsi = $this->opsiHk($request);

        $path = $request->file('image')->store('mahjong-scans', 'public');

        MahjongScan::query()
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status', 'pending')
            ->update(['status' => 'discarded']);

        $absolutePath = Storage::disk('public')->path($path);

        // Deteksi AI bisa memakan waktu; jangan biarkan PHP memutus request lebih dulu.
        @set_time_limit(0);

        $detector = $this->detektor();
        $tiles = $detector->deteksi($absolutePath);
        $analisa = app(MahjongCombinationService::class)->analisa($tiles, $mode, $opsi);

        MahjongScan::create([
            'id_pengguna' => (string) $pengguna->id,
            'image_path' => $path,
            'tiles' => $analisa['tiles'],
            'combinations' => $analisa['combinations'],
            'unmatched' => $analisa['unmatched'],
            'message' => $analisa['message'],
            'is_winning_hand' => $analisa['is_winning_hand'],
            'points' => $analisa['points'],
            'scoring_mode' => $analisa['scoring_mode'],
            'faan' => $analisa['faan'],
            'faan_details' => $analisa['faan_details'],
            'seat_wind' => $opsi['seat_wind'],
            'round_wind' => $opsi['round_wind'],
            'is_self_draw' => $opsi['self_draw'],
            'is_concealed' => $opsi['concealed'],
            'is_no_flowers' => $opsi['no_flowers'],
            'is_last_tile' => $opsi['last_tile'],
            'is_robbing_kong' => $opsi['robbing_kong'],
            'is_kong_replacement' => $opsi['kong_replacement'],
            'status' => 'pending',
        ]);

        $pesan = $tiles === []
            ? ($detector->pesan ?? 'Foto berhasil diambil. Deteksi otomatis belum tersedia, silakan tambahkan kartu secara manual.')
            : 'Foto berhasil dipindai. Periksa hasil deteksi sebelum konfirmasi.';

        return redirect()->route('point')->with('success', $pesan);
    }

    /**
     * Periksa kode booking sebelum kamera dibuka. Dipakai halaman point lewat fetch.
     */
    public function cekBooking(Request $request): JsonResponse
    {
        $pengguna = $this->currentPengguna($request);

        if (! $pengguna) {
            return response()->json([
                'valid' => false,
                'message' => 'Sesi login berakhir. Silakan masuk kembali.',
            ], 401);
        }

        $validated = $request->validate([
            'kode_booking' => ['required', 'string', 'max:50'],
        ], [
            'kode_booking.required' => 'Kode booking wajib diisi sebelum scan point.',
        ]);

        $hasil = $this->periksaKodeBooking($validated['kode_booking']);

        return response()->json($hasil, $hasil['valid'] ? 200 : 422);
    }

    /**
     * Kode booking harus ada, belum lewat harinya, dan masih dalam masa
     * toleransi 30 menit setelah jam main berakhir. Kode boleh dimasukkan
     * oleh pengguna lain (misalnya rekan satu meja) selama jadwalnya valid.
     *
     * @return array{valid: bool, message: string}
     */
    private function periksaKodeBooking(string $kodeBooking): array
    {
        $kodeBooking = trim($kodeBooking);

        $booking = Booking::query()
            ->where('kode_booking', $kodeBooking)
            ->where('status_hapus', '!=', '1')
            ->latest('id')
            ->first();

        if ($booking === null) {
            return [
                'valid' => false,
                'message' => 'Kode booking tidak ditemukan.',
            ];
        }

        try {
            $sekarang = Carbon::now(self::ZONA_WAKTU);
            $tanggalMain = Carbon::parse((string) $booking->tgl_main, self::ZONA_WAKTU)->startOfDay();
            $jamSelesai = substr((string) $booking->jam_selesai, 0, 5);
            $batasScan = Carbon::parse($tanggalMain->toDateString().' '.$jamSelesai, self::ZONA_WAKTU)
                ->addMinutes(self::TOLERANSI_SCAN_MENIT);
        } catch (\Throwable) {
            return [
                'valid' => false,
                'message' => 'Jadwal booking tidak valid. Hubungi admin untuk bantuan.',
            ];
        }

        $hariIni = $sekarang->copy()->startOfDay();

        if ($tanggalMain->lt($hariIni)) {
            return [
                'valid' => false,
                'message' => 'Tanggal booking sudah lewat, scan point tidak dapat dilakukan.',
            ];
        }

        if ($tanggalMain->gt($hariIni)) {
            return [
                'valid' => false,
                'message' => 'Scan point baru bisa dilakukan pada hari booking, yaitu '.$tanggalMain->translatedFormat('d F Y').' (WIB).',
            ];
        }

        if ($sekarang->greaterThan($batasScan)) {
            return [
                'valid' => false,
                'message' => 'Waktu main sudah lewat lebih dari '.self::TOLERANSI_SCAN_MENIT.' menit, scan point tidak dapat dilakukan.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Kode booking valid. Silakan lanjutkan scan.',
        ];
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
            'mode' => ['nullable', 'string', 'in:chicken,hk'],
            'seat_wind' => ['required', 'string', 'in:z1,z2,z3,z4'],
            'round_wind' => ['nullable', 'string', 'in:z1,z2,z3,z4'],
            'self_draw' => ['nullable', 'boolean'],
            'concealed' => ['nullable', 'boolean'],
            'no_flowers' => ['nullable', 'boolean'],
            'last_tile' => ['nullable', 'boolean'],
            'robbing_kong' => ['nullable', 'boolean'],
            'kong_replacement' => ['nullable', 'boolean'],
        ]);

        $mode = $validated['mode']
            ?? $scan->scoring_mode
            ?? MahjongCombinationService::MODE_CHICKEN;

        $opsi = [
            'seat_wind' => $validated['seat_wind'] ?? $scan->seat_wind,
            'round_wind' => $validated['round_wind'] ?? $scan->round_wind,
            'self_draw' => array_key_exists('self_draw', $validated)
                ? (bool) $validated['self_draw']
                : (bool) $scan->is_self_draw,
            'concealed' => array_key_exists('concealed', $validated)
                ? (bool) $validated['concealed']
                : (bool) $scan->is_concealed,
            'no_flowers' => array_key_exists('no_flowers', $validated)
                ? (bool) $validated['no_flowers']
                : (bool) $scan->is_no_flowers,
            'last_tile' => array_key_exists('last_tile', $validated)
                ? (bool) $validated['last_tile']
                : (bool) $scan->is_last_tile,
            'robbing_kong' => array_key_exists('robbing_kong', $validated)
                ? (bool) $validated['robbing_kong']
                : (bool) $scan->is_robbing_kong,
            'kong_replacement' => array_key_exists('kong_replacement', $validated)
                ? (bool) $validated['kong_replacement']
                : (bool) $scan->is_kong_replacement,
        ];

        $analisa = app(MahjongCombinationService::class)->analisa($validated['tiles'] ?? [], $mode, $opsi);

        $scan->update([
            'tiles' => $analisa['tiles'],
            'combinations' => $analisa['combinations'],
            'unmatched' => $analisa['unmatched'],
            'message' => $analisa['message'],
            'is_winning_hand' => $analisa['is_winning_hand'],
            'points' => $analisa['points'],
            'scoring_mode' => $analisa['scoring_mode'],
            'faan' => $analisa['faan'],
            'faan_details' => $analisa['faan_details'],
            'seat_wind' => $opsi['seat_wind'],
            'round_wind' => $opsi['round_wind'],
            'is_self_draw' => $opsi['self_draw'],
            'is_concealed' => $opsi['concealed'],
            'is_no_flowers' => $opsi['no_flowers'],
            'is_last_tile' => $opsi['last_tile'],
            'is_robbing_kong' => $opsi['robbing_kong'],
            'is_kong_replacement' => $opsi['kong_replacement'],
        ]);

        if (! $analisa['is_winning_hand']) {
            return back()->with('success', 'Kartu diperbarui. '.$analisa['message']);
        }

        return back()->with('success', $analisa['scoring_mode'] === MahjongCombinationService::MODE_HK
            ? 'Winning hand HK terdeteksi: '.$analisa['faan'].' faan. Konfirmasi untuk mengklaim point.'
            : 'Winning hand terdeteksi. Konfirmasi untuk mengklaim point.');
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
     * @return array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}
     */
    private function opsiHk(Request $request): array
    {
        return [
            'seat_wind' => $request->input('seat_wind') ?: null,
            'round_wind' => $request->input('round_wind') ?: null,
            'self_draw' => $request->boolean('self_draw'),
            'concealed' => $request->boolean('concealed'),
            'no_flowers' => $request->boolean('no_flowers'),
            'last_tile' => $request->boolean('last_tile'),
            'robbing_kong' => $request->boolean('robbing_kong'),
            'kong_replacement' => $request->boolean('kong_replacement'),
        ];
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
            'flowers' => array_values(array_filter(
                $scan->tiles ?? [],
                fn (string $tile): bool => str_starts_with($tile, 'f'),
            )),
            'combinations' => $scan->combinations ?? [],
            'unmatched' => $scan->unmatched ?? [],
            'message' => $scan->message,
            'is_winning_hand' => $scan->is_winning_hand,
            'points' => $scan->points,
            'scoring_mode' => $scan->scoring_mode ?? MahjongCombinationService::MODE_CHICKEN,
            'faan' => $scan->faan,
            'faan_details' => $scan->faan_details ?? [],
            'seat_wind' => $scan->seat_wind,
            'round_wind' => $scan->round_wind,
            'is_self_draw' => (bool) $scan->is_self_draw,
            'is_concealed' => (bool) $scan->is_concealed,
            'is_no_flowers' => (bool) $scan->is_no_flowers,
            'is_last_tile' => (bool) $scan->is_last_tile,
            'is_robbing_kong' => (bool) $scan->is_robbing_kong,
            'is_kong_replacement' => (bool) $scan->is_kong_replacement,
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
            'scoring_mode' => $scan->scoring_mode ?? MahjongCombinationService::MODE_CHICKEN,
            'faan' => $scan->faan,
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
