<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\Pengguna;
use App\Models\Settingbookingroom;
use App\Models\Statusvoucher;
use App\Models\Voucher;
use App\Services\BuktiPembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_booking_with_voucher_rejects_fewer_selected_hours(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 2);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->from(route('booking'))
            ->post('/booking', [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
                'kode_voucher' => $voucher->kode_voucher,
            ]);

        $response->assertRedirect(route('booking'));
        $response->assertSessionHasErrors([
            'kode_voucher' => 'Jumlah jam yang Anda pilih kurang atau lebih dari jumlah jam yang sudah ditentukan untuk voucher ini.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_with_voucher_rejects_more_selected_hours(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $jamKetiga = jam_main::create(['jam_mulai' => '12:00', 'jam_selesai' => '13:00', 'status' => 'aktif']);
        $voucher = $this->buatVoucher($pengguna, 2);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->from(route('booking'))
            ->post('/booking', [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id, (string) $jamKetiga->id],
                'tgl_main' => $tanggal->toDateString(),
                'kode_voucher' => $voucher->kode_voucher,
            ]);

        $response->assertRedirect(route('booking'));
        $response->assertSessionHasErrors([
            'kode_voucher' => 'Jumlah jam yang Anda pilih kurang atau lebih dari jumlah jam yang sudah ditentukan untuk voucher ini.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
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
        $response->assertSessionHas('booking_success', true);

        $bookings = Booking::query()->where('kode_pembayaran', $kodePembayaran)->get();

        $this->assertCount(2, $bookings);
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->status_pembayaran === 'menunggu_verifikasi'));
        $this->assertTrue($bookings->every(fn (Booking $booking) => $booking->sistem_pembayaran === 'transfer'));
        Storage::disk('public')->assertExists($bookings->first()->bukti_pembayaran);
        Storage::disk('public')->assertExists(
            app(BuktiPembayaranService::class)->pathThumbnail($bookings->first()->bukti_pembayaran),
        );
    }

    public function test_cash_payment_is_no_longer_accepted(): void
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
        ])->assertSessionHasErrors(['sistem_pembayaran']);

        $booking->refresh();

        $this->assertSame('belum_bayar', $booking->status_pembayaran);
        $this->assertEmpty($booking->sistem_pembayaran);
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

    public function test_admin_deleted_booking_hides_it_and_releases_the_time_slot(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $bookingData = [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ];

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $booking = Booking::query()->firstOrFail();

        $this->withSession(['admin_id' => 1])
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertRedirect();

        $booking->refresh();
        $this->assertSame('1', $booking->status_hapus);

        $this->withSession(['admin_id' => 1])
            ->get(route('admin.bookings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Booking/Index')
                ->has('bookings.data', 0));

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $bookings = Booking::query()->where('tgl_main', $tanggal->toDateString())->get();
        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();

        $response->assertRedirect(route('booking.pembayaran', $newBooking->kode_pembayaran));
        $this->assertCount(2, $bookings);
        $this->assertSame('0', $newBooking->status_hapus);
    }

    public function test_reapproving_voucher_booking_marks_voucher_as_used_again(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
                'kode_voucher' => $voucher->kode_voucher,
            ]);

        $booking = Booking::query()->firstOrFail();
        $adminSession = ['admin_id' => 1];

        $this->withSession($adminSession)
            ->patch(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $voucher->refresh();
        $this->assertSame('', $voucher->tanggal_digunakan);

        $this->withSession($adminSession)
            ->patch(route('admin.bookings.approve', $booking))
            ->assertRedirect();

        $booking->refresh();
        $voucher->refresh();

        $this->assertSame('200', $booking->status_booking);
        $this->assertSame(Carbon::today()->toDateString(), $voucher->tanggal_digunakan);
    }

    public function test_deleting_voucher_booking_rejects_it_and_allows_voucher_to_be_used_again(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);
        $bookingData = [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'kode_voucher' => $voucher->kode_voucher,
        ];

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $booking = Booking::query()->firstOrFail();

        $this->withSession(['admin_id' => 1])
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertRedirect();

        $booking->refresh();
        $voucher->refresh();

        $this->assertSame('400', $booking->status_booking);
        $this->assertSame('1', $booking->status_hapus);
        $this->assertSame('', $voucher->tanggal_digunakan);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();
        $voucher->refresh();

        $response->assertRedirect(route('booking.pembayaran', $newBooking->kode_pembayaran));
        $this->assertSame($voucher->kode_voucher, $newBooking->kode_voucher);
        $this->assertSame(Carbon::today()->toDateString(), $voucher->tanggal_digunakan);
    }

    public function test_deleting_unapproved_voucher_booking_from_riwayat_allows_voucher_to_be_used_again(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);
        $bookingData = [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'kode_voucher' => $voucher->kode_voucher,
        ];

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(Carbon::today()->toDateString(), $voucher->refresh()->tanggal_digunakan);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('riwayat.destroy', $booking))
            ->assertRedirect();

        $this->assertSame('1', $booking->refresh()->status_hapus);
        $this->assertSame('', $voucher->refresh()->tanggal_digunakan);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $bookingData);

        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();
        $voucher->refresh();

        $response->assertRedirect(route('booking.pembayaran', $newBooking->kode_pembayaran));
        $this->assertSame($voucher->kode_voucher, $newBooking->kode_voucher);
        $this->assertSame(Carbon::today()->toDateString(), $voucher->tanggal_digunakan);
    }

    public function test_deleting_one_voucher_booking_keeps_voucher_used_while_other_booking_remains(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
                'kode_voucher' => $voucher->kode_voucher,
            ]);

        $booking = Booking::query()->firstOrFail();

        // Simulasikan booking aktif lain yang masih memakai voucher yang sama.
        Booking::create([
            'kode_booking' => 'BK-TEST-LAIN',
            'kode_pembayaran' => $booking->kode_pembayaran,
            'kode_voucher' => $voucher->kode_voucher,
            'tipe_booking' => 'reguler',
            'id_pengguna' => (string) $pengguna->id,
            'id_meja' => (string) $meja->id,
            'jam_mulai' => '13:00',
            'jam_selesai' => '14:00',
            'tgl_main' => $tanggal->toDateString(),
            'tgl_booking' => Carbon::today()->toDateString(),
            'sistem_pembayaran' => '',
            'harga' => '50000',
            'total_harga' => '50000',
            'bukti_pembayaran' => '',
            'status_booking' => '0',
            'status_pembayaran' => 'belum_bayar',
            'status_hapus' => '0',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('riwayat.destroy', $booking))
            ->assertRedirect();

        $this->assertSame(Carbon::today()->toDateString(), $voucher->refresh()->tanggal_digunakan);
    }

    public function test_user_can_delete_one_session_and_total_is_recalculated(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $bookings = Booking::query()->orderBy('jam_mulai')->get();
        $kodePembayaran = $bookings->first()->kode_pembayaran;
        $dihapus = $bookings->first();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, $dihapus]))
            ->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        $this->assertSame('1', $dihapus->refresh()->status_hapus);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pembayaran')
                ->where('pembayaran.total', 50000)
                ->has('pembayaran.bookings', 1));
    }

    public function test_deleting_last_session_cancels_pembayaran_and_returns_to_riwayat(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$booking->kode_pembayaran, $booking]))
            ->assertRedirect(route('riwayat'));

        $this->assertSame('1', $booking->refresh()->status_hapus);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $booking->kode_pembayaran))
            ->assertNotFound();
    }

    public function test_user_cannot_delete_session_after_payment_is_submitted(): void
    {
        Storage::fake('public');

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->post(route('booking.pembayaran.store', $booking->kode_pembayaran), [
            'sistem_pembayaran' => 'transfer',
            'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$booking->kode_pembayaran, $booking]))
            ->assertRedirect();

        $this->assertSame('0', $booking->refresh()->status_hapus);
        $this->assertSame('menunggu_verifikasi', $booking->status_pembayaran);
    }

    public function test_user_cannot_delete_session_from_another_user_pembayaran(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-OTHER2',
            'nama_depan' => 'Lain',
            'nama_balakang' => 'Orang',
            'email' => 'lain2@example.com',
            'nowa' => '0822222222',
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
            ->delete(route('booking.pembayaran.sesi.destroy', [$booking->kode_pembayaran, $booking]))
            ->assertNotFound();

        $this->assertSame('0', $booking->refresh()->status_hapus);
    }

    public function test_booking_rejects_missing_tipe_booking_when_setting_active(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->from(route('booking'))
            ->post('/booking', [
                'meja_id' => (string) $meja->id,
                'jam_main_ids' => [(string) $jam->id],
                'tgl_main' => $tanggal->toDateString(),
            ]);

        $response->assertRedirect(route('booking'));
        $response->assertSessionHasErrors([
            'tipe_booking' => 'Pilih tipe booking terlebih dahulu.',
        ]);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_saves_tipe_booking_public_when_pembayaran_all_active(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ])->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('public', $booking->tipe_booking);
        $this->assertSame('12500', $booking->total_harga);
    }

    public function test_booking_saves_private_tipe_with_full_price(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'private',
        ])->assertRedirect();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('private', $booking->tipe_booking);
        $this->assertSame('50000', $booking->total_harga);
    }

    public function test_pengguna_lain_bisa_join_public_room(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $bookingPemilik = Booking::query()->firstOrFail();

        $this->assertNotNull($bookingPemilik->kode_room);
        $this->assertSame('12500', $bookingPemilik->total_harga);

        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-TEST02',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Tester',
            'email' => 'budi@example.com',
            'nowa' => '081234567891',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('room.booking.join', $bookingPemilik->kode_room));

        $bookingPendatang = Booking::query()
            ->where('id_pengguna', (string) $penggunaLain->id)
            ->firstOrFail();

        $response->assertRedirect(route('booking.pembayaran', $bookingPendatang->kode_pembayaran));
        $this->assertSame($bookingPemilik->kode_room, $bookingPendatang->kode_room);
        $this->assertSame('public', $bookingPendatang->tipe_booking);
        $this->assertSame('12500', $bookingPendatang->total_harga);
        $this->assertSame('belum_bayar', $bookingPendatang->status_pembayaran);
    }

    public function test_pengguna_lain_join_room_one_tanpa_bayar(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'one',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $bookingPemilik = Booking::query()->firstOrFail();
        $this->assertSame('50000', $bookingPemilik->total_harga);
        $this->assertSame('one', $bookingPemilik->mode_pembayaran);

        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-TEST03',
            'nama_depan' => 'Citra',
            'nama_balakang' => 'Tester',
            'email' => 'citra@example.com',
            'nowa' => '081234567892',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('room.booking.join', $bookingPemilik->kode_room));

        $bookingPendatang = Booking::query()
            ->where('id_pengguna', (string) $penggunaLain->id)
            ->firstOrFail();

        $response->assertRedirect(route('riwayat'));
        $this->assertSame($bookingPemilik->kode_room, $bookingPendatang->kode_room);
        $this->assertSame('0', $bookingPendatang->total_harga);
        $this->assertSame('lunas', $bookingPendatang->status_pembayaran);
        $this->assertSame('room', $bookingPendatang->sistem_pembayaran);
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

    private function buatVoucher(Pengguna $pengguna, int $jumlahJam): Voucher
    {
        $statusVoucher = Statusvoucher::create([
            'status_voucher' => 'Voucher Tes',
            'jumah_booking' => '1',
            'jumah_jam' => (string) $jumlahJam,
        ]);

        return Voucher::create([
            'kode_user' => $pengguna->kode_user,
            'kode_voucher' => 'VC-TEST-001',
            'tanggal_diterima' => Carbon::today()->toDateString(),
            'tanggal_digunakan' => '',
            'id_statusvoucher' => (string) $statusVoucher->id,
            'status' => 'aktif',
        ]);
    }
}
