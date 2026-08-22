<?php

namespace App\Support\Architect;

/**
 * Street View helpers — no Google Maps Platform / Mapbox API keys.
 *
 * In-app pane uses Google’s public Street View page embed (not a billed
 * Maps Platform request). New-tab link is the same public maps URL.
 */
class StreetView
{
    /**
     * Public Google Maps Street View URL (new tab).
     */
    public static function mapsUrl(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='
            .rawurlencode(number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', ''));
    }

    /**
     * Public Street View page for in-app iframe (no API key).
     */
    public static function embedUrl(float $lat, float $lng): string
    {
        $ll = number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', '');

        return 'https://www.google.com/maps?layer=c&cbll='.rawurlencode($ll)
            .'&cbp=12,0,0,0,0&hl=en&output=svembed';
    }

    /**
     * @return array{mapsTemplate: string, embedTemplate: string}
     */
    public static function clientConfig(): array
    {
        return [
            'mapsTemplate' => 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={lat},{lng}',
            'embedTemplate' => 'https://www.google.com/maps?layer=c&cbll={lat},{lng}&cbp=12,0,0,0,0&hl=en&output=svembed',
        ];
    }
}
