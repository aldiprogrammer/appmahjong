<?php

namespace Tests\Feature;

use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMejaQrCodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withSession(['admin_id' => 1]);
    }

    public function test_tamu_diarahkan_ke_halaman_login_admin(): void
    {
        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);

        $this->get("/admin/mejas/{$meja->id}/qr-code")->assertRedirect(route('admin.login'));
        $this->get('/admin/mejas/qr-code/semua')->assertRedirect(route('admin.login'));
    }

    public function test_admin_bisa_melihat_gambar_qr_code_meja(): void
    {
        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);

        $response = $this->admin()->get("/admin/mejas/{$meja->id}/qr-code/gambar");

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    public function test_admin_bisa_mengunduh_qr_code_per_meja_dalam_pdf(): void
    {
        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);

        $response = $this->admin()->get("/admin/mejas/{$meja->id}/qr-code");

        $response->assertOk();
        $response->assertDownload('qr-meja-MJ-01.pdf');
    }

    public function test_admin_bisa_mengunduh_semua_qr_code_dalam_satu_pdf(): void
    {
        Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 1']);
        Meja::create(['kode_meja' => 'MJ-02', 'nama_meja' => 'Meja 2']);

        $response = $this->admin()->get('/admin/mejas/qr-code/semua');

        $response->assertOk();
        $response->assertDownload('qr-code-semua-meja.pdf');
    }

    public function test_unduh_semua_qr_tanpa_data_meja_kembali_dengan_pesan_kesalahan(): void
    {
        $this->admin()
            ->get('/admin/mejas/qr-code/semua')
            ->assertRedirect(route('admin.mejas.index'))
            ->assertSessionHas('error');
    }
}
