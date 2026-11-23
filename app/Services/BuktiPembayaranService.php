<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Mengompres dan meresize bukti pembayaran (JPG/PNG) di memory sebelum
 * diunggah ke Cloudinary:
 * - gambar utama maksimal 1000x1400px, kualitas JPEG 80
 * - thumbnail dibuat otomatis oleh Cloudinary lewat transformasi URL
 *
 * File asli tidak disimpan, baik di storage Laravel maupun di Cloudinary.
 */
class BuktiPembayaranService
{
    private const MAX_LEBAR = 1000;

    private const MAX_TINGGI = 1400;

    private const KUALITAS = 80;

    private const DISK = 'public';

    public function __construct(private readonly CloudinaryService $cloudinary) {}

    /**
     * Simpan bukti pembayaran terkompres ke Cloudinary, kembalikan secure_url.
     */
    public function simpan(UploadedFile $file): string
    {
        try {
            $isi = (string) file_get_contents((string) $file->getRealPath());
            $nama = bin2hex(random_bytes(12));
            $gd = @imagecreatefromstring($isi);

            if ($gd === false) {
                // Tetap unggah apa adanya bila GD tidak bisa memproses gambar.
                return $this->cloudinary->unggah($isi, $nama.'.'.$this->ekstensiAsli($file));
            }

            $gd = $this->perbaikiOrientasiExif($file, $gd);
            $isi = $this->kompresJpeg($gd);

            imagedestroy($gd);

            return $this->cloudinary->unggah($isi, $nama.'.jpg');
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('Gagal mengunggah bukti pembayaran. Silakan coba lagi.', 0, $exception);
        }
    }

    /**
     * URL thumbnail dari URL Cloudinary, atau path thumbnail lama untuk
     * booking yang masih memakai storage lokal. Null bila path utama kosong.
     */
    public function pathThumbnail(?string $pathUtama): ?string
    {
        if (! $pathUtama) {
            return null;
        }

        if ($this->adalahUrl($pathUtama)) {
            return $this->cloudinary->urlThumbnail($pathUtama);
        }

        $info = pathinfo($pathUtama);

        return sprintf('%s/%s-thumb.jpg', $info['dirname'] ?? '', $info['filename']);
    }

    /**
     * Hapus aset Cloudinary untuk bukti baru. Path lokal lama tetap dihapus
     * dari disk 'public' agar booking lama bisa diunggah ulang.
     */
    public function hapus(?string $pathUtama): void
    {
        if (! $pathUtama) {
            return;
        }

        if ($this->adalahUrl($pathUtama)) {
            try {
                $this->cloudinary->hapus($pathUtama);
            } catch (Throwable $exception) {
                // Bukti baru sudah tersimpan; kegagalan hapus tidak boleh
                // membatalkan unggah ulang.
                report($exception);
            }

            return;
        }

        Storage::disk(self::DISK)->delete($pathUtama);
        $thumbnail = $this->pathThumbnail($pathUtama);

        if ($thumbnail) {
            Storage::disk(self::DISK)->delete($thumbnail);
        }
    }

    private function adalahUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private function ekstensiAsli(UploadedFile $file): string
    {
        $ekstensi = strtolower((string) $file->getClientOriginalExtension());

        return in_array($ekstensi, ['jpg', 'jpeg', 'png'], true) ? $ekstensi : 'jpg';
    }

    /**
     * Ukur ulang lebar gambar bila melebihi batas, lalu kembalikan byte JPEG.
     */
    private function kompresJpeg(\GdImage $gd): string
    {
        $lebarAsli = max(1, imagesx($gd));
        $tinggiAsli = max(1, imagesy($gd));
        $skala = min(1, self::MAX_LEBAR / $lebarAsli, self::MAX_TINGGI / $tinggiAsli);

        $lebarBaru = max(1, (int) round($lebarAsli * $skala));
        $tinggiBaru = max(1, (int) round($tinggiAsli * $skala));

        $hasil = $this->kanvasJpeg($gd, $lebarBaru, $tinggiBaru);

        ob_start();
        imagejpeg($hasil, null, self::KUALITAS);
        $isi = (string) ob_get_clean();
        imagedestroy($hasil);

        return $isi;
    }

    /**
     * Kanvas bersih dengan latar putih agar PNG transparan tidak hitam saat dijadikan JPEG.
     * Resize dilakukan lewat imagecopyresampled agar tidak bergantung pada imagescale()
     * yang bisa mengembalikan false pada sebagian konfigurasi GD.
     */
    private function kanvasJpeg(\GdImage $gd, int $lebar, int $tinggi): \GdImage
    {
        $kanvas = imagecreatetruecolor($lebar, $tinggi);

        if ($kanvas === false) {
            throw new RuntimeException('Gagal membuat kanvas gambar bukti pembayaran.');
        }

        $putih = (int) imagecolorallocate($kanvas, 255, 255, 255);
        imagefill($kanvas, 0, 0, $putih);
        imagecopyresampled($kanvas, $gd, 0, 0, 0, 0, $lebar, $tinggi, imagesx($gd), imagesy($gd));

        return $kanvas;
    }

    /**
     * Koreksi rotasi foto HP (orientation EXIF dari kamera) sebelum resize.
     */
    private function perbaikiOrientasiExif(UploadedFile $file, \GdImage $gd): \GdImage
    {
        $orientasi = 0;

        try {
            $exif = @exif_read_data((string) $file->getRealPath());
            $orientasi = (int) ($exif['Orientation'] ?? 0);
        } catch (Throwable) {
            // Gambar tanpa EXIF dipakai apa adanya.
        }

        $hasilRotasi = match ($orientasi) {
            3 => imagerotate($gd, 180, 0),
            6 => imagerotate($gd, -90, 0),
            8 => imagerotate($gd, 90, 0),
            default => $gd,
        };

        return $hasilRotasi instanceof \GdImage ? $hasilRotasi : $gd;
    }
}
