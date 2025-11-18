<?php

namespace Mucan54\TauriPhp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Camera Facade
 *
 * @method static \Mucan54\TauriPhp\Plugins\Camera\CameraResult takePhoto(array $options = [])
 * @method static \Mucan54\TauriPhp\Plugins\Camera\CameraResult pickPhoto(array $options = [])
 * @method static array pickMultiplePhotos(array $options = [])
 * @method static array getPermissions()
 * @method static array requestPermissions()
 * @method static array checkPermissions()
 *
 * @see \Mucan54\TauriPhp\Plugins\Camera\Camera
 */
class Camera extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Mucan54\TauriPhp\Plugins\Camera\Camera::class;
    }
}
