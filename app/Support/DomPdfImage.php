<?php

namespace App\Support;

/**
 * DomPDF can embed JPEG without GD, but PNG (incl. data-URI stamps/logos) requires ext-gd.
 * Use this before passing image src into PDF Blade views.
 */
class DomPdfImage
{
    public static function canEmbedPng(): bool
    {
        return function_exists('imagecreatefrompng') && function_exists('imagecreatefromstring');
    }

    /**
     * Return a data URI DomPDF can embed, or null to omit the image.
     * When GD is available, PNG→JPEG flattening avoids alpha-channel edge cases.
     */
    public static function embeddable(?string $dataUri): ?string
    {
        if (! filled($dataUri) || ! is_string($dataUri)) {
            return null;
        }

        if (preg_match('#^data:image/jpe?g;#i', $dataUri)) {
            return $dataUri;
        }

        if (! self::canEmbedPng()) {
            return null;
        }

        if (! preg_match('#^data:image/(png|webp|gif);base64,#i', $dataUri)) {
            return $dataUri;
        }

        $raw = substr($dataUri, strpos($dataUri, ',') + 1);
        $binary = base64_decode($raw, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);

            return null;
        }

        $dst = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $width, $height, $white);
        imagealphablending($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($dst);

        if (! is_string($jpeg) || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}
