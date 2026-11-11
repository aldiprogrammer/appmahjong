<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiMahjongDetector
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
        return filled(config('services.openai.key'));
    }

    /**
     * Kirim foto ke OpenAI Vision dan ubah hasilnya menjadi kode kartu internal.
     *
     * @return array<int, string>
     */
    public function deteksi(string $absolutePath): array
    {
        if (! $this->tersedia()) {
            $this->pesan = 'Layanan AI (OpenAI) belum dikonfigurasi. Tambahkan OPENAI_API_KEY lalu tambahkan kartu secara manual.';

            return [];
        }

        if (! is_file($absolutePath)) {
            $this->pesan = 'Foto tidak ditemukan. Silakan unggah ulang.';

            return [];
        }

        $dataUri = $this->encoder->dataUri($absolutePath);

        if ($dataUri === null) {
            $this->pesan = 'Foto tidak dapat dibaca. Silakan unggah ulang dengan format JPG, PNG, atau WEBP.';

            return [];
        }

        try {
            $response = $this->kirimPermintaan($dataUri, true);

            // Sebagian model belum mendukung json_schema; ulangi dengan json_object.
            if ($response->status() === 400 && str_contains($response->body(), 'response_format')) {
                $response = $this->kirimPermintaan($dataUri, false);
            }

            if ($response->failed()) {
                Log::warning('Deteksi mahjong OpenAI gagal.', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                $this->pesan = 'Layanan AI sedang tidak bisa dihubungi. Tambahkan kartu secara manual.';

                return [];
            }

            $tiles = $this->ambilTiles($response->json());

            if ($tiles === null) {
                $this->pesan = 'Balasan AI tidak dapat dibaca. Tambahkan kartu secara manual.';

                return [];
            }

            if ($tiles === []) {
                $this->pesan = 'Kartu belum terbaca dari foto. Pastikan seluruh kartu terlihat, lalu tambahkan manual jika perlu.';
            }

            return $tiles;
        } catch (\Throwable $exception) {
            Log::warning('Deteksi mahjong OpenAI error: '.$exception->getMessage());

            $this->pesan = 'Layanan AI tidak dapat dihubungi. Tambahkan kartu secara manual.';

            return [];
        }
    }

    private function kirimPermintaan(string $dataUri, bool $pakaiJsonSchema): Response
    {
        $model = config('services.mahjong_ai.model')
            ?: config('services.openai.model', 'gpt-4o-mini');

        $payload = [
            'model' => $model,
            'temperature' => 0,
            'max_tokens' => 300,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->instruksiSistem(),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Identifikasi seluruh ubin mahjong pada foto ini dan kembalikan kode kelasnya sesuai urutan baca (kiri ke kanan, atas ke bawah). Jangan mengarang ubin yang tidak terlihat.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $dataUri,
                                'detail' => config('services.mahjong_ai.detail', 'high'),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($pakaiJsonSchema) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'mahjong_tiles',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'tiles' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'enum' => self::kodeKelas(),
                                ],
                            ],
                        ],
                        'required' => ['tiles'],
                        'additionalProperties' => false,
                    ],
                ],
            ];
        } else {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $this->client()->post('/chat/completions', $payload);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'))
            ->withToken((string) config('services.openai.key'))
            ->acceptJson()
            ->timeout((int) config('services.mahjong_ai.timeout', 60));
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

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (is_array($content)) {
            $teks = null;

            foreach ($content as $bagian) {
                if (is_array($bagian) && ($bagian['type'] ?? null) === 'text' && is_string($bagian['text'] ?? null)) {
                    $teks = $bagian['text'];
                    break;
                }
            }

            $content = $teks;
        }

        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $hasil = json_decode($content, true);

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
