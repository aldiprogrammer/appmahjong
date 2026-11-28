<?php

namespace Tests\Feature;

use App\Models\Aturandasarfaan;
use App\Models\Aturanmahjong;
use App\Models\Booking;
use App\Models\Faanhand;
use App\Models\MahjongScan;
use App\Models\Pengguna;
use App\Services\MahjongCombinationService;
use Database\Seeders\FaanRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PointScanTest extends TestCase
{
    use RefreshDatabase;

    private const WINNING_TILES = ['w1', 'w2', 'w3', 't4', 't5', 't6', 'b7', 'b8', 'b9', 'z1', 'z1', 'z1', 't9', 't9'];

    public function test_service_mendeteksi_winning_hand_dan_menghitung_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa(self::WINNING_TILES);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(6, $hasil['points']);
        $this->assertCount(5, $hasil['combinations']);
        $this->assertSame(
            ['chow', 'chow', 'chow', 'pung', 'pair'],
            array_column($hasil['combinations'], 'type'),
        );
    }

    public function test_service_mendeteksi_tujuh_pasang_sebagai_winning_hand(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w3', 'w3', 't2', 't2', 't7', 't7', 'b4', 'b4', 'b9', 'b9', 'z5', 'z5',
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(0, $hasil['points']);
    }

    public function test_service_menghitung_point_per_meld_chicken_hand(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'b1', 'b2', 'b3',
            'b4', 'b5', 'b6', 'b6', 'b6', 'b6',
            'w5', 'w5', 'w5',
            't8', 't8',
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(
            ['pung', 'chow', 'chow', 'pung', 'pair'],
            array_column($hasil['combinations'], 'type'),
        );
        $this->assertSame(8, $hasil['points']);
    }

    public function test_service_menghitung_kong_satu_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w1',
            't2', 't3', 't4',
            'b5', 'b6', 'b7',
            'z1', 'z1', 'z1',
            't9', 't9',
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertContains('kong', array_column($hasil['combinations'], 'type'));
        $this->assertSame(6, $hasil['points']);
    }

    public function test_service_bunga_tanpa_posisi_duduk_tidak_menambah_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([...self::WINNING_TILES, 'f1', 'f5', 'f3']);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(6, $hasil['points']);
        $this->assertSame(['f1', 'f3', 'f5'], $hasil['flowers']);
    }

    public function test_service_set_bunga_sesuai_posisi_duduk_menambah_satu_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa(
            [...self::WINNING_TILES, 'f1', 'f3'],
            MahjongCombinationService::MODE_CHICKEN,
            ['seat_wind' => 'z1'],
        );

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(7, $hasil['points']);
        $this->assertContains('special', array_column($hasil['combinations'], 'type'));
        $this->assertStringContainsString('bunga kursi', strtolower((string) $hasil['message']));
    }

    public function test_service_bunga_bukan_posisi_duduk_tidak_menambah_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa(
            [...self::WINNING_TILES, 'f2', 'f6'],
            MahjongCombinationService::MODE_CHICKEN,
            ['seat_wind' => 'z1'],
        );

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(6, $hasil['points']);
        $this->assertNotContains('special', array_column($hasil['combinations'], 'type'));
    }

    public function test_service_mengembalikan_nol_point_untuk_susunan_bukan_winning(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa(['w1', 'w3', 'w5', 't2', 'b8', 'z1']);

        $this->assertFalse($hasil['is_winning_hand']);
        $this->assertSame(0, $hasil['points']);
    }

    public function test_hk_klasik_empat_kong_tertutup_hanya_menampilkan_pola_limit(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w1',
            't2', 't2', 't2', 't2',
            'b3', 'b3', 'b3', 'b3',
            'z1', 'z1', 'z1', 'z1',
            'b8', 'b8',
        ], MahjongCombinationService::MODE_HK, ['concealed' => true]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(13, $hasil['faan']);
        $this->assertSame(13, $hasil['points']);
        $this->assertSame(
            ['四槓 (All Kongs)', '四暗刻 (Four Concealed Pungs)'],
            array_column($hasil['faan_details'], 'label'),
        );
    }

    public function test_hk_klasik_empat_kong_terbuka_hanya_menampilkan_all_kongs(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w1',
            't2', 't2', 't2', 't2',
            'b3', 'b3', 'b3', 'b3',
            'z1', 'z1', 'z1', 'z1',
            'b8', 'b8',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(13, $hasil['faan']);
        $this->assertSame(
            ['四槓 (All Kongs)'],
            array_column($hasil['faan_details'], 'label'),
        );
    }

    public function test_hk_klasik_faan_semua_kong_mengikuti_nilai_di_tabel_faanhands(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $aturan = Aturanmahjong::query()->where('kode', 'AM001')->firstOrFail();
        Aturandasarfaan::query()
            ->where('id_aturanmahjong', (string) $aturan->id)
            ->update(['batas_faan' => '20']);
        Faanhand::query()
            ->where('id_aturanmahjong', (string) $aturan->id)
            ->where('inggris', 'All Kongs')
            ->update(['faan' => '8']);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w1',
            't2', 't2', 't2', 't2',
            'b3', 'b3', 'b3', 'b3',
            'z1', 'z1', 'z1', 'z1',
            'b8', 'b8',
        ], MahjongCombinationService::MODE_HK, ['concealed' => true]);

        $detailAllKongs = collect($hasil['faan_details'])
            ->firstWhere('label', '四槓 (All Kongs)');

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(20, $hasil['faan']);
        $this->assertSame(8, $detailAllKongs['faan']);
        $this->assertSame(
            ['四槓 (All Kongs)', '四暗刻 (Four Concealed Pungs)'],
            array_column($hasil['faan_details'], 'label'),
        );
    }

    public function test_hk_klasik_satu_kong_tetap_dihitung_walaupun_tiga_belas_kartu_lebih(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w1',
            'z5', 'z5', 'z5',
            'z6', 'z6', 'z6',
            'b7', 'b8', 'b9',
            'b2', 'b2',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(3, $hasil['faan']);
        $this->assertContains('槓 (Kong) 1 Wan', array_column($hasil['faan_details'], 'label'));
    }

    public function test_hk_klasik_di_bawah_tiga_faan_belum_bisa_menang(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 't2', 't3', 't4', 'b7', 'b8', 'b9', 't9', 't9',
        ], MahjongCombinationService::MODE_HK);

        $this->assertFalse($hasil['is_winning_hand']);
        $this->assertSame(MahjongCombinationService::MODE_HK, $hasil['scoring_mode']);
        $this->assertSame(1, $hasil['faan']);
        $this->assertSame(0, $hasil['points']);
        $this->assertStringContainsString('minimal 3 faan', $hasil['message']);
        $this->assertContains('平胡 (All Chows)', array_column($hasil['faan_details'], 'label'));
    }

    public function test_hk_klasik_menghitung_full_flush_dan_all_pungs(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(10, $hasil['faan']);
        $this->assertSame(10, $hasil['points']);
        $this->assertContains('清一色 (Full Flush)', array_column($hasil['faan_details'], 'label'));
        $this->assertContains('對對胡 (All Pungs)', array_column($hasil['faan_details'], 'label'));
    }

    public function test_hk_klasik_mendeteksi_thirteen_orphans(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w9', 't1', 't9', 'b1', 'b9', 'z1', 'z2', 'z3', 'z4', 'z5', 'z6', 'z7',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(13, $hasil['faan']);
        $this->assertSame(13, $hasil['points']);
        $this->assertSame('special', $hasil['combinations'][0]['type']);
    }

    public function test_hk_klasik_membatasi_maksimum_tiga_belas_faan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'z1', 'z1', 'z1', 'z2', 'z2', 'z2', 'z3', 'z3', 'z3', 'z4', 'z4', 'z4', 'z5', 'z5',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(13, $hasil['faan']);
        $this->assertSame(13, $hasil['points']);
    }

    public function test_hk_klasik_mendeteksi_tujuh_pasang_sebagai_empat_faan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w3', 'w3', 't2', 't2', 't7', 't7', 'b4', 'b4', 'b9', 'b9', 'z5', 'z5',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(4, $hasil['faan']);
        $this->assertSame(4, $hasil['points']);
    }

    public function test_hk_klasik_menghitung_self_draw_dan_kartu_tertutup(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1',
        ], MahjongCombinationService::MODE_HK, [
            'self_draw' => true,
            'concealed' => true,
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(6, $hasil['faan']);
        $labels = array_column($hasil['faan_details'], 'label');
        $this->assertContains('自摸 (Self-draw)', $labels);
        $this->assertContains('門前清 (Concealed Hand)', $labels);
    }

    public function test_hk_klasik_menghitung_kondisi_tambahan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1',
        ], MahjongCombinationService::MODE_HK, [
            'self_draw' => true,
            'concealed' => true,
            'no_flowers' => true,
            'last_tile' => true,
            'robbing_kong' => true,
            'kong_replacement' => true,
        ]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(10, $hasil['faan']);

        $labels = array_column($hasil['faan_details'], 'label');

        $this->assertContains('自摸 (Self-draw)', $labels);
        $this->assertContains('門前清 (Concealed Hand)', $labels);
        $this->assertContains('無花 (No Flowers)', $labels);
        $this->assertContains('海底撈月 (Last Tile)', $labels);
        $this->assertContains('搶槓 (Robbing Kong)', $labels);
        $this->assertContains('槓上開花 (Kong Replacement)', $labels);
    }

    public function test_hk_klasik_mendeteksi_own_flower_dari_bunga_pada_foto(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1', 'f5',
        ], MahjongCombinationService::MODE_HK, ['seat_wind' => 'z1']);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(['f5'], $hasil['flowers']);
        $this->assertContains('f5', $hasil['tiles']);
        $this->assertContains('正花 (Own Flowers)', array_column($hasil['faan_details'], 'label'));
        $this->assertSame(5, $hasil['faan']);
    }

    public function test_hk_klasik_mendeteksi_own_season_dari_bunga_pada_foto(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1', 'f1',
        ], MahjongCombinationService::MODE_HK, ['seat_wind' => 'z1']);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(['f1'], $hasil['flowers']);
        $this->assertContains('f1', $hasil['tiles']);
        $this->assertContains('季節 (Own Season)', array_column($hasil['faan_details'], 'label'));
        $this->assertSame(5, $hasil['faan']);
    }

    public function test_hk_klasik_no_flowers_diabaikan_saat_ada_bunga_terdeteksi(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1', 'f5',
        ], MahjongCombinationService::MODE_HK, [
            'seat_wind' => 'z1',
            'no_flowers' => true,
        ]);

        $labels = array_column($hasil['faan_details'], 'label');

        $this->assertContains('正花 (Own Flowers)', $labels);
        $this->assertNotContains('無花 (No Flowers)', $labels);
    }

    public function test_hk_klasik_pung_angin_hanya_dihitung_saat_cocok_angin_kursi_atau_meja(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z1', 'z1', 'z1', 'z2', 'z2'];

        $tanpa = app(MahjongCombinationService::class)->analisa($tiles, MahjongCombinationService::MODE_HK);
        $dengan = app(MahjongCombinationService::class)->analisa($tiles, MahjongCombinationService::MODE_HK, [
            'seat_wind' => 'z1',
            'round_wind' => 'z3',
        ]);

        $this->assertSame(3, $tanpa['faan']);
        $this->assertSame(4, $dengan['faan']);
        $this->assertContains('Angin Timur (angin kursi)', array_column($dengan['faan_details'], 'label'));

        $putaran = app(MahjongCombinationService::class)->analisa($tiles, MahjongCombinationService::MODE_HK, [
            'round_wind' => 'z1',
        ]);

        $this->assertSame(4, $putaran['faan']);
        $this->assertContains('Angin Timur (angin putaran)', array_column($putaran['faan_details'], 'label'));
    }

    public function test_hk_klasik_kartu_tertutup_semua_pung_menjadi_si_an_ke(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9',
        ], MahjongCombinationService::MODE_HK, ['concealed' => true]);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(13, $hasil['faan']);
        $this->assertSame(
            ['四暗刻 (Four Concealed Pungs)'],
            array_column($hasil['faan_details'], 'label'),
        );
    }

    public function test_hk_ubin_kemenangan_yang_menutup_pung_membatalkan_empat_pung_tersembunyi(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];
        $servis = app(MahjongCombinationService::class);

        $dariPung = $servis->analisa($tiles, MahjongCombinationService::MODE_HK, [
            'concealed' => true,
            'winning_tile' => 'w1',
        ]);
        $dariPasangan = $servis->analisa($tiles, MahjongCombinationService::MODE_HK, [
            'concealed' => true,
            'winning_tile' => 'w9',
        ]);

        $this->assertSame(11, $dariPung['faan']);
        $this->assertContains('對對胡 (All Pungs)', array_column($dariPung['faan_details'], 'label'));
        $this->assertNotContains('四暗刻 (Four Concealed Pungs)', array_column($dariPung['faan_details'], 'label'));

        $this->assertSame(13, $dariPasangan['faan']);
        $this->assertContains('四暗刻 (Four Concealed Pungs)', array_column($dariPasangan['faan_details'], 'label'));
    }

    public function test_kartu_menyimpan_ubin_kemenangan_dan_menghitung_ulang_faan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, $tiles, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.kartu', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'concealed' => 1,
                'winning_tile' => 'w1',
            ])
            ->assertRedirect();

        $scan->refresh();

        $this->assertSame('w1', $scan->winning_tile);
        $this->assertTrue($scan->is_concealed);
        $this->assertSame(11, $scan->faan);
        $this->assertSame(11, $scan->points);
        $this->assertNotContains('四暗刻 (Four Concealed Pungs)', array_column($scan->faan_details ?? [], 'label'));
    }

    public function test_kartu_boleh_melewati_penandaan_ubin_kemenangan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, $tiles, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.kartu', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'concealed' => 1,
                'winning_tile' => 'w1',
            ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.kartu', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'concealed' => 1,
                'winning_tile' => null,
            ])
            ->assertRedirect();

        $scan->refresh();

        $this->assertNull($scan->winning_tile);
        $this->assertSame(13, $scan->faan);
        $this->assertContains('四暗刻 (Four Concealed Pungs)', array_column($scan->faan_details ?? [], 'label'));
    }

    public function test_kartu_menolak_ubin_kemenangan_yang_tidak_dikenal(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.kartu', $scan), [
                'tiles' => self::WINNING_TILES,
                'mode' => MahjongCombinationService::MODE_CHICKEN,
                'seat_wind' => 'z1',
                'winning_tile' => 'bukan-ubin',
            ])
            ->assertSessionHasErrors('winning_tile');

        $this->assertNull($scan->refresh()->winning_tile);
    }

    public function test_bandingkan_bukaan_menanyakan_pilihan_saat_faan_tertutup_dan_terbuka_beda(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, $tiles, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.bandingkan', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'concealed' => 0,
            ])
            ->assertOk()
            ->assertJson([
                'perlu_ditanya' => true,
                'paksa_tertutup' => false,
                'alasan' => 'foto',
            ]);
    }

    public function test_bandingkan_bukaan_menanyakan_pilihan_saat_hanya_tertutup_yang_mencapai_minimal(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.bandingkan', $scan), [
                'tiles' => self::WINNING_TILES,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'self_draw' => 1,
                'concealed' => 0,
            ])
            ->assertOk()
            ->assertJson([
                'perlu_ditanya' => true,
                'paksa_tertutup' => false,
                'alasan' => 'foto',
            ]);
    }

    public function test_bandingkan_bukaan_melewati_pertanyaan_untuk_tujuh_pasang(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w3', 'w3', 't2', 't2', 't7', 't7', 'b4', 'b4', 'b9', 'b9', 'z5', 'z5'];
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, $tiles, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.bandingkan', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'concealed' => 0,
            ])
            ->assertOk()
            ->assertJson([
                'perlu_ditanya' => false,
                'paksa_tertutup' => true,
                'alasan' => 'selalu_tertutup',
            ]);
    }

    public function test_bandingkan_bukaan_melewati_pertanyaan_saat_skor_sama(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.bandingkan', $scan), [
                'tiles' => self::WINNING_TILES,
                'mode' => MahjongCombinationService::MODE_CHICKEN,
                'seat_wind' => 'z1',
                'concealed' => 0,
            ])
            ->assertOk()
            ->assertJson([
                'perlu_ditanya' => false,
                'paksa_tertutup' => false,
                'alasan' => 'sama',
            ]);
    }

    public function test_bandingkan_bukaan_hanya_boleh_melihat_scan_sendiri(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pemilik = $this->buatPengguna('PG-POINT-PEMILIK', 'pemilik@example.com');
        $lain = $this->buatPengguna('PG-POINT-LAIN', 'lain@example.com');
        $scan = $this->buatScan($pemilik, self::WINNING_TILES, MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $lain->id])
            ->post(route('point.bandingkan', $scan), [
                'tiles' => self::WINNING_TILES,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
            ])
            ->assertNotFound();
    }

    public function test_hk_tiga_naga_besar_tidak_menambah_faan_pong_naga_individu(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'w2', 'w3', 'w4', 'b8', 'b8', 'z5', 'z5', 'z5', 'z6', 'z6', 'z6', 'z7', 'z7', 'z7',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(8, $hasil['faan']);
        $this->assertSame(8, $hasil['points']);

        $label = array_column($hasil['faan_details'], 'label');

        $this->assertContains('大三元 (Great Three Dragons)', $label);
        $this->assertNotContains('Naga Merah', $label);
        $this->assertNotContains('Naga Hijau', $label);
        $this->assertNotContains('Naga Putih', $label);
    }

    public function test_hk_tiga_naga_kecil_tidak_menambah_faan_pong_naga_individu(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $hasil = app(MahjongCombinationService::class)->analisa([
            'z5', 'z5', 'z5', 'z6', 'z6', 'z6', 'z7', 'z7', 'w2', 'w3', 'w4', 't2', 't3', 't4',
        ], MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(5, $hasil['faan']);
        $this->assertSame(5, $hasil['points']);

        $label = array_column($hasil['faan_details'], 'label');

        $this->assertContains('小三元 (Small Three Dragons)', $label);
        $this->assertNotContains('Naga Merah', $label);
        $this->assertNotContains('Naga Hijau', $label);
    }

    public function test_scan_menyimpan_penanda_manual_hk(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => [],
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['tiles' => [
                                'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8', 'w9', 'z5', 'z5', 'z5', 'z1', 'z1',
                            ]]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'round_wind' => 'z2',
                'self_draw' => 1,
                'concealed' => 1,
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame('z1', $scan->seat_wind);
        $this->assertSame('z2', $scan->round_wind);
        $this->assertTrue($scan->is_self_draw);
        $this->assertTrue($scan->is_concealed);
        $this->assertSame(6, $scan->faan);
        $this->assertSame(6, $scan->points);
    }

    public function test_scan_menyimpan_kondisi_hk_tambahan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => [],
            'services.openai.key' => null,
        ]);

        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['tiles' => [
                                'w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9',
                            ]]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
                'no_flowers' => 1,
                'last_tile' => 1,
                'robbing_kong' => 1,
                'kong_replacement' => 1,
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_no_flowers);
        $this->assertTrue($scan->is_last_tile);
        $this->assertTrue($scan->is_robbing_kong);
        $this->assertTrue($scan->is_kong_replacement);
        $this->assertSame(13, $scan->faan);
        $this->assertSame(13, $scan->points);
    }

    public function test_scan_menyimpan_foto_dan_membuat_scan_pending(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config(['services.openai.key' => null, 'services.gemini.key' => null]);
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame('pending', $scan->status);
        $this->assertSame('z1', $scan->seat_wind);
        $this->assertStringStartsWith(
            'https://res.cloudinary.com/test-cloud/image/upload/',
            (string) $scan->image_path,
        );
        $this->assertStringContainsString('/scanning-point/', (string) $scan->image_path);
        $this->assertSame($scan->image_path, MahjongScan::imageUrl($scan->image_path));
        $this->assertSame([], Storage::disk('public')->files('mahjong-scans'));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/upload'
            && str_starts_with((string) $this->parameterCloudinary($request, 'public_id'), 'scanning-point/'));
    }

    public function test_gagal_unggah_foto_scan_ke_cloudinary_tidak_membuat_scan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);
        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Server error']], 500),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertSessionHas('error', 'Gagal mengunggah foto scan ke Cloudinary. Silakan coba lagi.');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_url_gambar_scan_lokal_lama_tetap_memakai_storage(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $this->assertSame(
            asset('storage/mahjong-scans/lama.jpg'),
            MahjongScan::imageUrl('mahjong-scans/lama.jpg'),
        );
        $this->assertNull(MahjongScan::imageUrl(null));
    }

    public function test_scan_mode_hk_menyimpan_faan_dan_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => [],
            'services.openai.key' => null,
        ]);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];

        Http::fake([
            'gemini.test/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['tiles' => $tiles]),
                        ]],
                    ],
                ]],
            ]),
        ]);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertSame(MahjongCombinationService::MODE_HK, $scan->scoring_mode);
        $this->assertSame(10, $scan->faan);
        $this->assertSame(10, $scan->points);
        $this->assertTrue($scan->is_winning_hand);
    }

    public function test_mengganti_mode_menghitung_ulang_faan_tanpa_scan_baru(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $tiles = ['w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9'];
        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, $tiles);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.kartu', $scan), [
                'tiles' => $tiles,
                'mode' => MahjongCombinationService::MODE_HK,
                'seat_wind' => 'z1',
            ])
            ->assertRedirect();

        $scan->refresh();

        $this->assertSame(MahjongCombinationService::MODE_HK, $scan->scoring_mode);
        $this->assertSame(10, $scan->faan);
        $this->assertSame(10, $scan->points);
    }

    public function test_konfirmasi_hk_menambahkan_point_sesuai_faan(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, [
            'w1', 'w1', 'w1', 'w3', 'w3', 'w3', 'w5', 'w5', 'w5', 'w7', 'w7', 'w7', 'w9', 'w9',
        ], MahjongCombinationService::MODE_HK);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertRedirect(route('point'));

        $this->assertSame(10, $pengguna->refresh()->points);
        $this->assertDatabaseHas('ranks', [
            'kode_user' => $pengguna->kode_user,
            'point' => '10',
        ]);
    }

    public function test_scan_wajib_berupa_gambar(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), ['image' => 'bukan-file'])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_scan_foto_lebih_besar_dari_5mb_tetap_diterima(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config([
            'services.openai.key' => null,
            'services.gemini.key' => null,
        ]);

        Http::preventStrayRequests();

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg')->size(6144),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, MahjongScan::query()->count());
    }

    public function test_scan_hk_wajib_pilih_posisi_duduk(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'mode' => MahjongCombinationService::MODE_HK,
            ])
            ->assertSessionHasErrors('seat_wind');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_scan_chicken_hand_wajib_pilih_posisi_duduk(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'mode' => MahjongCombinationService::MODE_CHICKEN,
            ])
            ->assertSessionHasErrors('seat_wind');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_konfirmasi_winning_hand_menambahkan_point(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertRedirect(route('point'));

        $scan->refresh();
        $pengguna->refresh();

        $this->assertSame('confirmed', $scan->status);
        $this->assertNotNull($scan->confirmed_at);
        $this->assertSame(6, $pengguna->points);
        $this->assertDatabaseHas('ranks', [
            'kode_user' => $pengguna->kode_user,
            'point' => '6',
        ]);
    }

    public function test_konfirmasi_kedua_kali_tidak_menambah_point_lagi(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.konfirmasi', $scan))
            ->assertSessionHas('error');

        $this->assertSame(6, $pengguna->refresh()->points);
    }

    public function test_susunan_bukan_winning_tidak_bisa_dikonfirmasi(): void
    {
        $this->seed(FaanRuleSeeder::class);

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
        $this->seed(FaanRuleSeeder::class);

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
        $this->seed(FaanRuleSeeder::class);

        $service = app(MahjongCombinationService::class);

        $this->assertSame('w1', $service->normalisasiKode('1m'));
        $this->assertSame('t2', $service->normalisasiKode('2p'));
        $this->assertSame('b3', $service->normalisasiKode('3s'));
        $this->assertSame('w4', $service->normalisasiKode('4man'));
        $this->assertSame('t5', $service->normalisasiKode('pin5'));
        $this->assertSame('b6', $service->normalisasiKode('6-bamboo'));
        $this->assertSame('z5', $service->normalisasiKode('Red Dragon'));
        $this->assertSame('z7', $service->normalisasiKode('nagaputih'));
        $this->assertSame('f1', $service->normalisasiKode('spring'));
        $this->assertSame('f5', $service->normalisasiKode('bungaplum'));
        $this->assertNull($service->normalisasiKode('bukan-ubin'));
    }

    public function test_scan_memakai_hasil_deteksi_ai_untuk_winning_hand(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(
            app(MahjongCombinationService::class)->analisa(self::WINNING_TILES)['tiles'],
            $scan->tiles,
        );
        $this->assertSame(6, $scan->points);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($request['model'] ?? null) === config('services.mahjong_ai.model');
        });
    }

    public function test_scan_tetap_berjalan_saat_openai_belum_dikonfigurasi(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
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
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config([
            'services.gemini.key' => 'gemini-test-key',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.max_output_tokens' => 8192,
            'services.gemini.media_resolution' => 'high',
            'services.gemini.thinking_level' => 'low',
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(6, $scan->points);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://gemini.test/v1beta/models/gemini-test-model:generateContent'
                && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
                /*
                 * Gemini 3.x memakai sebagian besar token untuk "thinking".
                 * Batas kecil membuat JSON terpotong sehingga kartu kosong,
                 * jadi pastikan anggaran output tetap besar.
                 */
                && ($request['generationConfig']['maxOutputTokens'] ?? 0) >= 4096
                && ($request['generationConfig']['mediaResolution'] ?? null) === 'MEDIA_RESOLUTION_HIGH'
                && ($request['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'low'
                && isset($request['contents'][0]['parts'][1]['inline_data']['data']);
        });
    }

    public function test_balasan_gemini_yang_terpotong_tidak_menghasilkan_kartu_palsu(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
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
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'));

        $scan = MahjongScan::query()->firstOrFail();

        $this->assertTrue($scan->is_winning_hand);
        $this->assertSame(6, $scan->points);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://gemini.test/v1beta/models/gemini-cadangan:generateContent');
    }

    public function test_pesan_kuota_muncul_saat_semua_model_kena_limit(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
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
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
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
    private function buatScan(Pengguna $pengguna, array $tiles, string $mode = MahjongCombinationService::MODE_CHICKEN): MahjongScan
    {
        $hasil = app(MahjongCombinationService::class)->analisa($tiles, $mode);

        return MahjongScan::create([
            'id_pengguna' => (string) $pengguna->id,
            'tiles' => $hasil['tiles'],
            'combinations' => $hasil['combinations'],
            'unmatched' => $hasil['unmatched'],
            'message' => $hasil['message'],
            'is_winning_hand' => $hasil['is_winning_hand'],
            'points' => $hasil['points'],
            'scoring_mode' => $hasil['scoring_mode'],
            'faan' => $hasil['faan'],
            'faan_details' => $hasil['faan_details'],
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

    private function kodeBookingUntuk(
        Pengguna $pengguna,
        string $kode = 'BKG-POINT-TEST',
        ?string $tanggal = null,
        ?string $jamMulai = null,
        ?string $jamSelesai = null,
    ): string {
        $sekarang = Carbon::now('Asia/Jakarta');

        Booking::create([
            'kode_booking' => $kode,
            'id_pengguna' => (string) $pengguna->id,
            'id_meja' => '1',
            'jam_mulai' => $jamMulai ?? $sekarang->copy()->subHour()->format('H:i'),
            'jam_selesai' => $jamSelesai ?? $sekarang->format('H:i'),
            'tgl_main' => $tanggal ?? $sekarang->toDateString(),
            'tgl_booking' => now()->toDateString(),
            'sistem_pembayaran' => 'transfer',
            'harga' => '50000',
            'total_harga' => '50000',
            'bukti_pembayaran' => '',
            'status_booking' => '100',
            'status_pembayaran' => 'lunas',
            'status_hapus' => '0',
        ]);

        return $kode;
    }

    public function test_cek_booking_menolak_kode_yang_tidak_dikenal(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => 'BKG-TIDAK-ADA'])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_cek_booking_menerima_kode_milik_pengguna_lain(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $penggunaLain = $this->buatPengguna('PG-POINT02', 'point2@example.com');
        $kode = $this->kodeBookingUntuk($penggunaLain);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_pengguna_lain_bisa_scan_memakai_kode_booking_teman_satu_meja(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        config(['services.openai.key' => null, 'services.gemini.key' => null]);

        $pengguna = $this->buatPengguna();
        $penggunaLain = $this->buatPengguna('PG-POINT02', 'point2@example.com');
        $kode = $this->kodeBookingUntuk($penggunaLain);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'kode_booking' => $kode,
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'seat_wind' => 'z1',
            ])
            ->assertRedirect(route('point'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, MahjongScan::query()->count());
        $this->assertSame(
            (string) $pengguna->id,
            (string) MahjongScan::query()->firstOrFail()->id_pengguna,
        );
    }

    public function test_cek_booking_menerima_scan_sampai_tiga_puluh_menit_setelah_jam_selesai(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '09:00', '10:00');

        $this->travelTo(Carbon::parse('2026-11-20 10:30:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_cek_booking_menolak_scan_lewat_tiga_puluh_menit_setelah_jam_selesai(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '09:00', '10:00');

        $this->travelTo(Carbon::parse('2026-11-20 10:31:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_cek_booking_menolak_scan_sebelum_jam_main_dimulai(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '18:00', '19:00');
        $this->kodeBookingUntuk($pengguna, $kode, '2026-11-20', '19:00', '20:00');

        $this->travelTo(Carbon::parse('2026-11-20 17:00:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertStatus(422)
            ->assertJson([
                'valid' => false,
                'message' => 'Scan point baru bisa dilakukan mulai jam 18:00 WIB sesuai jadwal booking.',
            ]);
    }

    public function test_cek_booking_menerima_scan_tepat_saat_jam_main_dimulai(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '18:00', '19:00');
        $this->kodeBookingUntuk($pengguna, $kode, '2026-11-20', '19:00', '20:00');

        $this->travelTo(Carbon::parse('2026-11-20 18:00:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_cek_booking_menerima_kode_beberapa_sesi_pada_sesi_kedua(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '18:00', '19:00');
        $this->kodeBookingUntuk($pengguna, $kode, '2026-11-20', '19:00', '20:00');

        $this->travelTo(Carbon::parse('2026-11-20 19:05:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_cek_booking_beberapa_sesi_memakai_jam_selesai_paling_akhir(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        // Sesi kedua disimpan lebih dulu untuk memastikan urutan id tidak
        // menentukan batas waktu scan.
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '19:00', '20:00');
        $this->kodeBookingUntuk($pengguna, $kode, '2026-11-20', '18:00', '19:00');

        $this->travelTo(Carbon::parse('2026-11-20 19:45:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_cek_booking_beberapa_sesi_tetap_berakhir_tiga_puluh_menit_setelah_sesi_terakhir(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-20', '18:00', '19:00');
        $this->kodeBookingUntuk($pengguna, $kode, '2026-11-20', '19:00', '20:00');

        $this->travelTo(Carbon::parse('2026-11-20 20:31:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_cek_booking_menolak_kode_dari_hari_sebelumnya(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-19', '09:00', '10:00');

        $this->travelTo(Carbon::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_cek_booking_menolak_scan_sebelum_hari_booking(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-26', '14:00', '15:00');

        // Hari Rabu, booking baru hari Kamis.
        $this->travelTo(Carbon::parse('2026-11-25 10:00:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson(route('point.cek-booking'), ['kode_booking' => $kode])
            ->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_scan_menolak_kode_booking_yang_sudah_lewat(): void
    {
        $this->seed(FaanRuleSeeder::class);

        Storage::fake('public');
        $this->fakeCloudinary();
        $pengguna = $this->buatPengguna();
        $kode = $this->kodeBookingUntuk($pengguna, 'BKG-POINT-TEST', '2026-11-18', '09:00', '10:00');

        $this->travelTo(Carbon::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'kode_booking' => $kode,
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'seat_wind' => 'z1',
            ])
            ->assertSessionHasErrors('kode_booking');

        $this->assertSame(0, MahjongScan::query()->count());
    }

    public function test_service_memakai_nilai_faan_hand_dari_admin(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $aturan = Aturanmahjong::query()->where('kode', 'AM001')->firstOrFail();
        Faanhand::query()
            ->where('id_aturanmahjong', (string) $aturan->id)
            ->where('inggris', 'All chow')
            ->update(['faan' => 5]);

        $hasil = app(MahjongCombinationService::class)->analisa(self::WINNING_TILES);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(18, $hasil['points']);
    }

    public function test_service_memakai_minimal_dan_batas_faan_dari_admin(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $aturan = Aturanmahjong::query()->where('kode', 'AM001')->firstOrFail();
        $dasar = Aturandasarfaan::query()->where('id_aturanmahjong', (string) $aturan->id);

        $dasar->update(['minimal_faan' => '2', 'batas_faan' => '3']);

        $tiles = [
            'w1', 'w1', 'w3', 'w3', 't2', 't2', 't7', 't7', 'b4', 'b4', 'b9', 'b9', 'z5', 'z5',
        ];
        $hasil = app(MahjongCombinationService::class)->analisa($tiles, MahjongCombinationService::MODE_HK);

        $this->assertTrue($hasil['is_winning_hand']);
        $this->assertSame(3, $hasil['faan']);
        $this->assertSame(3, $hasil['points']);

        $dasar->update(['minimal_faan' => '5', 'batas_faan' => '13']);

        $hasil = app(MahjongCombinationService::class)->analisa($tiles, MahjongCombinationService::MODE_HK);

        $this->assertFalse($hasil['is_winning_hand']);
        $this->assertSame(4, $hasil['faan']);
        $this->assertStringContainsString('minimal 5 faan', $hasil['message']);
    }

    public function test_service_tanpa_aturan_aktif_menolak_perhitungan(): void
    {
        $hasil = app(MahjongCombinationService::class)->analisa(self::WINNING_TILES);

        $this->assertFalse($hasil['is_winning_hand']);
        $this->assertSame(0, $hasil['points']);
        $this->assertSame(
            'Aturan point belum dikonfigurasi. Aktifkan satu aturan mahjong dan lengkapi aturan dasar faan serta faan hand di admin.',
            $hasil['message'],
        );
    }

    public function test_halaman_point_menandai_aturan_point_belum_tersedia(): void
    {
        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('point'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Point')
                ->where('aturanPoint.tersedia', false)
                ->where('hkMinFaan', 0)
                ->where('hkMaxFaan', 0)
            );
    }

    public function test_halaman_point_mengirim_ubin_kemenangan_ke_ui(): void
    {
        $this->seed(FaanRuleSeeder::class);

        $pengguna = $this->buatPengguna();
        $scan = $this->buatScan($pengguna, self::WINNING_TILES);
        $scan->update(['winning_tile' => 't9']);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('point'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Point')
                ->where('pendingScan.id', $scan->id)
                ->where('pendingScan.winning_tile', 't9')
            );
    }

    public function test_scan_tanpa_aturan_aktif_ditolak_sebelum_menyimpan_scan(): void
    {
        Storage::fake('public');
        $this->fakeCloudinary();
        config(['services.openai.key' => null, 'services.gemini.key' => null]);
        Http::preventStrayRequests();

        $pengguna = $this->buatPengguna();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('point.scan'), [
                'image' => UploadedFile::fake()->image('susunan.jpg'),
                'kode_booking' => $this->kodeBookingUntuk($pengguna),
                'seat_wind' => 'z1',
            ])
            ->assertSessionHas(
                'error',
                'Aturan point belum dikonfigurasi. Aktifkan satu aturan mahjong dan lengkapi aturan dasar faan serta faan hand di admin.',
            );

        $this->assertSame(0, MahjongScan::query()->count());
    }
}
