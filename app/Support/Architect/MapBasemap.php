<?php

namespace App\Support\Architect;

/**
 * Free basemap tiles for practice maps — no API keys, no Mapbox, no billing account.
 * Streets: CARTO Voyager (OSM). Satellite: Esri World Imagery public tiles (attribution only).
 */
class MapBasemap
{
    public const STREETS_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

    public const STREETS_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';

    public const SATELLITE_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';

    public const SATELLITE_ATTRIBUTION = 'Tiles &copy; Esri — Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community';

    public const MAX_ZOOM = 20;

    /**
     * Default streets config (portfolio / general maps).
     *
     * @return array{url: string, attribution: string, maxZoom: int, subdomains: string|null}
     */
    public static function leafletConfig(): array
    {
        return self::streetsConfig();
    }

    /**
     * @return array{url: string, attribution: string, maxZoom: int, subdomains: string|null}
     */
    public static function streetsConfig(): array
    {
        return [
            'url' => self::STREETS_URL,
            'attribution' => self::STREETS_ATTRIBUTION,
            'maxZoom' => self::MAX_ZOOM,
            'subdomains' => 'abcd',
        ];
    }

    /**
     * @return array{url: string, attribution: string, maxZoom: int, subdomains: string|null}
     */
    public static function satelliteConfig(): array
    {
        return [
            'url' => self::SATELLITE_URL,
            'attribution' => self::SATELLITE_ATTRIBUTION,
            'maxZoom' => self::MAX_ZOOM,
            'subdomains' => null,
        ];
    }
}
