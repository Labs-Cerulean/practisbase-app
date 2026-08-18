<?php

namespace App\Support\Architect;

/**
 * Deep links into the Planning Authority MapServer.
 * No public API is assumed — we do not pin PractisBase sites onto their map.
 */
class MapServerLink
{
    public const HOME = 'https://pamapserver.pa.org.mt/';

    public static function home(): string
    {
        return self::HOME;
    }
}
