<?php

namespace Mucan54\TauriPhp\Plugins\Vibration;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Plugins\Plugin;

/**
 * Vibration plugin for Tauri mobile applications.
 *
 * Provides access to device vibration functionality:
 * - Trigger vibration
 * - Custom vibration patterns
 * - Cancel ongoing vibration
 */
class Vibration extends Plugin
{
    /**
     * The plugin name.
     *
     * @var string
     */
    protected $pluginName = 'vibration';

    /**
     * Trigger a simple vibration.
     *
     * @param  int  $duration  Duration in milliseconds (default: 300)
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function vibrate(int $duration = 300): array
    {
        return $this->invoke('vibrate', ['duration' => $duration]);
    }

    /**
     * Trigger a vibration pattern.
     *
     * @param  array  $pattern  Array of durations [vibrate, pause, vibrate, ...]
     * @return array Operation result
     *
     * @throws TauriPhpException
     *
     * Example: [100, 200, 100] = vibrate 100ms, pause 200ms, vibrate 100ms
     */
    public function vibratePattern(array $pattern): array
    {
        return $this->invoke('vibratePattern', ['pattern' => $pattern]);
    }

    /**
     * Cancel any ongoing vibration.
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function cancel(): array
    {
        return $this->invoke('cancel', []);
    }

    /**
     * Impact vibration (iOS Taptic Engine / Android equivalent).
     *
     * @param  string  $style  'light'|'medium'|'heavy' (default: 'medium')
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function impact(string $style = 'medium'): array
    {
        return $this->invoke('impact', ['style' => $style]);
    }

    /**
     * Notification vibration feedback.
     *
     * @param  string  $type  'success'|'warning'|'error' (default: 'success')
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function notification(string $type = 'success'): array
    {
        return $this->invoke('notification', ['type' => $type]);
    }

    /**
     * Selection vibration feedback (subtle tick).
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function selection(): array
    {
        return $this->invoke('selection', []);
    }
}
