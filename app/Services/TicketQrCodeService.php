<?php

namespace App\Services;

use App\Models\Concern;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TicketQrCodeService
{
    public function forConcern(Concern $concern): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(180, 2),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString(route('concerns.show', $concern));

        return preg_replace('/<\?xml.*?\?>\s*/', '', $svg) ?? $svg;
    }
}
