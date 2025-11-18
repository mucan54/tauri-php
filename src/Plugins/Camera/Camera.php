<?php

namespace Mucan54\TauriPhp\Plugins\Camera;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Plugins\Plugin;

/**
 * Camera plugin for Tauri mobile applications.
 *
 * Provides access to device camera functionality including:
 * - Taking photos
 * - Recording videos
 * - Accessing photo gallery
 * - Image quality and format configuration
 */
class Camera extends Plugin
{
    /**
     * The plugin name.
     *
     * @var string
     */
    protected $pluginName = 'camera';

    /**
     * Take a photo using the device camera.
     *
     * @param  array  $options  Camera options
     * @return CameraResult Photo information
     *
     * @throws TauriPhpException
     *
     * Options:
     * - quality: int (0-100) - Image quality (default: 90)
     * - allowEditing: bool - Allow user to edit photo (default: false)
     * - resultType: string - 'base64'|'uri'|'dataUrl' (default: 'uri')
     * - saveToGallery: bool - Save photo to gallery (default: false)
     * - correctOrientation: bool - Auto-rotate image (default: true)
     * - width: int - Maximum width in pixels
     * - height: int - Maximum height in pixels
     * - preserveAspectRatio: bool (default: true)
     */
    public function takePhoto(array $options = []): CameraResult
    {
        $result = $this->invoke('takePhoto', $options);

        return new CameraResult($result);
    }

    /**
     * Pick a photo from the device gallery.
     *
     * @param  array  $options  Gallery options
     * @return CameraResult Photo information
     *
     * @throws TauriPhpException
     */
    public function pickPhoto(array $options = []): CameraResult
    {
        $result = $this->invoke('pickPhoto', $options);

        return new CameraResult($result);
    }

    /**
     * Pick multiple photos from the device gallery.
     *
     * @param  array  $options  Gallery options
     * @return array Array of CameraResult objects
     *
     * @throws TauriPhpException
     *
     * Options:
     * - limit: int - Maximum number of photos (default: 10)
     */
    public function pickMultiplePhotos(array $options = []): array
    {
        $results = $this->invoke('pickMultiplePhotos', $options);

        return array_map(
            fn ($result) => new CameraResult($result),
            $results['photos'] ?? []
        );
    }

    /**
     * Get camera permissions status.
     *
     * @return array Permissions status
     *
     * @throws TauriPhpException
     */
    public function getPermissions(): array
    {
        return $this->checkPermissions();
    }
}
