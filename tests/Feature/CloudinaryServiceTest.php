<?php

namespace Tests\Feature;

use App\Services\CloudinaryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    public function test_unggah_mengirim_parameter_bertanda_tangan_tanpa_menulis_file_lokal(): void
    {
        $this->freezeTime();
        $this->fakeCloudinary();
        Storage::fake('public');

        $timestamp = (string) now()->getTimestamp();
        $tandaTangan = sha1('public_id=bukti-pembayaran/abc&timestamp='.$timestamp.'test-secret');

        $url = app(CloudinaryService::class)->unggah('isi-gambar', 'abc.jpg');

        $this->assertSame(
            'https://res.cloudinary.com/test-cloud/image/upload/v1234/bukti-pembayaran/abc.jpg',
            $url,
        );
        Http::assertSent(function (Request $request) use ($timestamp, $tandaTangan): bool {
            return $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/upload'
                && $this->parameterCloudinary($request, 'public_id') === 'bukti-pembayaran/abc'
                && $this->parameterCloudinary($request, 'api_key') === 'test-key'
                && $this->parameterCloudinary($request, 'timestamp') === $timestamp
                && $this->parameterCloudinary($request, 'signature') === $tandaTangan;
        });
        Storage::disk('public')->assertMissing('bukti-pembayaran/abc.jpg');
        Storage::disk('public')->assertMissing('bukti-pembayaran/abc-thumb.jpg');
    }

    public function test_unggah_bisa_memilih_folder_scanning_point(): void
    {
        $this->freezeTime();
        $this->fakeCloudinary();

        $timestamp = (string) now()->getTimestamp();
        $tandaTangan = sha1('public_id=scanning-point/abc&timestamp='.$timestamp.'test-secret');

        $url = app(CloudinaryService::class)->unggah('isi-gambar', 'abc.jpg', 'scanning-point');

        $this->assertSame(
            'https://res.cloudinary.com/test-cloud/image/upload/v1234/scanning-point/abc.jpg',
            $url,
        );
        Http::assertSent(fn (Request $request) => $this->parameterCloudinary($request, 'public_id') === 'scanning-point/abc'
            && $this->parameterCloudinary($request, 'signature') === $tandaTangan);
    }

    public function test_hapus_mengirim_public_id_hasil_ekstraksi_dari_url_cloudinary(): void
    {
        $this->freezeTime();
        $this->fakeCloudinary();

        $timestamp = (string) now()->getTimestamp();
        $tandaTangan = sha1('invalidate=true&public_id=bukti-pembayaran/abc&timestamp='.$timestamp.'test-secret');

        app(CloudinaryService::class)->hapus(
            'https://res.cloudinary.com/test-cloud/image/upload/v1234/bukti-pembayaran/abc.jpg',
        );

        Http::assertSent(function (Request $request) use ($timestamp, $tandaTangan): bool {
            return $request->url() === 'https://api.cloudinary.com/v1_1/test-cloud/image/destroy'
                && $this->parameterCloudinary($request, 'public_id') === 'bukti-pembayaran/abc'
                && $this->parameterCloudinary($request, 'invalidate') === 'true'
                && $this->parameterCloudinary($request, 'timestamp') === $timestamp
                && $this->parameterCloudinary($request, 'signature') === $tandaTangan;
        });
    }

    public function test_url_thumbnail_memakai_transformasi_cloudinary(): void
    {
        $url = app(CloudinaryService::class)->urlThumbnail(
            'https://res.cloudinary.com/test-cloud/image/upload/v1234/bukti-pembayaran/abc.jpg',
        );

        $this->assertSame(
            'https://res.cloudinary.com/test-cloud/image/upload/c_limit,w_420,q_auto:good/v1234/bukti-pembayaran/abc.jpg',
            $url,
        );
    }

    public function test_unggah_ditolak_bila_konfigurasi_cloudinary_belum_lengkap(): void
    {
        config([
            'services.cloudinary.cloud_name' => null,
            'services.cloudinary.api_key' => null,
            'services.cloudinary.api_secret' => null,
        ]);
        Http::preventStrayRequests();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Konfigurasi Cloudinary belum lengkap');

        app(CloudinaryService::class)->unggah('isi-gambar', 'abc.jpg');
    }

    public function test_unggah_melempar_error_bila_cloudinary_menolak_permintaan(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
        ]);
        Http::fake([
            'api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid signature']], 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid signature');

        app(CloudinaryService::class)->unggah('isi-gambar', 'abc.jpg');
    }
}
