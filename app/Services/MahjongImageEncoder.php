<?php

namespace App\Services;

/**
 * Menyiapkan gambar dari disk agar bisa dikirim ke layanan AI vision:
 * diperkecil maksimal 1536px dan diubah ke JPEG agar payload ringan.
 */
class MahjongImageEncoder
{
    private const MAX_DIMENSION = 1536;

    /**
     * @return array{mime: string, data: string}|null
     */
    public function base64(string $absolutePath): ?array
    {
        $contents = @file_get_contents($absolutePath);

        if ($contents === false || $contents === '') {
            return null;
        }

        $mime = 'image/jpeg';

        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $gambar = @imagecreatefromstring($contents);

            if ($gambar !== false) {
                $lebar = imagesx($gambar);
                $tinggi = imagesy($gambar);

                if (max($lebar, $tinggi) > self::MAX_DIMENSION) {
                    $rasio = self::MAX_DIMENSION / max($lebar, $tinggi);
                    $diperkecil = imagescale(
                        $gambar,
                        max(1, (int) round($lebar * $rasio)),
                        max(1, (int) round($tinggi * $rasio)),
                    );

                    if ($diperkecil !== false) {
                        imagedestroy($gambar);
                        $gambar = $diperkecil;
                    }
                }

                ob_start();
                imagejpeg($gambar, null, 85);
                $jpeg = ob_get_clean();

                if (is_string($jpeg) && $jpeg !== '') {
                    $contents = $jpeg;
                    $mime = 'image/jpeg';
                }

                if ($gambar instanceof \GdImage) {
                    imagedestroy($gambar);
                }
            }
        }

        if (! str_starts_with($contents, "\xFF\xD8")) {
            $info = @getimagesizefromstring($contents);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : $mime;
        }

        return ['mime' => $mime, 'data' => base64_encode($contents)];
    }

    public function dataUri(string $absolutePath): ?string
    {
        $gambar = $this->base64($absolutePath);

        return $gambar ? 'data:'.$gambar['mime'].';base64,'.$gambar['data'] : null;
    }
}
