<?php

namespace App\Support\Architect;

/**
 * Street View helpers — no Google Maps Platform / Mapbox API keys.
 *
 * In-app pane uses Google’s public Street View page embed (not a billed
 * Maps Platform request). Heading is passed so the map look-cone can match.
 */
class StreetView
{
    /**
     * Public Google Maps Street View URL (new tab).
     */
    public static function mapsUrl(float $lat, float $lng, float $heading = 0): string
    {
        $viewpoint = number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', '');

        return 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='.rawurlencode($viewpoint)
            .'&heading='.rawurlencode((string) round(self::normalizeHeading($heading), 1));
    }

    /**
     * Public Street View page for in-app iframe (no API key).
     * cbp: degreesOfArc,yaw(heading),tilt,zoom,pitch
     */
    public static function embedUrl(float $lat, float $lng, float $heading = 0): string
    {
        $ll = number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', '');
        $yaw = round(self::normalizeHeading($heading), 1);

        return 'https://www.google.com/maps?layer=c&cbll='.rawurlencode($ll)
            .'&cbp=12,'.rawurlencode((string) $yaw).',0,0,0&hl=en&output=svembed';
    }

    public static function normalizeHeading(float $heading): float
    {
        $h = fmod($heading, 360.0);
        if ($h < 0) {
            $h += 360.0;
        }

        return $h;
    }

    /**
     * @return array{mapsTemplate: string, embedTemplate: string}
     */
    public static function clientConfig(): array
    {
        return [
            'mapsTemplate' => 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={lat},{lng}&heading={heading}',
            'embedTemplate' => 'https://www.google.com/maps?layer=c&cbll={lat},{lng}&cbp=12,{heading},0,0,0&hl=en&output=svembed',
        ];
    }
}
