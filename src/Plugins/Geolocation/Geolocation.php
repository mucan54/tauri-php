<?php

namespace Mucan54\TauriPhp\Plugins\Geolocation;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Plugins\Plugin;

/**
 * Geolocation plugin for Tauri mobile applications.
 *
 * Provides access to device location services:
 * - Get current position
 * - Watch position changes
 * - Geocoding and reverse geocoding
 */
class Geolocation extends Plugin
{
    /**
     * The plugin name.
     *
     * @var string
     */
    protected $pluginName = 'geolocation';

    /**
     * Get the current device position.
     *
     * @param  array  $options  Position options
     * @return Position Current position
     *
     * @throws TauriPhpException
     *
     * Options:
     * - enableHighAccuracy: bool - Use GPS (default: false)
     * - timeout: int - Timeout in milliseconds (default: 10000)
     * - maximumAge: int - Maximum cached position age in milliseconds
     */
    public function getCurrentPosition(array $options = []): Position
    {
        $result = $this->invoke('getCurrentPosition', $options);

        return new Position($result);
    }

    /**
     * Start watching position changes.
     *
     * @param  array  $options  Watch options
     * @param  callable  $callback  Callback for position updates
     * @return string Watch ID
     *
     * @throws TauriPhpException
     */
    public function watchPosition(array $options, callable $callback): string
    {
        $result = $this->invoke('watchPosition', $options);

        // Store callback for later use
        session()->put("geolocation_watch_{$result['id']}", $callback);

        return $result['id'];
    }

    /**
     * Stop watching position changes.
     *
     * @param  string  $watchId  Watch ID returned from watchPosition()
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function clearWatch(string $watchId): array
    {
        session()->forget("geolocation_watch_{$watchId}");

        return $this->invoke('clearWatch', ['id' => $watchId]);
    }

    /**
     * Check if location services are enabled.
     *
     * @return bool Location services status
     *
     * @throws TauriPhpException
     */
    public function isLocationEnabled(): bool
    {
        $result = $this->invoke('isLocationEnabled', []);

        return $result['enabled'] ?? false;
    }

    /**
     * Open device location settings.
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function openLocationSettings(): array
    {
        return $this->invoke('openLocationSettings', []);
    }
}
