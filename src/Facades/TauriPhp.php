<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void initialize(string $appName, array $options = [])
 * @method static array build(string|array $platforms = 'all', array $options = [])
 * @method static void dev(array $options = [])
 * @method static array package(array $options = [])
 * @method static string getVersion()
 * @method static bool isTauriInitialized()
 * @method static array getBuildInfo()
 * @method static void clean()
 *
 * @see \Mucan54\TauriPhp\Services\TauriPhpService
 */
class TauriPhp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tauri-php';
    }
}
