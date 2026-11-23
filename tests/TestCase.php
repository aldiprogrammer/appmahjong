<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ganti Upload API Cloudinary dengan respons deterministik untuk test
     * yang mengunggah bukti pembayaran. Tidak ada file yang ditulis ke disk.
     */
    protected function fakeCloudinary(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'test-cloud',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
            'services.cloudinary.folder' => 'bukti-pembayaran',
        ]);

        Http::fake([
            'api.cloudinary.com/v1_1/test-cloud/image/upload' => function (Request $request) {
                $publicId = $this->parameterCloudinary($request, 'public_id') ?? 'bukti-pembayaran/bukti';

                return Http::response([
                    'secure_url' => 'https://res.cloudinary.com/test-cloud/image/upload/v1234/'.$publicId.'.jpg',
                    'public_id' => $publicId,
                ]);
            },
            'api.cloudinary.com/*' => Http::response(['result' => 'ok']),
        ]);
    }

    /**
     * Laravel menyimpan field multipart sebagai daftar bagian, sedangkan
     * permintaan destroy memakai form biasa. Baca keduanya dengan satu helper.
     */
    protected function parameterCloudinary(Request $request, string $nama): ?string
    {
        $data = $request->data();

        if (array_is_list($data)) {
            foreach ($data as $bagian) {
                if (is_array($bagian) && ($bagian['name'] ?? null) === $nama) {
                    return (string) ($bagian['contents'] ?? '');
                }
            }

            return null;
        }

        return isset($data[$nama]) ? (string) $data[$nama] : null;
    }
}
