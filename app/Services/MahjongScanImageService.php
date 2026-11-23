<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

/**
 * Menyimpan foto susunan mahjong langsung ke Cloudinary pada folder
 * "scanning-point". File asli tidak ditulis ke storage Laravel.
 */
class MahjongScanImageService
{
    private const FOLDER = 'scanning-point';

    public function __construct(private readonly CloudinaryService $cloudinary) {}

    /**
     * Simpan foto scan ke Cloudinary, kembalikan secure_url.
     */
    public function simpan(UploadedFile $file): string
    {
        try {
            $isi = (string) file_get_contents((string) $file->getRealPath());
            $nama = bin2hex(random_bytes(12)).'.'.$this->ekstensiAsli($file);

            return $this->cloudinary->unggah($isi, $nama, self::FOLDER);
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('Gagal mengunggah foto scan ke Cloudinary. Silakan coba lagi.', 0, $exception);
        }
    }

    private function ekstensiAsli(UploadedFile $file): string
    {
        $ekstensi = strtolower((string) $file->getClientOriginalExtension());

        return in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ekstensi : 'jpg';
    }
}
