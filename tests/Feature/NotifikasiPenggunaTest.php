<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\NotifikasiPengguna;
use App\Models\Pengguna;
use App\Services\NotifikasiPenggunaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotifikasiPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(string $kode = 'PG-NOTIF-USER', string $email = 'user-notif@example.com'): Pengguna
    {
        return Pengguna::create([
            'kode_user' => $kode,
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => $email,
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);
    }

    private function buatBooking(Pengguna $pengguna, string $kodeBooking = 'BK-NOTIF-001'): Booking
    {
        return Booking::create([
            'kode_booking' => $kodeBooking,
            'kode_pembayaran' => 'PAY-'.$kodeBooking,
            'id_pengguna' => (string) $pengguna->id,
            'id_meja' => '1',
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'tgl_main' => now()->toDateString(),
            'tgl_booking' => now()->toDateString(),
            'sistem_pembayaran' => 'transfer',
            'harga' => '50000',
            'total_harga' => '50000',
            'bukti_pembayaran' => '',
            'status_booking' => '100',
            'status_pembayaran' => 'belum_bayar',
            'status_hapus' => '0',
        ]);
    }

    public function test_approve_booking_membuat_notifikasi_untuk_pengguna_booking(): void
    {
        $pengguna = $this->buatPengguna();
        $booking = $this->buatBooking($pengguna);

        $this->withSession(['admin_id' => 1])
            ->patch(route('admin.bookings.approve', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('notifikasi_penggunas', [
            'id_pengguna' => (string) $pengguna->id,
            'judul' => 'Booking disetujui',
            'kode_booking' => $booking->kode_booking,
            'dibaca' => false,
        ]);
    }

    public function test_reject_booking_membuat_notifikasi_untuk_pengguna_booking(): void
    {
        $pengguna = $this->buatPengguna();
        $booking = $this->buatBooking($pengguna);

        $this->withSession(['admin_id' => 1])
            ->patch(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('notifikasi_penggunas', [
            'id_pengguna' => (string) $pengguna->id,
            'judul' => 'Booking ditolak',
            'kode_booking' => $booking->kode_booking,
        ]);
    }

    public function test_pengguna_hanya_menerima_notifikasi_miliknya(): void
    {
        $pengguna = $this->buatPengguna();
        $penggunaLain = $this->buatPengguna('PG-LAIN', 'lain-notif@example.com');

        NotifikasiPengguna::create([
            'id_pengguna' => (string) $pengguna->id,
            'judul' => 'Booking disetujui',
            'pesan' => 'Booking kamu disetujui.',
        ]);
        NotifikasiPengguna::create([
            'id_pengguna' => (string) $penggunaLain->id,
            'judul' => 'Booking ditolak',
            'pesan' => 'Booking kamu ditolak.',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->getJson('/notifikasi-pengguna')
            ->assertOk()
            ->assertJsonPath('belum_dibaca', 1)
            ->assertJsonCount(1, 'notifikasi')
            ->assertJsonPath('notifikasi.0.pesan', 'Booking kamu disetujui.');
    }

    public function test_pengguna_tidak_bisa_menandai_notifikasi_pengguna_lain(): void
    {
        $pengguna = $this->buatPengguna();
        $penggunaLain = $this->buatPengguna('PG-LAIN', 'lain-notif@example.com');

        $notifikasi = NotifikasiPengguna::create([
            'id_pengguna' => (string) $penggunaLain->id,
            'judul' => 'Booking disetujui',
            'pesan' => 'Booking kamu disetujui.',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson("/notifikasi-pengguna/{$notifikasi->id}/dibaca")
            ->assertForbidden();

        $this->assertFalse($notifikasi->refresh()->dibaca);
    }

    public function test_pengguna_bisa_menandai_notifikasi_sendiri_sebagai_dibaca(): void
    {
        $pengguna = $this->buatPengguna();

        $notifikasi = NotifikasiPengguna::create([
            'id_pengguna' => (string) $pengguna->id,
            'judul' => 'Booking disetujui',
            'pesan' => 'Booking kamu disetujui.',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson("/notifikasi-pengguna/{$notifikasi->id}/dibaca")
            ->assertOk();

        $this->assertTrue($notifikasi->refresh()->dibaca);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->postJson('/notifikasi-pengguna/dibaca-semua')
            ->assertOk();
    }

    public function test_push_pengguna_dikirim_lewat_external_id_bila_dikonfigurasi(): void
    {
        config([
            'services.onesignal.app_id' => 'app-id-contoh',
            'services.onesignal.rest_api_key' => 'rest-key-contoh',
        ]);
        Http::fake();

        $pengguna = $this->buatPengguna();

        app(NotifikasiPenggunaService::class)->catat(
            $pengguna->id,
            'Booking disetujui',
            'Booking kamu telah disetujui.',
            'BK-PUSH-001',
        );

        Http::assertSent(function ($request) use ($pengguna): bool {
            return $request->url() === 'https://api.onesignal.com/notifications'
                && $request->hasHeader('Authorization', 'Key rest-key-contoh')
                && $request['app_id'] === 'app-id-contoh'
                && $request['include_aliases']['external_id'][0] === 'pengguna-'.$pengguna->id;
        });
    }
}
