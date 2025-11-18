<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Vibration Facade
 *
 * @method static array vibrate(int $duration = 300)
 * @method static array vibratePattern(array $pattern)
 * @method static array cancel()
 * @method static array impact(string $style = 'medium')
 * @method static array notification(string $type = 'success')
 * @method static array selection()
 * @method static array requestPermissions()
 * @method static array checkPermissions()
 *
 * @see \Mucan54\TauriPhp\Plugins\Vibration\Vibration
 */
class Vibration extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Mucan54\TauriPhp\Plugins\Vibration\Vibration::class;
    }
}
