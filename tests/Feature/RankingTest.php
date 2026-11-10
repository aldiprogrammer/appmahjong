<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use App\Models\Rank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $kodeUser, string $namaDepan = 'Pemain', ?string $avatar = null): Pengguna
    {
        return Pengguna::create([
            'kode_user' => $kodeUser,
            'nama_depan' => $namaDepan,
            'nama_balakang' => 'Mahjong',
            'email' => strtolower($kodeUser).'@example.test',
            'nowa' => '08120000'.random_int(1000, 9999),
            'password' => bcrypt('password'),
            'status' => 'aktif',
            'avatar' => $avatar,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/ranking')->assertRedirect(route('login'));
    }

    public function test_halaman_ranking_menjumlahkan_point_per_pengguna_dan_mengurutkannya(): void
    {
        $pemainA = $this->buatPengguna('PG-RANK01', 'Andi', 'https://example.test/andi.jpg');
        $pemainB = $this->buatPengguna('PG-RANK02', 'Bunga');
        $this->buatPengguna('PG-RANK03', 'Candra');

        Rank::create(['kode_user' => $pemainA->kode_user, 'point' => '20']);
        Rank::create(['kode_user' => $pemainA->kode_user, 'point' => '25']);
        Rank::create(['kode_user' => $pemainB->kode_user, 'point' => '30']);
        Rank::create(['kode_user' => 'PG-TIDAK-ADA', 'point' => '99']);

        $this->withSession(['pengguna_id' => $pemainA->id])
            ->get('/ranking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Ranking')
                ->has('ranking', 2)
                ->where('ranking.0.kode_user', 'PG-RANK01')
                ->where('ranking.0.nama', 'Andi Mahjong')
                ->where('ranking.0.point', 45)
                ->where('ranking.0.peringkat', 1)
                ->where('ranking.0.saya', true)
                ->where('ranking.1.kode_user', 'PG-RANK02')
                ->where('ranking.1.point', 30)
                ->where('ranking.1.peringkat', 2)
                ->where('peringkatSaya.kode_user', 'PG-RANK01')
                ->where('peringkatSaya.peringkat', 1)
                ->where('ringkasan.total_pemain', 2)
                ->where('ringkasan.total_point', 75)
                ->where('ringkasan.point_tertinggi', 45)
            );
    }

    public function test_halaman_ranking_tetap_tampil_saat_belum_ada_data(): void
    {
        $pengguna = $this->buatPengguna('PG-RANK04', 'Dewi');

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get('/ranking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Ranking')
                ->has('ranking', 0)
                ->where('peringkatSaya', null)
                ->where('ringkasan.total_pemain', 0)
                ->where('ringkasan.total_point', 0)
                ->where('ringkasan.point_tertinggi', 0)
            );
    }
}
