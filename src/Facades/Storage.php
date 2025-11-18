<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Storage Facade
 *
 * @method static array set(string $key, mixed $value)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array remove(string $key)
 * @method static array clear()
 * @method static array keys()
 * @method static int length()
 * @method static array setMultiple(array $items)
 * @method static array getMultiple(array $keys)
 * @method static array removeMultiple(array $keys)
 * @method static array requestPermissions()
 * @method static array checkPermissions()
 *
 * @see \Mucan54\TauriPhp\Plugins\Storage\Storage
 */
class Storage extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Mucan54\TauriPhp\Plugins\Storage\Storage::class;
    }
}
