<?php

namespace Tests\Feature;

use App\Models\Aturanpointvenue;
use App\Models\Mahjongfaanrules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMahjongFaanRuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    private function buatAturanPoint(string $nama = 'Bonus Faan Dasar'): Aturanpointvenue
    {
        return Aturanpointvenue::create([
            'nama_aturan' => $nama,
            'slug' => strtolower(str_replace(' ', '-', $nama)),
            'deskripsi' => 'Deskripsi aturan point.',
            'min_faan' => '0',
            'max_faan' => '3',
            'status' => 'aktif',
        ]);
    }

    private function buatFaanRule(Aturanpointvenue $aturanPoint, array $data = []): Mahjongfaanrules
    {
        return Mahjongfaanrules::create([
            'id_aturanpoint' => (string) $aturanPoint->id,
            'kode' => 'HK001',
            'nama' => '清一色 (Full Flush)',
            'faan_value' => '10',
            ...$data,
        ]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $this->get('/admin/mahjong-faan-rules')->assertRedirect(route('admin.login'));
    }

    public function test_admin_bisa_melihat_daftar_mahjong_faan_rule(): void
    {
        $aturanPoint = $this->buatAturanPoint();
        $this->buatFaanRule($aturanPoint);

        $this->admin()
            ->get('/admin/mahjong-faan-rules')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MahjongFaanRule/Index')
                ->has('mahjongFaanRules.data', 1)
                ->where('mahjongFaanRules.data.0.kode', 'HK001')
                ->where('mahjongFaanRules.data.0.nama', '清一色 (Full Flush)')
                ->where('mahjongFaanRules.data.0.aturan_point.nama_aturan', 'Bonus Faan Dasar')
                ->has('aturanPoints', 1)
                ->where('aturanPoints.0.nama_aturan', 'Bonus Faan Dasar')
            );
    }

    public function test_admin_bisa_menambahkan_mahjong_faan_rule(): void
    {
        $aturanPoint = $this->buatAturanPoint();

        $this->admin()
            ->post('/admin/mahjong-faan-rules', [
                'id_aturanpoint' => $aturanPoint->id,
                'kode' => 'HK002',
                'nama' => '對對胡 (All Pungs)',
                'faan_value' => 3,
            ])
            ->assertRedirect(route('admin.mahjong-faan-rules.index'))
            ->assertSessionHas('success');

        $faanRule = Mahjongfaanrules::query()->firstOrFail();

        $this->assertSame((string) $aturanPoint->id, $faanRule->id_aturanpoint);
        $this->assertSame('HK002', $faanRule->kode);
        $this->assertSame('對對胡 (All Pungs)', $faanRule->nama);
        $this->assertSame('3', $faanRule->faan_value);
    }

    public function test_semua_field_mahjong_faan_rule_wajib_diisi(): void
    {
        $this->admin()
            ->post('/admin/mahjong-faan-rules', [
                'id_aturanpoint' => '',
                'kode' => '',
                'nama' => '',
                'faan_value' => '',
            ])
            ->assertSessionHasErrors(['id_aturanpoint', 'kode', 'nama', 'faan_value']);

        $this->assertSame(0, Mahjongfaanrules::query()->count());
    }

    public function test_aturan_point_harus_ada_di_database(): void
    {
        $this->admin()
            ->post('/admin/mahjong-faan-rules', [
                'id_aturanpoint' => 999,
                'kode' => 'HK003',
                'nama' => '平胡 (All Chows)',
                'faan_value' => 1,
            ])
            ->assertSessionHasErrors('id_aturanpoint');

        $this->assertSame(0, Mahjongfaanrules::query()->count());
    }

    public function test_nilai_faan_harus_berupa_angka_bulat(): void
    {
        $aturanPoint = $this->buatAturanPoint();

        $this->admin()
            ->post('/admin/mahjong-faan-rules', [
                'id_aturanpoint' => $aturanPoint->id,
                'kode' => 'HK004',
                'nama' => '平胡 (All Chows)',
                'faan_value' => 'satu',
            ])
            ->assertSessionHasErrors('faan_value');
    }

    public function test_admin_bisa_memperbarui_mahjong_faan_rule(): void
    {
        $aturanPoint = $this->buatAturanPoint();
        $faanRule = $this->buatFaanRule($aturanPoint);

        $this->admin()
            ->put("/admin/mahjong-faan-rules/{$faanRule->id}", [
                'id_aturanpoint' => $aturanPoint->id,
                'kode' => 'HK010',
                'nama' => '七對 (Seven Pairs)',
                'faan_value' => 10,
            ])
            ->assertRedirect(route('admin.mahjong-faan-rules.index'))
            ->assertSessionHasNoErrors();

        $faanRule->refresh();

        $this->assertSame('HK010', $faanRule->kode);
        $this->assertSame('七對 (Seven Pairs)', $faanRule->nama);
        $this->assertSame('10', $faanRule->faan_value);
    }

    public function test_admin_bisa_menghapus_mahjong_faan_rule(): void
    {
        $aturanPoint = $this->buatAturanPoint();
        $faanRule = $this->buatFaanRule($aturanPoint);

        $this->admin()
            ->delete("/admin/mahjong-faan-rules/{$faanRule->id}")
            ->assertRedirect(route('admin.mahjong-faan-rules.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mahjongfaanrules', ['id' => $faanRule->id]);
    }

    public function test_pencarian_mahjong_faan_rule_berdasarkan_kode_nama_dan_aturan_point(): void
    {
        $aturanPoint = $this->buatAturanPoint();
        $aturanLain = $this->buatAturanPoint('Aturan Turnamen');
        $this->buatFaanRule($aturanPoint);
        $this->buatFaanRule($aturanLain, [
            'kode' => 'TN001',
            'nama' => 'Aturan Turnamen Khusus',
            'faan_value' => '5',
        ]);

        $this->admin()
            ->get('/admin/mahjong-faan-rules?search=HK001')
            ->assertInertia(fn (Assert $page) => $page
                ->has('mahjongFaanRules.data', 1)
                ->where('mahjongFaanRules.data.0.kode', 'HK001')
            );

        $this->admin()
            ->get('/admin/mahjong-faan-rules?search=Khusus')
            ->assertInertia(fn (Assert $page) => $page
                ->has('mahjongFaanRules.data', 1)
                ->where('mahjongFaanRules.data.0.kode', 'TN001')
            );

        $this->admin()
            ->get('/admin/mahjong-faan-rules?search=Turnamen')
            ->assertInertia(fn (Assert $page) => $page
                ->has('mahjongFaanRules.data', 1)
                ->where('mahjongFaanRules.data.0.kode', 'TN001')
            );
    }
}
