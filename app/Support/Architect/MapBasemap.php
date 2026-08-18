<?php

namespace App\Support\Architect;

/**
 * Free basemap tiles for practice maps (no API key).
 * CARTO Voyager — clearer than default OSM for Malta/Gozo site work.
 */
class MapBasemap
{
    public const TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

    public const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';

    public const MAX_ZOOM = 20;

    /**
     * @return array{url: string, attribution: string, maxZoom: int}
     */
    public static function leafletConfig(): array
    {
        return [
            'url' => self::TILE_URL,
            'attribution' => self::ATTRIBUTION,
            'maxZoom' => self::MAX_ZOOM,
        ];
    }
}
