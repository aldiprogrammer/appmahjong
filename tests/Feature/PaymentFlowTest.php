<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creates_pembayaran_without_payment_fields(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $response->assertRedirect(route('booking.pembayaran', $booking->kode_pembayaran));
        $this->assertNotNull($booking->kode_pembayaran);
        $this->assertSame('belum_bayar', $booking->status_pembayaran);
        $this->assertSame('', $booking->sistem_pembayaran);
        $this->assertSame('0', $booking->status_booking);
    }

    public function test_transfer_upload_marks_all_pembayaran_sessions_waiting_verification(): void
    {
        Storage::fake('public');

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = Booking::query()->firstOrFail()->kode_pembayaran;

        $response = $this->post(route('booking.pembayaran.store', $kodePembayaran), [
            'sistem_pembayaran' => 'transfer',
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $response->assertRedirect(route('riwayat'));

        $bookings = Booking::query()->where('kode_pembayaran', $kodePembayaran)->get();

        $this->assertCount(2, $bookings);
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->status_pembayaran === 'menunggu_verifikasi'));
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->sistem_pembayaran === 'transfer'));
        Storage::disk('public')->assertExists($bookings->first()->bukti_pembayaran);
    }

    public function test_cash_payment_marks_pembayaran_pay_at_venue(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->post(route('booking.pembayaran.store', $booking->kode_pembayaran), [
            'sistem_pembayaran' => 'tunai',
        ])->assertRedirect(route('riwayat'));

        $booking->refresh();

        $this->assertSame('bayar_di_tempat', $booking->status_pembayaran);
        $this->assertSame('tunai', $booking->sistem_pembayaran);
    }

    public function test_user_cannot_view_another_user_pembayaran(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-OTHER',
            'nama_depan' => 'Lain',
            'nama_balakang' => 'Orang',
            'email' => 'lain@example.com',
            'nowa' => '0811111111',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->get(route('booking.pembayaran', $booking->kode_pembayaran))
            ->assertNotFound();
    }

    public function test_admin_can_verify_pembayaran(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $booking = Booking::create([
            'kode_booking' => 'BK-TEST-0001',
            'kode_pembayaran' => 'INV-TEST-0001',
            'id_pengguna' => (string) $pengguna->id,
            'id_meja' => $meja->kode_meja,
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'tgl_main' => $tanggal->toDateString(),
            'tgl_booking' => Carbon::today()->toDateString(),
            'sistem_pembayaran' => 'transfer',
            'harga' => '50000',
            'total_harga' => '50000',
            'bukti_pembayaran' => '',
            'status_booking' => '0',
            'status_pembayaran' => 'menunggu_verifikasi',
            'status_hapus' => '0',
        ]);

        $this->withSession(['admin_id' => 1])->patch("/admin/bookings/{$booking->id}/verifikasi-pembayaran")->assertRedirect();

        $booking->refresh();

        $this->assertSame('lunas', $booking->status_pembayaran);
        $this->assertNotNull($booking->dibayar_pada);
    }

    /**
     * @return array{0: Pengguna, 1: Meja, 2: jam_main, 3: Carbon}
     */
    private function siapkanJadwal(): array
    {
        $pengguna = Pengguna::create([
            'kode_user' => 'PG-TEST01',
            'nama_depan' => 'Aldi',
            'nama_balakang' => 'Tester',
            'email' => 'aldi@example.com',
            'nowa' => '081234567890',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $meja = Meja::create(['kode_meja' => 'MJ-01', 'nama_meja' => 'Meja 01']);
        $jam = jam_main::create(['jam_mulai' => '10:00', 'jam_selesai' => '11:00', 'status' => 'aktif']);
        $tanggal = Carbon::tomorrow();

        $this->buatHarga($jam, $tanggal);

        return [$pengguna, $meja, $jam, $tanggal];
    }

    private function buatHarga(jam_main $jam, Carbon $tanggal): void
    {
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        hargajammain::create([
            'jam_main_id' => (string) $jam->id,
            'hari' => $hari,
            'harga' => '50000',
            'diskon' => '0',
            'harga_akhir' => '50000',
        ]);
    }
}
