<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\hargajammain;
use App\Models\jam_main;
use App\Models\Meja;
use App\Models\paket_hargajammain;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Settingbookingroom;
use App\Models\Statusvoucher;
use App\Models\Vanue;
use App\Models\Voucher;
use App\Services\BuktiPembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lanjut_ke_pembayaran_tidak_menyimpan_booking_ke_database(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $response->assertRedirect(route('booking.pembayaran', $kodePembayaran));
        $this->assertDatabaseCount('bookings', 0);
        $this->assertNotNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_kirim_bukti_pembayaran_menyimpan_booking_ke_database(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('riwayat'));

        $booking = Booking::query()->firstOrFail();

        $this->assertSame($kodePembayaran, $booking->kode_pembayaran);
        $this->assertSame('menunggu_verifikasi', $booking->status_pembayaran);
        $this->assertSame('transfer', $booking->sistem_pembayaran);
        $this->assertSame('0', $booking->status_booking);
        $this->assertNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_semua_sesi_satu_transaksi_memakai_kode_booking_yang_sama(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('riwayat'));

        $bookings = Booking::query()->orderBy('jam_mulai')->get();

        $this->assertCount(2, $bookings);
        $this->assertNotNull($bookings->first()->kode_booking);
        $this->assertSame(1, $bookings->pluck('kode_booking')->unique()->count());
        $this->assertSame(1, $bookings->pluck('kode_pembayaran')->unique()->count());
        $this->assertSame($kodePembayaran, $bookings->first()->kode_pembayaran);
        $this->assertSame([(string) $pengguna->id, (string) $pengguna->id], $bookings->pluck('id_pengguna')->all());
    }

    public function test_gagal_unggah_ke_cloudinary_tidak_menyimpan_booking(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);
        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Server error']], 500),
        ]);

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertSessionHas('error', 'Gagal mengunggah bukti pembayaran ke Cloudinary. Silakan coba lagi.');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertNotNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_bukti_pembayaran_tidak_valid_tidak_menyimpan_booking(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors('bukti_pembayaran');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertNotNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_pembayaran_menampilkan_harga_asli_diskon_dan_total_paket(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $jamKetiga = jam_main::create(['jam_mulai' => '12:00', 'jam_selesai' => '13:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);
        $this->buatHarga($jamKetiga, $tanggal);

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        paket_hargajammain::create([
            'vanue_id' => null,
            'hari' => $hari,
            'minimal_jam' => 3,
            'jam_mulai' => '00:00',
            'jam_selesai' => '00:00',
            'diskon_persen' => 20,
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id, (string) $jamKetiga->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pembayaran')
                ->where('pembayaran.subtotal', 150000)
                ->where('pembayaran.diskon', 30000)
                ->where('pembayaran.label_diskon', 'Diskon paket')
                ->where('pembayaran.total', 120000)
                ->has('pembayaran.bookings', 3)
                ->where('pembayaran.bookings.0.harga', 50000)
                ->where('pembayaran.bookings.0.total_harga', 40000)
                ->where('pembayaran.bookings.0.diskon_paket', 10000)
                ->where('pembayaran.bookings.0.diskon_paket_persen', 20)
                ->where('pembayaran.bookings.0.pakai_voucher', false)
                ->where('pembayaran.bookings.2.diskon_paket', 10000)
                ->where('pembayaran.bookings.2.diskon_paket_persen', 20));
    }

    public function test_metode_pembayaran_diambil_dari_tabel_pembayaran_sesuai_venue(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $vanue = Vanue::create(['nama' => 'Vanue Utama', 'alamat' => 'Jl. A', 'logo' => 'vanues/a.png']);
        $vanueLain = Vanue::create(['nama' => 'Vanue Lain', 'alamat' => 'Jl. B', 'logo' => 'vanues/b.png']);

        $meja->update(['vanue_id' => $vanue->id]);
        hargajammain::query()->update(['vanue_id' => $vanue->id]);

        Pembayaran::create([
            'id_venue' => (string) $vanue->id,
            'metode_pembayaran' => 'transfer',
            'bank' => 'BCA',
            'atas_nama' => 'Mahjong Medan',
            'norek' => '1234567890',
        ]);
        Pembayaran::create([
            'id_venue' => (string) $vanue->id,
            'metode_pembayaran' => 'qris',
            'atas_nama' => 'QRIS Mahjong Medan',
            'qris_image' => 'pembayarans/qris-utama.png',
        ]);
        Pembayaran::create([
            'id_venue' => (string) $vanueLain->id,
            'metode_pembayaran' => 'transfer',
            'bank' => 'BRI',
            'atas_nama' => 'Vanue Lain',
            'norek' => '999999',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pembayaran')
                ->has('pengaturanPembayaran.metode', 2)
                ->where('pengaturanPembayaran.metode.0.metode', 'transfer')
                ->where('pengaturanPembayaran.metode.0.bank', 'BCA')
                ->where('pengaturanPembayaran.metode.0.norek', '1234567890')
                ->where('pengaturanPembayaran.metode.1.metode', 'qris')
                ->where('pengaturanPembayaran.metode.1.atas_nama', 'QRIS Mahjong Medan')
                ->where('pengaturanPembayaran.metode.1.qris_image_url', asset('storage/pembayarans/qris-utama.png')));
    }

    public function test_user_bisa_mengirim_bukti_dengan_metode_qris(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'qris',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti-qris.png'),
            ])
            ->assertRedirect(route('riwayat'));

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('qris', $booking->refresh()->sistem_pembayaran);
        $this->assertSame('menunggu_verifikasi', $booking->status_pembayaran);
    }

    public function test_booking_ditolak_bila_nomor_whatsapp_kosong(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $pengguna->update(['nowa' => '']);

        $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ])->assertSessionHasErrors('nowa');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_bukti_pembayaran_lebih_dari_2mb_tetap_diterima(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()
                    ->image('bukti-besar.jpg', 600, 600)
                    ->size(5120),
            ])
            ->assertRedirect(route('riwayat'))
            ->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('menunggu_verifikasi', $booking->refresh()->status_pembayaran);
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
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $responseDraft = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($responseDraft);

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
        $this->assertStringStartsWith(
            'https://res.cloudinary.com/test-cloud/image/upload/',
            $bookings->first()->bukti_pembayaran,
        );
        $this->assertStringContainsString(
            'c_limit,w_420,q_auto:good',
            $bookings->first()->bukti_pembayaran_thumb_url,
        );
        Http::assertSentCount(1);
    }

    public function test_cash_payment_is_no_longer_accepted(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'tunai',
            ])
            ->assertSessionHasErrors(['sistem_pembayaran']);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertNotNull(session('booking_drafts.'.$kodePembayaran));
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

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertNotFound();
    }

    public function test_finalisasi_voucher_tanpa_metode_ditolak(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'kode_voucher' => $voucher->kode_voucher,
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [])
            ->assertSessionHasErrors('sistem_pembayaran');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertNotNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_pengguna_lain_tidak_bisa_finalisasi_draf_booking(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-OTHER3',
            'nama_depan' => 'Lain',
            'nama_balakang' => 'Orang',
            'email' => 'lain3@example.com',
            'nowa' => '0833333333',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('bookings', 0);
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

    public function test_approving_booking_menandai_pembayaran_lunas(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $this->fakeCloudinary();

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('menunggu_verifikasi', $booking->status_pembayaran);

        $this->withSession(['admin_id' => 1])
            ->patch(route('admin.bookings.approve', $booking))
            ->assertRedirect();

        $booking->refresh();

        $this->assertSame('200', $booking->status_booking);
        $this->assertSame('lunas', $booking->status_pembayaran);
        $this->assertNotNull($booking->dibayar_pada);
    }

    public function test_admin_deleted_booking_hides_it_and_releases_the_time_slot(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $bookingData = [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ];

        $this->ajukanBooking($pengguna, $bookingData);

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

        $kodePembayaranBaru = $this->ajukanBooking($pengguna, $bookingData);

        $bookings = Booking::query()->where('tgl_main', $tanggal->toDateString())->get();
        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();

        $this->assertSame($kodePembayaranBaru, $newBooking->kode_pembayaran);
        $this->assertCount(2, $bookings);
        $this->assertSame('0', $newBooking->status_hapus);
    }

    public function test_admin_booking_index_menampilkan_nomor_whatsapp_pengguna(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $this->withSession(['admin_id' => 1])
            ->get(route('admin.bookings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Booking/Index')
                ->has('bookings.data', 1)
                ->where('bookings.data.0.pengguna.nowa', '081234567890'));
    }

    public function test_reapproving_voucher_booking_marks_voucher_as_used_again(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $this->ajukanBooking($pengguna, [
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

        $this->ajukanBooking($pengguna, $bookingData);

        $booking = Booking::query()->firstOrFail();

        $this->withSession(['admin_id' => 1])
            ->delete(route('admin.bookings.destroy', $booking))
            ->assertRedirect();

        $booking->refresh();
        $voucher->refresh();

        $this->assertSame('400', $booking->status_booking);
        $this->assertSame('1', $booking->status_hapus);
        $this->assertSame('', $voucher->tanggal_digunakan);

        $kodePembayaranBaru = $this->ajukanBooking($pengguna, $bookingData);

        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();
        $voucher->refresh();

        $this->assertSame($kodePembayaranBaru, $newBooking->kode_pembayaran);
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

        $this->ajukanBooking($pengguna, $bookingData);

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(Carbon::today()->toDateString(), $voucher->refresh()->tanggal_digunakan);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('riwayat.destroy', $booking))
            ->assertRedirect();

        $this->assertSame('1', $booking->refresh()->status_hapus);
        $this->assertSame('', $voucher->refresh()->tanggal_digunakan);

        $kodePembayaranBaru = $this->ajukanBooking($pengguna, $bookingData);

        $newBooking = Booking::query()->where('status_hapus', '0')->firstOrFail();
        $voucher->refresh();

        $this->assertSame($kodePembayaranBaru, $newBooking->kode_pembayaran);
        $this->assertSame($voucher->kode_voucher, $newBooking->kode_voucher);
        $this->assertSame(Carbon::today()->toDateString(), $voucher->tanggal_digunakan);
    }

    public function test_deleting_one_voucher_booking_keeps_voucher_used_while_other_booking_remains(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $this->ajukanBooking($pengguna, [
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
        $jadwal = $this->siapkanJadwal();
        [$pengguna, , $jam, $tanggal] = $jadwal;
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $kodePembayaran = 'PAY-TEST-SESI-1';
        $dihapus = $this->buatBookingSesi($jadwal, $kodePembayaran, 'BK-TEST-SESI-1', '10:00', '11:00');
        $this->buatBookingSesi($jadwal, $kodePembayaran, 'BK-TEST-SESI-2', '11:00', '12:00');

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

    public function test_user_can_delete_one_draft_session_and_total_is_recalculated(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        $this->assertSame([(string) $jamKedua->id], session('booking_drafts.'.$kodePembayaran.'.jam_main_ids'));
        $this->assertDatabaseCount('bookings', 0);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pembayaran')
                ->where('pembayaran.total', 50000)
                ->where('pembayaran.bisa_hapus_sesi', true)
                ->has('pembayaran.bookings', 1)
                ->where('pembayaran.bookings.0.jam_mulai', '11:00'));
    }

    public function test_deleting_last_draft_session_removes_draft_and_returns_to_riwayat(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertRedirect(route('riwayat'));

        $this->assertNull(session('booking_drafts.'.$kodePembayaran));
        $this->assertDatabaseCount('bookings', 0);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertNotFound();
    }

    public function test_finalisasi_setelah_hapus_sesi_draft_hanya_menyimpan_sesi_tersisa(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), [
                'sistem_pembayaran' => 'transfer',
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('riwayat'));

        $this->assertDatabaseCount('bookings', 1);

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('11:00', substr((string) $booking->jam_mulai, 0, 5));
        $this->assertNull(session('booking_drafts.'.$kodePembayaran));
    }

    public function test_user_cannot_delete_draft_session_from_another_user(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-OTHER-DRAFT',
            'nama_depan' => 'Lain',
            'nama_balakang' => 'Orang',
            'email' => 'lain-draft@example.com',
            'nowa' => '0833333333',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertNotFound();

        $this->assertSame([(string) $jam->id], session('booking_drafts.'.$kodePembayaran.'.jam_main_ids'));
    }

    public function test_voucher_draft_session_cannot_be_deleted(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $voucher = $this->buatVoucher($pengguna, 1);

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'kode_voucher' => $voucher->kode_voucher,
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-0']))
            ->assertSessionHas('error', 'Sesi dengan voucher tidak dapat dihapus satu per satu.');

        $this->assertSame([(string) $jam->id], session('booking_drafts.'.$kodePembayaran.'.jam_main_ids'));

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pembayaran.bisa_hapus_sesi', false));
    }

    public function test_invalid_draft_session_index_returns_404(): void
    {
        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])->post('/booking', [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
        ]);

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, 'draft-9']))
            ->assertNotFound();

        $this->assertSame([(string) $jam->id], session('booking_drafts.'.$kodePembayaran.'.jam_main_ids'));
    }

    public function test_hapus_sesi_menghilangkan_diskon_paket_dan_harga_kembali_normal(): void
    {
        $jadwal = $this->siapkanJadwal();
        [$pengguna, , , $tanggal] = $jadwal;
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $jamKetiga = jam_main::create(['jam_mulai' => '12:00', 'jam_selesai' => '13:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);
        $this->buatHarga($jamKetiga, $tanggal);

        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];

        paket_hargajammain::create([
            'vanue_id' => null,
            'hari' => $hari,
            'minimal_jam' => 3,
            'jam_mulai' => '00:00',
            'jam_selesai' => '00:00',
            'diskon_persen' => 20,
        ]);

        $kodePembayaran = 'PAY-TEST-SESI-2';
        $dihapus = $this->buatBookingSesi($jadwal, $kodePembayaran, 'BK-TEST-DISKON-1', '10:00', '11:00', 'belum_bayar', '40000', '10000', 20);
        $tersisa = [
            $this->buatBookingSesi($jadwal, $kodePembayaran, 'BK-TEST-DISKON-2', '11:00', '12:00', 'belum_bayar', '40000', '10000', 20),
            $this->buatBookingSesi($jadwal, $kodePembayaran, 'BK-TEST-DISKON-3', '12:00', '13:00', 'belum_bayar', '40000', '10000', 20),
        ];

        Booking::query()->where('kode_pembayaran', $kodePembayaran)->each(
            fn (Booking $booking) => $this->assertSame('40000', $booking->total_harga),
        );

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$kodePembayaran, $dihapus]))
            ->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        foreach ($tersisa as $booking) {
            $this->assertSame('50000', $booking->refresh()->total_harga);
        }

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->get(route('booking.pembayaran', $kodePembayaran))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Pembayaran')
                ->where('pembayaran.subtotal', 100000)
                ->where('pembayaran.diskon', 0)
                ->where('pembayaran.total', 100000)
                ->has('pembayaran.bookings', 2));
    }

    public function test_deleting_last_session_cancels_pembayaran_and_returns_to_riwayat(): void
    {
        $jadwal = $this->siapkanJadwal();
        [$pengguna] = $jadwal;

        $booking = $this->buatBookingSesi($jadwal, 'PAY-TEST-SESI-3', 'BK-TEST-SESI-3', '10:00', '11:00');

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
        $jadwal = $this->siapkanJadwal();
        [$pengguna] = $jadwal;

        $booking = $this->buatBookingSesi(
            $jadwal,
            'PAY-TEST-SESI-4',
            'BK-TEST-SESI-4',
            '10:00',
            '11:00',
            'menunggu_verifikasi',
        );

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->delete(route('booking.pembayaran.sesi.destroy', [$booking->kode_pembayaran, $booking]))
            ->assertRedirect();

        $this->assertSame('0', $booking->refresh()->status_hapus);
        $this->assertSame('menunggu_verifikasi', $booking->status_pembayaran);
    }

    public function test_user_cannot_delete_session_from_another_user_pembayaran(): void
    {
        $jadwal = $this->siapkanJadwal();
        [$pengguna] = $jadwal;
        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-OTHER2',
            'nama_depan' => 'Lain',
            'nama_balakang' => 'Orang',
            'email' => 'lain2@example.com',
            'nowa' => '0822222222',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $booking = $this->buatBookingSesi($jadwal, 'PAY-TEST-SESI-5', 'BK-TEST-SESI-5', '10:00', '11:00');

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
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('public', $booking->tipe_booking);
        $this->assertSame('50000', $booking->total_harga);
    }

    public function test_booking_saves_private_tipe_with_full_price(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'private',
        ]);

        $booking = Booking::query()->firstOrFail();

        $this->assertSame('private', $booking->tipe_booking);
        $this->assertSame('50000', $booking->total_harga);
    }

    public function test_pengguna_lain_bisa_join_public_room(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $bookingPemilik = Booking::query()->firstOrFail();

        $this->assertNotNull($bookingPemilik->kode_room);
        $this->assertSame('50000', $bookingPemilik->total_harga);

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
        $this->assertSame('50000', $bookingPendatang->total_harga);
        $this->assertSame('belum_bayar', $bookingPendatang->status_pembayaran);
    }

    public function test_semua_sesi_join_room_memakai_satu_kode_booking_untuk_user_tersebut(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();
        $jamKedua = jam_main::create(['jam_mulai' => '11:00', 'jam_selesai' => '12:00', 'status' => 'aktif']);
        $this->buatHarga($jamKedua, $tanggal);

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id, (string) $jamKedua->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $kodeBookingPemilik = Booking::query()
            ->where('id_pengguna', (string) $pengguna->id)
            ->value('kode_booking');

        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-JOIN-DUA-SESI',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Tester',
            'email' => 'budi-dua-sesi@example.com',
            'nowa' => '081234567893',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $bookingPemilik = Booking::query()->where('id_pengguna', (string) $pengguna->id)->firstOrFail();

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('room.booking.join', $bookingPemilik->kode_room))
            ->assertRedirect();

        $bookingsPendatang = Booking::query()
            ->where('id_pengguna', (string) $penggunaLain->id)
            ->orderBy('jam_mulai')
            ->get();

        $this->assertCount(2, $bookingsPendatang);
        $this->assertNotNull($bookingsPendatang->first()->kode_booking);
        $this->assertSame(1, $bookingsPendatang->pluck('kode_booking')->unique()->count());
        $this->assertSame(1, $bookingsPendatang->pluck('kode_pembayaran')->unique()->count());
        $this->assertNotSame($kodeBookingPemilik, $bookingsPendatang->first()->kode_booking);
    }

    public function test_join_room_ditolak_bila_nomor_whatsapp_kosong(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'all',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
            'meja_id' => (string) $meja->id,
            'jam_main_ids' => [(string) $jam->id],
            'tgl_main' => $tanggal->toDateString(),
            'tipe_booking' => 'public',
        ]);

        $bookingPemilik = Booking::query()->firstOrFail();

        $penggunaLain = Pengguna::create([
            'kode_user' => 'PG-NOWA-KOSONG',
            'nama_depan' => 'Budi',
            'nama_balakang' => 'Tester',
            'email' => 'budi-nowa-kosong@example.com',
            'nowa' => '',
            'password' => bcrypt('password'),
            'status' => 'aktif',
        ]);

        $this->withSession(['pengguna_id' => $penggunaLain->id])
            ->post(route('room.booking.join', $bookingPemilik->kode_room))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_pengguna_lain_join_room_one_tanpa_bayar(): void
    {
        $this->fakeCloudinary();

        [$pengguna, $meja, $jam, $tanggal] = $this->siapkanJadwal();

        Settingbookingroom::create([
            'pembayaran' => 'one',
            'jumlah_user' => '4',
            'minimal_user' => '2',
            'maksimal_user' => '8',
            'status' => 'aktif',
        ]);

        $this->ajukanBooking($pengguna, [
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

    /**
     * @param  array{0: Pengguna, 1: Meja, 2: jam_main, 3: Carbon}  $jadwal
     */
    private function buatBookingSesi(
        array $jadwal,
        string $kodePembayaran,
        string $kodeBooking,
        string $jamMulai,
        string $jamSelesai,
        string $statusPembayaran = 'belum_bayar',
        string $totalHarga = '50000',
        string $diskonPaket = '0',
        ?float $diskonPaketPersen = null,
        ?string $kodeVoucher = null,
    ): Booking {
        [$pengguna, $meja, , $tanggal] = $jadwal;

        return Booking::create([
            'kode_booking' => $kodeBooking,
            'kode_pembayaran' => $kodePembayaran,
            'kode_voucher' => $kodeVoucher,
            'tipe_booking' => 'reguler',
            'id_pengguna' => (string) $pengguna->id,
            'id_meja' => $meja->kode_meja,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'tgl_main' => $tanggal->toDateString(),
            'tgl_booking' => Carbon::today()->toDateString(),
            'sistem_pembayaran' => '',
            'harga' => '50000',
            'total_harga' => $totalHarga,
            'diskon_paket' => $diskonPaket,
            'diskon_paket_persen' => $diskonPaketPersen,
            'bukti_pembayaran' => '',
            'status_booking' => '0',
            'status_pembayaran' => $statusPembayaran,
            'status_hapus' => '0',
        ]);
    }

    public function test_bukti_pembayaran_tetap_tersimpan_saat_gambar_tidak_bisa_diproses_gd(): void
    {
        $this->fakeCloudinary();

        $file = UploadedFile::fake()->createWithContent('bukti.jpg', 'bukan-data-gambar');

        $url = app(BuktiPembayaranService::class)->simpan($file);

        $this->assertStringStartsWith('https://res.cloudinary.com/test-cloud/image/upload/', $url);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/upload');
    }

    private function ajukanBooking(Pengguna $pengguna, array $data, ?string $metode = 'transfer'): string
    {
        $this->fakeCloudinary();

        $response = $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.store'), $data)
            ->assertRedirect();

        $kodePembayaran = $this->kodePembayaranDariRedirect($response);
        $response->assertRedirect(route('booking.pembayaran', $kodePembayaran));

        $metode = filled($data['kode_voucher'] ?? null) ? 'voucher' : ($metode ?? 'transfer');
        $payload = ['sistem_pembayaran' => $metode];

        if ($metode !== 'voucher') {
            $payload['bukti_pembayaran'] = UploadedFile::fake()->image('bukti.jpg');
        }

        $this->withSession(['pengguna_id' => $pengguna->id])
            ->post(route('booking.pembayaran.store', $kodePembayaran), $payload)
            ->assertRedirect(route('riwayat'));

        return $kodePembayaran;
    }

    private function kodePembayaranDariRedirect($response): string
    {
        return Str::afterLast((string) $response->headers->get('Location'), '/');
    }
}
