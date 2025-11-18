<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Geolocation Facade
 *
 * @method static \Mucan54\TauriPhp\Plugins\Geolocation\Position getCurrentPosition(array $options = [])
 * @method static int watchPosition(array $options = [])
 * @method static array clearWatch(int $watchId)
 * @method static array requestPermissions()
 * @method static array checkPermissions()
 *
 * @see \Mucan54\TauriPhp\Plugins\Geolocation\Geolocation
 */
class Geolocation extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Mucan54\TauriPhp\Plugins\Geolocation\Geolocation::class;
    }
}
