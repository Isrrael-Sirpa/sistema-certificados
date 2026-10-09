<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Genera el contenido SVG del código QR para una URL dada.
     */
    public function generateSvg(string $url, int $size = 180): string
    {
        return QrCode::size($size)
            ->format('svg')
            ->errorCorrection('H')
            ->generate($url);
    }

    /**
     * Genera una cadena Base64 Data URI del QR SVG listo para incrustar directamente en PDFs y Blade.
     */
    public function generateBase64Svg(string $url, int $size = 180): string
    {
        $svg = $this->generateSvg($url, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
