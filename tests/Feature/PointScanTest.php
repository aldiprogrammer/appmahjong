<?php

namespace Tests\Feature;

use App\Models\MahjongScan;
use App\Models\Pengguna;
use App\Services\MahjongCombinationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PointScanTest extends TestCase
{
    use RefreshDatabase;

    private const WINNING_TILES = ['w1', 'w2', 'w3', 't4', 't5', 't6', 'b7', 'b8', 'b9', 'z1', 'z1', 'z1', 't9', 't9'];

    public function test_service_mendeteksi_winning_hand_dan_menghitung_point(): void
    {
        $hasil = (new MahjongCombinationService)->analisa(self::WINNING_TILES);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $hasil['points']);
        $this->assertCount(5, $hasil['combinations']);
        $this->assertSame(
            ['chow', 'chow', 'chow', 'pung', 'pair'],
            array_column($hasil['combinations'], 'type'),
        );
    }

    public function test_service_mendeteksi_tujuh_pasang_sebagai_winning_hand(): void
    {
        $hasil = (new MahjongCombinationService)->analisa([
            'w1', 'w1', 'w3', 'w3', 't2', 't2', 't7', 't7', 'b4', 'b4', 'b9', 'b9', 'z5', 'z5',
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $hasil['points']);
    }

    public function test_service_mengembalikan_nol_point_untuk_susunan_bukan_winning(): void
    {
        $hasil = (new MahjongCombinationService)->analisa(['w1', 'w3', 'w5', 't2', 'b8', 'z1']);

        $this->assertFalse($hasil['is_winning_hand']);
        $this->assertSame(0, $hasil['points']);
    }

    public function test_scan_menyimpan_foto_dan_membuat_scan_pending(): void
    {
        Storage::fake('public');
        config(['services.openai.key' => null, 'services.gemini.key' => null]);
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame('pending', $scan->status);
        $this->assertNotNull($scan->image_path);
        Storage::disk('public')->assertExists($scan->image_path);
    }

    public function test_scan_wajib_berupa_gambar(): void
    {
        Storage::fake('public');
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), ['image' => 'bukan-file'])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_konfirmasi_winning_hand_menambahkan_point(): void
    {
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertRedirect(route('point'));

        $scan->refresh();
        $pengguna->refresh();

        $this->assertSame('confirmed', $scan->status);
        $this->assertNotNull($scan->confirmed_at);
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $pengguna->points);
        $this->assertDatabaseHas('ranks', [
            'kode_user' => $pengguna->kode_user,
            'point' => (string) MahjongCombinationService::POINT_PER_HAND,
        ]);
    }

    public function test_konfirmasi_kedua_kali_tidak_menambah_point_lagi(): void
    {
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertSessionHas('error');

        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $pengguna->refresh()->points);
    }

    public function test_susunan_bukan_winning_tidak_bisa_dikonfirmasi(): void
    {
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, ['w1', 'w3', 'w5', 't2']);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertSessionHas('error');

        $this->assertSame('pending', $scan->refresh()->status);
        $this->assertSame(0, $pengguna->refresh()->points);
    }

    public function test_pengguna_lain_tidak_bisa_mengonfirmasi_scan(): void
    {
        $pemilik = $this->buatPengguna();
        $penggunaLain = $this->buatPengguna('PG-POINT02', 'lain@example.com');
        $scan = $this->buatScan($pemilik, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertNotFound();

        $this->assertSame(0, $pemilik->refresh()->points);
    }

    public function test_label_deteksi_dinormalisasi_ke_kode_internal(): void
    {
        $service = new MahjongCombinationService;

        $this->assertSame('w1', $service->normalisasiKode('1m'));
        $this->assertSame('t2', $service->normalisasiKode('2p'));
        $this->assertSame('b3', $service->normalisasiKode('3s'));
        $this->assertSame('w4', $service->normalisasiKode('4man'));
        $this->assertSame('t5', $service->normalisasiKode('pin5'));
        $this->assertSame('b6', $service->normalisasiKode('6-bamboo'));
        $this->assertSame('z5', $service->normalisasiKode('Red Dragon'));
        $this->assertSame('z7', $service->normalisasiKode('nagaputih'));
        $this->assertNull($service->normalisasiKode('bukan-ubin'));
    }

    public function test_scan_memakai_hasil_deteksi_ai_untuk_winning_hand(): void
    {
        Storage::fake('public');
        config([
            'services.openai.key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'services.gemini.key' => null,
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['tiles' => self::WINNING_TILES]),
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(
            (new MahjongCombinationService)->analisa(self::WINNING_TILES)['tiles'],
            $scan->tiles,
        );
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $scan->points);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($request['model'] ?? null) === config('services.mahjong_ai.model');
        });
    }

    public function test_scan_tetap_berjalan_saat_openai_belum_dikonfigurasi(): void
    {
        Storage::fake('public');
        config([
            'services.openai.key' => null,
            'services.gemini.key' => null,
            'services.mahjong_ai.model' => 'gpt-4o-mini',
        ]);

        Http::preventStrayRequests();

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'))
            ->assertSessionHas('success');

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertFalse($scan->is_winning_hand);
        $this->assertSame([], $scan->tiles);
        $this->assertStringContainsString('belum dikonfigurasi', (string) session('success'));
    }

    public function test_scan_memakai_gemini_saat_api_key_tersedia(): void
    {
        Storage::fake('public');
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.max_output_tokens' => 8192,
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['tiles' => self::WINNING_TILES]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $scan->points);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://gemini.test/v1beta/models/gemini-test-model:generateContent'
                && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
                /*
                 * Gemini 3.x memakai sebagian besar token untuk "thinking".
                 * Batas kecil membuat JSON terpotong sehingga kartu kosong,
                 * jadi pastikan anggaran output tetap besar.
                 */
                && ($request['generationConfig']['maxOutputTokens'] ?? 0) >= 4096
                && isset($request['contents'][0]['parts'][1]['inline_data']['data']);
        });
    }

    public function test_balasan_gemini_yang_terpotong_tidak_menghasilkan_kartu_palsu(): void
    {
        Storage::fake('public');
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'MAX_TOKENS',
                    'content' => [
                        'parts' => [
                            ['text' => '{"tiles":["'],
                        ],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame([], $scan->tiles);
        $this->assertFalse($scan->is_winning_hand);
        $this->assertSame(0, $scan->points);
        $this->assertStringContainsString('tidak dapat dibaca', (string) session('success'));
    }

    public function test_scan_berpindah_ke_model_cadangan_saat_model_utama_kena_limit(): void
    {
        Storage::fake('public');
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-utama',
            'services.gemini.fallback_models' => ['gemini-cadangan'],
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/v1beta/models/gemini-utama:generateContent' => Http::response([
                'error' => [
                    'code' => 429,
                    'status' => 'RESOURCE_EXHAUSTED',
                    'message' => 'Quota exceeded.',
                ],
            ], 429),
            'gemini.test/v1beta/models/gemini-cadangan:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['tiles' => self::WINNING_TILES]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(MahjongCombinationService::POINT_PER_HAND, $scan->points);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://gemini.test/v1beta/models/gemini-cadangan:generateContent');
    }

    public function test_pesan_kuota_muncul_saat_semua_model_kena_limit(): void
    {
        Storage::fake('public');
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-utama',
            'services.gemini.fallback_models' => ['gemini-cadangan'],
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'status' => 'RESOURCE_EXHAUSTED',
                    'message' => 'Quota exceeded.',
                    'details' => [[
                        '@type' => 'type.googleapis.com/google.rpc.RetryInfo',
                        'retryDelay' => '3600s',
                    ]],
                ],
            ], 429),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame([], $scan->tiles);
        $this->assertStringContainsString('Kuota gratis', (string) session('success'));
        $this->assertStringContainsString('60 menit', (string) session('success'));
    }

    /**
     * @param  array<int, string>  $tiles
     */
    private function buatScan(Pengguna $pengguna, array $tiles): MahjongScan
    {
        $hasil = (new MahjongCombinationService)->analisa($tiles);

        return MahjongScan::create([
            'id_pengguna' => (string) $pengguna->id,
            'tiles' => $hasil['tiles'],
            'combinations' => $hasil['combinations'],
            'unmatched' => $hasil['unmatched'],
            'message' => $hasil['message'],
            'is_winning_hand' => $hasil['is_winning_hand'],
            'points' => $hasil['points'],
            'status' => 'pending',
        ]);
    }

    private function buatPengguna(string $kode = 'PG-POINT01', string $email = 'point@example.com'): Pengguna
    {
        return Pengguna::create([
            'kode_user' => $kode,
            'nama_depan' => 'Point',
            'nama_balakang' => 'Tester',
            'email' => $email,
            'nowa' => '081200000001',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
    }
}
