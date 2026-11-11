<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Deteksi kartu mahjong memakai Google Gemini (Generative Language API).
 *
 * API key dibuat di Google AI Studio (https://aistudio.google.com/app/apikey)
 * lalu disimpan pada GEMINI_API_KEY.
 */
class GeminiMahjongDetector
{
    /**
     * Pesan terakhir yang bisa ditampilkan ke pengguna.
     */
    public ?string $pesan = null;

    public function __construct(
        private MahjongCombinationService $kombinasi,
        private MahjongImageEncoder $encoder,
    ) {}

    public function tersedia(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Kirim foto ke Gemini Vision dan ubah hasilnya menjadi kode kartu internal.
     *
     * @return array<int, string>
     */
    public function deteksi(string $absolutePath): array
    {
        if (! $this->tersedia()) {
            $this->pesan = 'Layanan AI (Gemini) belum dikonfigurasi. Tambahkan GEMINI_API_KEY lalu tambahkan kartu secara manual.';

            return [];
        }

        if (! is_file($absolutePath)) {
            $this->pesan = 'Foto tidak ditemukan. Silakan unggah ulang.';

            return [];
        }

        $gambar = $this->encoder->base64($absolutePath);

        if ($gambar === null) {
            $this->pesan = 'Foto tidak dapat dibaca. Silakan unggah ulang dengan format JPG, PNG, atau WEBP.';

            return [];
        }

        $payloadGambar = $gambar;
        $modelTerpakai = null;
        $response = null;
        $modelTerakhir = null;

        try {
            foreach ($this->daftarModel() as $model) {
                $modelTerakhir = $model;
                $response = $this->kirimPermintaan($payloadGambar, $model);

                if (! $response->failed()) {
                    $modelTerpakai = $model;

                    break;
                }

                Log::warning('Deteksi mahjong Gemini gagal.', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                /*
                 * Model bisa habis kuota (429), sedang sibuk (503), atau sudah
                 * tidak tersedia (404). Coba model berikutnya bila ada.
                 */
                if (! in_array($response->status(), [404, 429, 500, 503], true)) {
                    break;
                }
            }

            if ($response === null || $response->failed()) {
                $this->pesan = $this->pesanKegagalan($response, $modelTerakhir);

                return [];
            }

            $payload = $response->json();
            $tiles = $this->ambilTiles($payload);

            if ($tiles === null) {
                Log::warning('Deteksi mahjong Gemini: balasan tidak bisa dibaca.', [
                    'model' => $modelTerpakai,
                    'finish_reason' => $payload['candidates'][0]['finishReason'] ?? null,
                    'usage' => $payload['usageMetadata'] ?? null,
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                $this->pesan = 'Balasan AI tidak dapat dibaca. Tambahkan kartu secara manual.';

                return [];
            }

            if ($tiles === []) {
                $this->pesan = 'Kartu belum terbaca dari foto. Pastikan seluruh kartu terlihat, lalu tambahkan manual jika perlu.';
            }

            return $tiles;
        } catch (\Throwable $exception) {
            Log::warning('Deteksi mahjong Gemini error: '.$exception->getMessage());

            $this->pesan = 'Layanan AI tidak dapat dihubungi. Tambahkan kartu secara manual.';

            return [];
        }
    }

    /**
     * Daftar model yang dicoba berurutan. Model pertama adalah GEMINI_MODEL,
     * sisanya berasal dari GEMINI_FALLBACK_MODELS.
     *
     * @return array<int, string>
     */
    private function daftarModel(): array
    {
        $daftar = [];

        $utama = trim((string) config('services.gemini.model'));

        if ($utama !== '') {
            $daftar[] = $utama;
        }

        $cadangan = config('services.gemini.fallback_models', []);

        if (is_string($cadangan)) {
            $cadangan = explode(',', $cadangan);
        }

        foreach ((array) $cadangan as $model) {
            $model = trim((string) $model);

            if ($model !== '') {
                $daftar[] = $model;
            }
        }

        return array_values(array_unique($daftar));
    }

    /**
     * Pesan yang ramah untuk pengguna, termasuk info kuota bila terkena 429.
     */
    private function pesanKegagalan(?Response $response, ?string $model): string
    {
        if ($response?->status() === 429) {
            $retry = $response->json('error.details') ?? [];
            $detik = null;

            foreach ((array) $retry as $detail) {
                if (is_array($detail) && isset($detail['retryDelay'])) {
                    $detik = (int) filter_var($detail['retryDelay'], FILTER_SANITIZE_NUMBER_INT);
                }
            }

            $kapan = $detik !== null && $detik > 0
                ? 'sekitar '.max(1, (int) ceil($detik / 60)).' menit lagi'
                : 'besok';

            return 'Kuota gratis AI sudah habis. Coba lagi '.$kapan.' atau aktifkan billing di Google AI Studio. Sementara itu kartu bisa ditambahkan manual.';
        }

        if ($response?->status() === 503) {
            return 'Layanan AI sedang sibuk. Coba lagi beberapa saat atau tambahkan kartu secara manual.';
        }

        if ($response?->status() === 404) {
            return 'Model AI ('.$model.') tidak tersedia untuk API key ini. Periksa GEMINI_MODEL lalu tambahkan kartu secara manual.';
        }

        return 'Layanan AI sedang tidak bisa dihubungi. Tambahkan kartu secara manual.';
    }

    /**
     * @param  array{mime: string, data: string}  $gambar
     */
    private function kirimPermintaan(array $gambar, string $model): Response
    {
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->instruksiSistem()],
                        [
                            'inline_data' => [
                                'mime_type' => $gambar['mime'],
                                'data' => $gambar['data'],
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 8192),
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'tiles' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'STRING',
                                'enum' => self::kodeKelas(),
                            ],
                        ],
                    ],
                    'required' => ['tiles'],
                ],
            ],
        ];

        return $this->client()->post("/models/{$model}:generateContent", $payload);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/'))
            ->withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
            ->acceptJson()
            ->timeout((int) config('services.gemini.timeout', 120));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<int, string>|null
     */
    private function ambilTiles(?array $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $parts = $payload['candidates'][0]['content']['parts'] ?? null;

        if (! is_array($parts)) {
            return null;
        }

        $teks = '';

        foreach ($parts as $bagian) {
            if (is_array($bagian) && is_string($bagian['text'] ?? null)) {
                $teks .= $bagian['text'];
            }
        }

        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        // Jaga-jaga bila model membungkus JSON dengan markdown code fence.
        $teks = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $teks) ?? $teks;
        $hasil = json_decode($teks, true);

        if (! is_array($hasil)) {
            return null;
        }

        $daftar = $hasil['tiles'] ?? $hasil['kartu'] ?? [];

        if (! is_array($daftar)) {
            return null;
        }

        $tiles = [];

        foreach ($daftar as $kode) {
            if (! is_string($kode)) {
                continue;
            }

            $normal = $this->kombinasi->normalisasiKode($kode);

            if ($normal !== null) {
                $tiles[] = $normal;
            }
        }

        return $tiles;
    }

    private function instruksiSistem(): string
    {
        return <<<'PROMPT'
You are an expert at reading physical mahjong tiles from photographs.

Class codes (use exactly these strings):
- w1..w9 = Character/Wan tiles (Chinese numerals 1-9 above the 萬 character).
- t1..t9 = Circle/Dot/Tong tiles (1-9 circular patterns; t1 is one large circle).
- b1..b9 = Bamboo/Sou tiles (b1 is the bird, b2..b9 are bamboo sticks).
- z1 = East wind (東), z2 = South wind (南), z3 = West wind (西), z4 = North wind (北).
- z5 = Red dragon (中), z6 = Green dragon (發), z7 = White dragon (blank tile, often with a blue frame).

Rules:
1. Report every visible tile exactly once, including duplicate copies.
2. Read tiles left-to-right, then top-to-bottom for multiple rows.
3. Do not invent tiles that are hidden, cut off, or unreadable.
4. Ignore non-tile objects (table, hands, racks, coins, flowers, jokers).
5. If the photo shows no mahjong tiles, return an empty array.
6. Return only JSON matching the provided schema.

Identify all mahjong tiles in this photo and return their class codes in reading order
(left to right, top to bottom). Do not make up tiles that are not visible.
PROMPT;
    }

    /**
     * @return array<int, string>
     */
    private static function kodeKelas(): array
    {
        return [
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9',
            't1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9',
            'b1', 'b2', 'b3', 'b4', 'b5', 'b6', 'b7', 'b8', 'b9',
            'z1', 'z2', 'z3', 'z4', 'z5', 'z6', 'z7',
        ];
    }
}
