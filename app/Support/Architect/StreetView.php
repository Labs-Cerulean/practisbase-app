<?php

namespace App\Support\Architect;

/**
 * Street View helpers — zero API keys, zero billing.
 *
 * Opens Google’s public maps.google.com Street View in a new tab.
 * PractisBase never calls Google Maps Platform / Mapbox / paid tile APIs.
 */
class StreetView
{
    /**
     * Public Google Maps Street View URL (no API key, not a billed Platform request).
     */
    public static function mapsUrl(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='
            .rawurlencode(number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', ''));
    }

    /**
     * @return array{mapsTemplate: string}
     */
    public static function clientConfig(): array
    {
        return [
            'mapsTemplate' => 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={lat},{lng}',
        ];
    }
}
