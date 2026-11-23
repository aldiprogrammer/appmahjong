<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\NotifikasiBooking;
use App\Models\Pengguna;
use App\Models\Vanue;
use App\Services\NotifikasiBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotifikasiBookingTest extends TestCase
{
    use RefreshDatabase;

    private function buatVanue(string $nama = 'Mahjong Center Medan'): Vanue
    {
        return Vanue::create([
            'nama' => $nama,
            'alamat' => 'Jl. Contoh No. 1',
            'logo' => 'vanues/medan.png',
        ]);
    }

    private function buatAdmin(?Vanue $vanue, string $role = 'admin', string $username = 'admin_venue'): Admin
    {
        return Admin::create([
            'username' => $username,
            'role' => $role,
            'vanue_id' => $vanue?->id,
            'hak_akses' => null,
            'password' => 'password123',
        ]);
    }

    /**
     * @return array{0: Pengguna, 1: Meja, 2: jam_main, 3: Carbon}
     */
    private function siapkanJadwal(Vanue $vanue): array
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-NOTIF01',
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => 'aldi-notif@example.com',
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $meja = Meja::create([
            'kode_meja' => 'MJ-01',
            'nama_meja' => 'Meja 01',
            'vanue_id' => $vanue->id,
        ]);
        $jam = jam_main::create(['jam_mulai' => '10:00', 'jam_selesai' => '11:00', 'status' => 'aktif']);
        $tanggal = Carbon::tomorrow();

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        hargajammain::create([
            'jam_main_id' => (string) $jam->id,
            'hari' => $hari,
            'harga' => '50000',
            'diskon' => '0',
            'harga_akhir' => '50000',
            'vanue_id' => $vanue->id,
        ]);

        return [$pengguna, $meja, $jam, $tanggal];
    }

    public function test_booking_masuk_membuat_notifikasi_untuk_venue_meja(): void
    {
        $this->fakeCloudinary();

        $vanue = $this->buatVanue();
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal($vanue);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ])->assertRedirect();

        $kodePembayaran = Str::afterLast((string) $response->headers->get('Location'), '/');

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('riwayat'));

        $notifikasi = NotifikasiBooking::query()->firstOrFail();

        $this->assertSame($vanue->id, $notifikasi->vanue_id);
        $this->assertSame(NotifikasiBooking::TIPE_BOOKING, $notifikasi->tipe);
        $this->assertSame('Booking baru masuk', $notifikasi->judul);
        $this->assertStringContainsString('Meja 01', $notifikasi->pesan);
        $this->assertNotNull($notifikasi->kode_booking);
        $this->assertFalse($notifikasi->dibaca);
        $this->assertDatabaseHas('notifikasi_bookings', [
            'kode_booking' => Booking::query()->firstOrFail()->kode_booking,
        ]);
    }

    public function test_admin_venue_hanya_menerima_notifikasi_venue_sendiri(): void
    {
        $vanueA = $this->buatVanue('Vanue A');
        $vanueB = $this->buatVanue('Vanue B');
        $admin = $this->buatAdmin($vanueA);

        NotifikasiBooking::create([
            'vanue_id' => $vanueA->id,
            'judul' => 'Booking baru masuk',
            'pesan' => 'Booking untuk venue A.',
            'tipe' => NotifikasiBooking::TIPE_BOOKING,
        ]);
        NotifikasiBooking::create([
            'vanue_id' => $vanueB->id,
            'judul' => 'Booking baru masuk',
            'pesan' => 'Booking untuk venue B.',
            'tipe' => NotifikasiBooking::TIPE_BOOKING,
        ]);

        $this->withSession(['admin_id' => $admin->id])
            ->getJson('/admin/notifikasi')
            ->assertOk()
            ->assertJsonPath('belum_dibaca', 1)
            ->assertJsonCount(1, 'notifikasi')
            ->assertJsonPath('notifikasi.0.pesan', 'Booking untuk venue A.');
    }

    public function test_super_admin_menerima_notifikasi_semua_venue(): void
    {
        $vanueA = $this->buatVanue('Vanue A');
        $vanueB = $this->buatVanue('Vanue B');
        $admin = $this->buatAdmin(null, Admin::ROLE_SUPER_ADMIN, 'super_admin');

        foreach ([$vanueA, $vanueB] as $vanue) {
            NotifikasiBooking::create([
                'vanue_id' => $vanue->id,
                'judul' => 'Booking baru masuk',
                'pesan' => 'Booking untuk '.$vanue->nama.'.',
                'tipe' => NotifikasiBooking::TIPE_BOOKING,
            ]);
        }

        $this->withSession(['admin_id' => $admin->id])
            ->getJson('/admin/notifikasi')
            ->assertOk()
            ->assertJsonPath('belum_dibaca', 2)
            ->assertJsonCount(2, 'notifikasi');
    }

    public function test_admin_bisa_menandai_notifikasi_sendiri_sebagai_dibaca(): void
    {
        $vanue = $this->buatVanue();
        $admin = $this->buatAdmin($vanue);

        $notifikasi = NotifikasiBooking::create([
            'vanue_id' => $vanue->id,
            'judul' => 'Booking baru masuk',
            'pesan' => 'Ada booking baru.',
            'tipe' => NotifikasiBooking::TIPE_BOOKING,
        ]);

        $this->withSession(['admin_id' => $admin->id])
            ->postJson("/admin/notifikasi/{$notifikasi->id}/dibaca")
            ->assertOk();

        $this->assertTrue($notifikasi->refresh()->dibaca);

        $this->withSession(['admin_id' => $admin->id])
            ->postJson('/admin/notifikasi/dibaca-semua')
            ->assertOk();
    }

    public function test_admin_tidak_bisa_menandai_notifikasi_venue_lain(): void
    {
        $vanueA = $this->buatVanue('Vanue A');
        $vanueB = $this->buatVanue('Vanue B');
        $admin = $this->buatAdmin($vanueA);

        $notifikasi = NotifikasiBooking::create([
            'vanue_id' => $vanueB->id,
            'judul' => 'Booking baru masuk',
            'pesan' => 'Booking untuk venue B.',
            'tipe' => NotifikasiBooking::TIPE_BOOKING,
        ]);

        $this->withSession(['admin_id' => $admin->id])
            ->postJson("/admin/notifikasi/{$notifikasi->id}/dibaca")
            ->assertForbidden();

        $this->assertFalse($notifikasi->refresh()->dibaca);
    }

    public function test_push_onesignal_dikirim_ke_venue_terkait_bila_dikonfigurasi(): void
    {
        config([
            'services.onesignal.app_id' => 'app-id-contoh',
            'services.onesignal.rest_api_key' => 'rest-key-contoh',
        ]);
        Http::fake();

        $vanue = $this->buatVanue();

        app(NotifikasiBookingService::class)->catatBookingMasuk(
            vanueId: $vanue->id,
            judul: 'Booking baru masuk',
            pesan: 'Ada booking baru untuk meja 01.',
            kodeBooking: 'BK-TEST-001',
        );

        Http::assertSent(function ($request) use ($vanue): bool {
            return $request->url() === 'https://api.onesignal.com/notifications'
                && $request->hasHeader('Authorization', 'Key rest-key-contoh')
                && $request['app_id'] === 'app-id-contoh'
                && $request['filters'][0]['key'] === 'vanue_id'
                && $request['filters'][0]['value'] === (string) $vanue->id;
        });
    }
}
