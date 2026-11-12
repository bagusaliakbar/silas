<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Foundation\Application;

class QrCodeService
{
    /**
     * Generate an SVG string representation of a QR code with optional center logo.
     */
    public function generateSvg(string $data, int $size = 240, ?string $logoPath = null): string
    {
        $baseDir = (app() instanceof Application) ? public_path('images/logos') : dirname(__DIR__, 2).'/public/images/logos';
        $subangLogo = $baseDir.'/universitas-subang.png';
        $defaultLogo = file_exists($subangLogo) ? $subangLogo : $baseDir.'/tutwuri.png';

        $logo = $logoPath ?: $defaultLogo;

        // Always use Level H (High Error Correction ~30%) when embedding center logos
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($data, Encoder::DEFAULT_BYTE_MODE_ENCODING, ErrorCorrectionLevel::H());

        if ($logo && file_exists($logo)) {
            $logoData = base64_encode(file_get_contents($logo));
            $mime = pathinfo($logo, PATHINFO_EXTENSION) === 'svg' ? 'image/svg+xml' : 'image/png';
            $logoDataUri = "data:{$mime};base64,{$logoData}";

            $logoBoxSize = (int) round($size * 0.28);
            $logoBoxX = (int) round(($size - $logoBoxSize) / 2);
            $logoBoxY = (int) round(($size - $logoBoxSize) / 2);

            $innerPadding = (int) round($logoBoxSize * 0.06);
            $innerLogoSize = $logoBoxSize - ($innerPadding * 2);
            $innerLogoX = $logoBoxX + $innerPadding;
            $innerLogoY = $logoBoxY + $innerPadding;

            $overlay = <<<SVG
  <rect x="{$logoBoxX}" y="{$logoBoxY}" width="{$logoBoxSize}" height="{$logoBoxSize}" fill="#ffffff" rx="5" stroke="#cbd5e1" stroke-width="0.75" />
  <image href="{$logoDataUri}" x="{$innerLogoX}" y="{$innerLogoY}" width="{$innerLogoSize}" height="{$innerLogoSize}" preserveAspectRatio="xMidYMid meet" />
</svg>
SVG;

            $svg = str_replace('</svg>', $overlay, $svg);
        }

        return $svg;
    }

    /**
     * Generate a Base64 SVG Data URI suitable for <img :src="..." />.
     */
    public function generateDataUri(string $data, int $size = 240, ?string $logoPath = null): string
    {
        $svg = $this->generateSvg($data, $size, $logoPath);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
