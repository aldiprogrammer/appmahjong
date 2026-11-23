<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Jembatan ke Cloudinary Upload API memakai Laravel HTTP client.
 *
 * Gambar bukti pembayaran diunggah dengan autentikasi bertanda tangan:
 * tanda tangan SHA-1 dibuat dari field yang dikirim (kecuali file, api_key,
 * cloud_name, dan resource_type) ditambah API secret. File tidak pernah
 * ditulis ke storage lokal.
 */
class CloudinaryService
{
    private const TRANSFORMASI_THUMBNAIL = 'c_limit,w_420,q_auto:good';

    /**
     * Unggah byte gambar dan kembalikan secure_url dari Cloudinary.
     * Folder bisa ditentukan per panggil; null memakai folder bawaan konfigurasi.
     */
    public function unggah(string $isi, string $namaFile, ?string $folder = null): string
    {
        $this->pastikanTerkonfigurasi();

        $parameter = [
            'public_id' => $this->publicId($namaFile, $folder),
            'timestamp' => (string) now()->getTimestamp(),
        ];

        $respons = $this->permintaan()
            ->attach('file', $isi, $namaFile)
            ->post($this->url('image/upload'), $parameter + [
                'api_key' => (string) config('services.cloudinary.api_key'),
                'signature' => $this->tandaTangan($parameter),
            ]);

        $this->pastikanBerhasil($respons, 'Gagal mengunggah bukti pembayaran ke Cloudinary.');

        $url = $respons->json('secure_url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Cloudinary tidak mengembalikan URL gambar bukti pembayaran.');
        }

        return $url;
    }

    /**
     * Hapus aset Cloudinary berdasarkan secure_url yang tersimpan di booking.
     * Aset lama yang sudah tidak ada di Cloudinary dianggap berhasil dihapus.
     */
    public function hapus(string $url): void
    {
        $this->pastikanTerkonfigurasi();

        $publicId = $this->publicIdDariUrl($url);

        if ($publicId === null) {
            return;
        }

        $parameter = [
            'invalidate' => 'true',
            'public_id' => $publicId,
            'timestamp' => (string) now()->getTimestamp(),
        ];

        $respons = $this->permintaan()->post($this->url('image/destroy'), $parameter + [
            'api_key' => (string) config('services.cloudinary.api_key'),
            'signature' => $this->tandaTangan($parameter),
        ]);

        $this->pastikanBerhasil($respons, 'Gagal menghapus bukti pembayaran lama di Cloudinary.');
    }

    /**
     * URL thumbnail memakai transformasi on-the-fly Cloudinary, sehingga tidak
     * perlu mengunggah file thumbnail terpisah.
     */
    public function urlThumbnail(string $url): string
    {
        return str_replace('/image/upload/', '/image/upload/'.self::TRANSFORMASI_THUMBNAIL.'/', $url);
    }

    /**
     * Ambil public_id dari secure_url Cloudinary, misalnya:
     * .../image/upload/v1234/bukti-pembayaran/abc.jpg -> bukti-pembayaran/abc
     */
    public function publicIdDariUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_contains($path, '/image/upload/')) {
            return null;
        }

        $publicId = Str::after($path, '/image/upload/');
        $publicId = (string) preg_replace('/^v\d+\//', '', $publicId);

        $ekstensi = pathinfo($publicId, PATHINFO_EXTENSION);

        if ($ekstensi !== '') {
            $publicId = substr($publicId, 0, -(strlen($ekstensi) + 1));
        }

        return $publicId !== '' ? $publicId : null;
    }

    private function permintaan(): PendingRequest
    {
        return Http::connectTimeout((int) config('services.cloudinary.connect_timeout', 5))
            ->timeout((int) config('services.cloudinary.timeout', 30));
    }

    private function url(string $aksi): string
    {
        return sprintf('https://api.cloudinary.com/v1_1/%s/%s', config('services.cloudinary.cloud_name'), $aksi);
    }

    /**
     * Cloudinary meng-hash pasangan key=value dengan nilai mentah, tanpa
     * URL-encoding. Mengubah "/" pada public_id menjadi %2F membuat tanda
     * tangan ditolak.
     *
     * @param  array<string, string>  $parameter
     */
    private function tandaTangan(array $parameter): string
    {
        ksort($parameter);

        $pasangan = [];

        foreach ($parameter as $kunci => $nilai) {
            $pasangan[] = $kunci.'='.$nilai;
        }

        return hash('sha1', implode('&', $pasangan).config('services.cloudinary.api_secret'));
    }

    private function publicId(string $namaFile, ?string $folder = null): string
    {
        $folder = trim($folder ?? (string) config('services.cloudinary.folder', 'bukti-pembayaran'), '/');
        $nama = pathinfo($namaFile, PATHINFO_FILENAME);

        return $folder !== '' ? $folder.'/'.$nama : $nama;
    }

    private function pastikanTerkonfigurasi(): void
    {
        $kunci = ['cloud_name', 'api_key', 'api_secret'];

        foreach ($kunci as $kunciConfig) {
            if (! filled(config('services.cloudinary.'.$kunciConfig))) {
                throw new RuntimeException('Konfigurasi Cloudinary belum lengkap. Isi CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, dan CLOUDINARY_API_SECRET di file .env.');
            }
        }
    }

    private function pastikanBerhasil(Response $respons, string $pesan): void
    {
        if ($respons->successful()) {
            return;
        }

        $detail = $respons->json('error.message') ?? 'HTTP '.$respons->status();

        throw new RuntimeException($pesan.' Detail: '.$detail);
    }
}
