<?php

namespace App\Support\Architect;

/**
 * Street View helpers for the Architect impact map split pane.
 * Prefer official Embed API when GOOGLE_MAPS_API_KEY is set.
 */
class StreetView
{
    public static function apiKey(): ?string
    {
        $key = trim((string) config('services.google.maps_api_key', ''));

        return $key !== '' ? $key : null;
    }

    /**
     * Open Google Maps Street View at a viewpoint (no API key required).
     */
    public static function mapsUrl(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='
            .rawurlencode(number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', ''));
    }

    /**
     * iframe src for the side panel.
     */
    public static function embedUrl(float $lat, float $lng): string
    {
        $key = self::apiKey();
        if ($key) {
            return 'https://www.google.com/maps/embed/v1/streetview?key='.rawurlencode($key)
                .'&location='.rawurlencode(number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', ''))
                .'&heading=0&pitch=0&fov=80';
        }

        // Fallback embed (coverage-dependent; full Street View link always available).
        return 'https://www.google.com/maps?layer=c&cbll='
            .rawurlencode(number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', ''))
            .'&cbp=12,0,0,0,0&hl=en&output=svembed';
    }

    /**
     * @return array{hasKey: bool, embedTemplate: string, mapsTemplate: string}
     */
    public static function clientConfig(): array
    {
        return [
            'hasKey' => self::apiKey() !== null,
            'embedTemplate' => self::apiKey()
                ? 'https://www.google.com/maps/embed/v1/streetview?key='.rawurlencode((string) self::apiKey()).'&location={lat},{lng}&heading=0&pitch=0&fov=80'
                : 'https://www.google.com/maps?layer=c&cbll={lat},{lng}&cbp=12,0,0,0,0&hl=en&output=svembed',
            'mapsTemplate' => 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={lat},{lng}',
        ];
    }
}
