<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MahjongScan;
use App\Models\Pengguna;
use App\Models\Rank;
use App\Services\AturanFaanService;
use App\Services\GeminiMahjongDetector;
use App\Services\MahjongCombinationService;
use App\Services\MahjongScanImageService;
use App\Services\OpenAiMahjongDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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

    public function __construct(
        private readonly MahjongScanImageService $gambarScan,
        private readonly MahjongCombinationService $kombinasi,
        private readonly AturanFaanService $aturanFaan,
        private readonly GeminiMahjongDetector $gemini,
        private readonly OpenAiMahjongDetector $openAi,
    ) {}

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

        $faan = $this->faanValues();
        $minimalFaan = $this->aturanFaan->minimalFaan();
        $batasFaan = $this->aturanFaan->batasFaan();

        return Inertia::render('User/Point', [
            'pengguna' => $pengguna,
            'points' => (int) ($pengguna?->points ?? 0),
            'pendingScan' => $pendingScan ? $this->payloadScan($pendingScan) : null,
            'riwayat' => $riwayat,
            'kartu' => $this->kombinasi->daftarKartu(),
            'chickenScoring' => $this->ringkasanChicken($faan),
            'scoringModes' => [
                [
                    'value' => MahjongCombinationService::MODE_CHICKEN,
                    'label' => 'Chicken Hand',
                    'description' => sprintf(
                        'Chow %d point, Pung %d point, Kong %d point. Own Flowers +%d point, Own Season +%d point.',
                        $faan['chow'],
                        $faan['pung'],
                        $faan['kong'],
                        $faan['ownFlowers'],
                        $faan['ownSeason'],
                    ),
                ],
                [
                    'value' => MahjongCombinationService::MODE_HK,
                    'label' => 'HK Klasik',
                    'description' => 'Penilaian Hong Kong klasik '.$minimalFaan.'-'.$batasFaan.' faan. Faan menjadi point.',
                ],
            ],
            'hkMinFaan' => $minimalFaan,
            'hkMaxFaan' => $batasFaan,
            'faanValues' => $faan,
            'aturanPoint' => [
                'tersedia' => $this->aturanFaan->terkonfigurasi(),
                'nama' => $this->aturanFaan->namaAturan(),
            ],
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp'],
            'mode' => ['nullable', 'string', 'in:chicken,hk'],
            'seat_wind' => ['required', 'string', 'in:z1,z2,z3,z4'],
            'round_wind' => ['nullable', 'string', 'in:z1,z2,z3,z4'],
            'self_draw' => ['nullable', 'boolean'],
            'concealed' => ['nullable', 'boolean'],
            'winning_tile' => $this->aturanWinningTile(),
            'no_flowers' => ['nullable', 'boolean'],
            'last_tile' => ['nullable', 'boolean'],
            'robbing_kong' => ['nullable', 'boolean'],
            'kong_replacement' => ['nullable', 'boolean'],
        ], [
            'kode_booking.required' => 'Kode booking wajib diisi sebelum scan point.',
            'image.required' => 'Foto susunan mahjong wajib diambil.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
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

        if (! $this->aturanFaan->terkonfigurasi()) {
            return back()
                ->withInput($request->except('image'))
                ->with('error', $this->pesanAturanPoint());
        }

        $mode = $request->input('mode') === MahjongCombinationService::MODE_HK
            ? MahjongCombinationService::MODE_HK
            : MahjongCombinationService::MODE_CHICKEN;

        $opsi = $this->opsiHk($request);

        try {
            $urlGambar = $this->gambarScan->simpan($request->file('image'));
        } catch (Throwable) {
            return back()
                ->withInput($request->except('image'))
                ->with('error', 'Gagal mengunggah foto scan ke Cloudinary. Silakan coba lagi.');
        }

        MahjongScan::query()
            ->where('id_pengguna', (string) $pengguna->id)
            ->where('status', 'pending')
            ->update(['status' => 'discarded']);

        // Deteksi AI memakai file temporer hasil upload, bukan storage Laravel.
        // Deteksi bisa memakan waktu; jangan biarkan PHP memutus request lebih dulu.
        @set_time_limit(0);

        $detector = $this->detektor();
        $tiles = $detector->deteksi((string) $request->file('image')->getRealPath());
        $analisa = $this->kombinasi->analisa($tiles, $mode, $opsi);

        MahjongScan::create([
            'id_pengguna' => (string) $pengguna->id,
            'image_path' => $urlGambar,
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
            'winning_tile' => $opsi['winning_tile'],
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
     * Kode booking harus ada, belum lewat harinya, sudah memasuki jam main
     * pertama, dan masih dalam masa toleransi 30 menit setelah jam main
     * berakhir. Kode boleh dimasukkan oleh pengguna lain (misalnya rekan satu
     * meja) selama jadwalnya valid.
     *
     * @return array{valid: bool, message: string}
     */
    private function periksaKodeBooking(string $kodeBooking): array
    {
        $kodeBooking = trim($kodeBooking);

        $bookings = Booking::query()
            ->where('kode_booking', $kodeBooking)
            ->where('status_hapus', '!=', '1')
            ->get();

        if ($bookings->isEmpty()) {
            return [
                'valid' => false,
                'message' => 'Kode booking tidak ditemukan.',
            ];
        }

        // Satu kode booking dipakai bersama oleh semua sesi (mis. 18-19 dan
        // 19-20). Ambil sesi dengan waktu selesai paling akhir agar kode tetap
        // valid selama seluruh rangkaian sesi main, bukan hanya sesi terakhir
        // yang tersimpan.
        $booking = $bookings
            ->sortByDesc(fn (Booking $item): string => substr((string) $item->tgl_main, 0, 10).' '.substr((string) $item->jam_selesai, 0, 5))
            ->first();

        try {
            $sekarang = Carbon::now(self::ZONA_WAKTU);
            $tanggalMain = Carbon::parse((string) $booking->tgl_main, self::ZONA_WAKTU)->startOfDay();
            $jamSelesai = substr((string) $booking->jam_selesai, 0, 5);
            $batasScan = Carbon::parse($tanggalMain->toDateString().' '.$jamSelesai, self::ZONA_WAKTU)
                ->addMinutes(self::TOLERANSI_SCAN_MENIT);

            // Scan hanya boleh dimulai sejak sesi paling awal, bukan sebelum
            // jam main dimulai.
            $sesiAwal = $bookings
                ->sortBy(fn (Booking $item): string => substr((string) $item->tgl_main, 0, 10).' '.substr((string) $item->jam_mulai, 0, 5))
                ->first();
            $jamMulai = substr((string) $sesiAwal->jam_mulai, 0, 5);
            $mulaiScan = Carbon::parse($tanggalMain->toDateString().' '.$jamMulai, self::ZONA_WAKTU);
        } catch (Throwable) {
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

        if ($sekarang->lessThan($mulaiScan)) {
            return [
                'valid' => false,
                'message' => 'Scan point baru bisa dilakukan mulai jam '.$jamMulai.' WIB sesuai jadwal booking.',
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

        if (! $this->aturanFaan->terkonfigurasi()) {
            return back()->with('error', $this->pesanAturanPoint());
        }

        $validated = $this->validasiPermintaanKartu($request);

        $mode = $validated['mode']
            ?? $scan->scoring_mode
            ?? MahjongCombinationService::MODE_CHICKEN;

        $opsi = $this->opsiPermintaanKartu($validated, $scan);

        $analisa = $this->kombinasi->analisa($validated['tiles'] ?? [], $mode, $opsi);

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
            'winning_tile' => $opsi['winning_tile'],
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

    /**
     * Bandingkan skor Concealed dan Exposed sebelum menanyakan pilihan bukaan
     * ke pemain. Mengikuti perilaku Tilerank: pertanyaan hanya muncul bila
     * jawabannya benar-benar mengubah skor, dan tidak muncul untuk tangan yang
     * selalu tertutup seperti Seven Pairs / Thirteen Orphans.
     */
    public function bandingkanBukaan(Request $request, MahjongScan $scan): JsonResponse
    {
        $pengguna = $this->currentPengguna($request);

        $this->pastikanMilik($pengguna, $scan);

        if ($scan->status !== 'pending') {
            return response()->json([
                'message' => 'Scan ini sudah diproses dan tidak dapat diubah.',
            ], 422);
        }

        if (! $this->aturanFaan->terkonfigurasi()) {
            return response()->json(['message' => $this->pesanAturanPoint()], 422);
        }

        $validated = $this->validasiPermintaanKartu($request);
        $tiles = $validated['tiles'] ?? $scan->tiles ?? [];
        $mode = $validated['mode']
            ?? $scan->scoring_mode
            ?? MahjongCombinationService::MODE_CHICKEN;
        $opsi = $this->opsiPermintaanKartu($validated, $scan);
        $tertutupDasar = $this->kombinasi->analisa($tiles, $mode, [...$opsi, 'concealed' => true]);
        $terbukaDasar = $this->kombinasi->analisa($tiles, $mode, [...$opsi, 'concealed' => false]);

        if (! $tertutupDasar['is_winning_hand'] && ! $terbukaDasar['is_winning_hand']) {
            return response()->json([
                'perlu_ditanya' => false,
                'paksa_tertutup' => false,
                'alasan' => 'belum_valid',
                'bantuan' => 'Lengkapi tangan menang dulu untuk melihat skor.',
            ]);
        }

        if ($this->kombinasi->selaluTertutup($tiles)) {
            return response()->json([
                'perlu_ditanya' => false,
                'paksa_tertutup' => true,
                'alasan' => 'selalu_tertutup',
                'bantuan' => 'Tujuh Pasang / Thirteen Orphans selalu semua di tangan.',
            ]);
        }

        $perluDitanya = false;

        foreach ($this->kandidatUbinMenang($tiles) as $ubin) {
            $opsiDenganUbin = [...$opsi, 'winning_tile' => $ubin];
            $tertutup = $this->kombinasi->analisa($tiles, $mode, [...$opsiDenganUbin, 'concealed' => true]);
            $terbuka = $this->kombinasi->analisa($tiles, $mode, [...$opsiDenganUbin, 'concealed' => false]);

            if ($this->tandaTanganSkor($tertutup) !== $this->tandaTanganSkor($terbuka)) {
                $perluDitanya = true;

                break;
            }
        }

        return response()->json([
            'perlu_ditanya' => $perluDitanya,
            'paksa_tertutup' => false,
            'alasan' => $perluDitanya ? 'foto' : 'sama',
            'bantuan' => $perluDitanya
                ? 'Foto tidak bisa membedakan, dan jawabannya mengubah skor.'
                : 'Skornya sama. Kalau Concealed, langsung lanjut saja.',
        ]);
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
     * Ubin kemenangan hanya boleh memakai kode kartu yang dikenal aplikasi.
     *
     * @return array<int, mixed>
     */
    private function aturanWinningTile(): array
    {
        return ['nullable', 'string', Rule::in(array_column($this->kombinasi->daftarKartu(), 'code'))];
    }

    private function winningTile(mixed $nilai): ?string
    {
        if (! is_string($nilai) || $nilai === '') {
            return null;
        }

        return $this->kombinasi->normalisasiKode($nilai);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasiPermintaanKartu(Request $request): array
    {
        return $request->validate([
            'tiles' => ['array'],
            'tiles.*' => ['string'],
            'mode' => ['nullable', 'string', 'in:chicken,hk'],
            'seat_wind' => ['required', 'string', 'in:z1,z2,z3,z4'],
            'round_wind' => ['nullable', 'string', 'in:z1,z2,z3,z4'],
            'self_draw' => ['nullable', 'boolean'],
            'concealed' => ['nullable', 'boolean'],
            'winning_tile' => $this->aturanWinningTile(),
            'no_flowers' => ['nullable', 'boolean'],
            'last_tile' => ['nullable', 'boolean'],
            'robbing_kong' => ['nullable', 'boolean'],
            'kong_replacement' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}
     */
    private function opsiPermintaanKartu(array $validated, MahjongScan $scan): array
    {
        return [
            'seat_wind' => $validated['seat_wind'] ?? $scan->seat_wind,
            'round_wind' => $validated['round_wind'] ?? $scan->round_wind,
            'self_draw' => array_key_exists('self_draw', $validated)
                ? (bool) $validated['self_draw']
                : (bool) $scan->is_self_draw,
            'concealed' => array_key_exists('concealed', $validated)
                ? (bool) $validated['concealed']
                : (bool) $scan->is_concealed,
            'winning_tile' => array_key_exists('winning_tile', $validated)
                ? $this->winningTile($validated['winning_tile'])
                : $scan->winning_tile,
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
    }

    /**
     * Ubin kemenangan yang mungkin dipilih: tebakan awal (null) dan setiap
     * ubin normal yang sedang ada di tangan.
     *
     * @param  array<int, string>  $tiles
     * @return array<int, string|null>
     */
    private function kandidatUbinMenang(array $tiles): array
    {
        $kandidat = [null];

        foreach ($tiles as $tile) {
            if (! is_string($tile) || $tile === '' || $tile[0] === 'f' || in_array($tile, $kandidat, true)) {
                continue;
            }

            $kandidat[] = $tile;
        }

        return $kandidat;
    }

    /**
     * Tanda tangan skor mirip Tilerank: faan, status limit, dan rincian faan.
     * Rincian dipakai sebagai pengganti payment delta karena aplikasi ini
     * belum menyimpan rincian pembayaran per repaired hand.
     *
     * @param  array<string, mixed>  $analisa
     */
    private function tandaTanganSkor(array $analisa): string
    {
        if (! $analisa['is_winning_hand']) {
            return 'x';
        }

        $rincian = array_map(
            fn (array $detail): string => ($detail['label'] ?? '').':'.($detail['faan'] ?? 0),
            $analisa['faan_details'] ?? [],
        );

        return implode('|', [
            $analisa['faan'] ?? 0,
            $analisa['points'] ?? 0,
            implode(',', $rincian),
        ]);
    }

    /**
     * @return array{seat_wind: string|null, round_wind: string|null, self_draw: bool, concealed: bool, winning_tile: string|null, no_flowers: bool, last_tile: bool, robbing_kong: bool, kong_replacement: bool}
     */
    private function opsiHk(Request $request): array
    {
        return [
            'seat_wind' => $request->input('seat_wind') ?: null,
            'round_wind' => $request->input('round_wind') ?: null,
            'self_draw' => $request->boolean('self_draw'),
            'concealed' => $request->boolean('concealed'),
            'winning_tile' => $this->winningTile($request->input('winning_tile')),
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
        return $this->gemini->tersedia() ? $this->gemini : $this->openAi;
    }

    /**
     * @return array<string, int>
     */
    private function faanValues(): array
    {
        $faan = $this->aturanFaan->faanValues();

        return [
            'chow' => $faan[AturanFaanService::KUNCI_CHOW] ?? 0,
            'pung' => $faan[AturanFaanService::KUNCI_PUNG] ?? 0,
            'kong' => $faan[AturanFaanService::KUNCI_KONG] ?? 0,
            'concealed' => $faan[AturanFaanService::KUNCI_CONCEALED_HAND] ?? 0,
            'selfDraw' => $faan[AturanFaanService::KUNCI_SELF_DRAW] ?? 0,
            'noFlowers' => $faan[AturanFaanService::KUNCI_NO_FLOWERS] ?? 0,
            'ownFlowers' => $faan[AturanFaanService::KUNCI_OWN_FLOWERS] ?? 0,
            'ownSeason' => $faan[AturanFaanService::KUNCI_OWN_SEASON] ?? 0,
            'lastTile' => $faan[AturanFaanService::KUNCI_LAST_TILE] ?? 0,
            'robbingKong' => $faan[AturanFaanService::KUNCI_ROBBING_THE_KONG] ?? 0,
            'kongReplacement' => $faan[AturanFaanService::KUNCI_WIN_ON_KONG_REPLACEMENT] ?? 0,
            'seatWindPung' => $faan[AturanFaanService::KUNCI_SEAT_WIND_PUNG] ?? 0,
            'prevailingWindPung' => $faan[AturanFaanService::KUNCI_PREVAILING_WIND_PUNG] ?? 0,
        ];
    }

    /**
     * @param  array<string, int>  $faan
     */
    private function ringkasanChicken(array $faan): string
    {
        return 'Chow '.$faan['chow'].' point · Pung '.$faan['pung'].' point · Kong '.$faan['kong']
            .' point · Own Flowers '.$faan['ownFlowers'].' point · Own Season '.$faan['ownSeason'].' point';
    }

    private function pesanAturanPoint(): string
    {
        return 'Aturan point belum dikonfigurasi. Aktifkan satu aturan mahjong dan lengkapi aturan dasar faan serta faan hand di admin.';
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
            'winning_tile' => $scan->winning_tile,
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
