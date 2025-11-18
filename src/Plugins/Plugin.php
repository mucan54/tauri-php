<?php

namespace Mucan54\TauriPhp\Plugins;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;

/**
 * Base class for all Tauri mobile plugins.
 *
 * Provides a bridge between PHP/Laravel and Tauri mobile plugins
 * running in the WebView via JavaScript commands.
 */
abstract class Plugin
{
    /**
     * The plugin name (used for JS bridge).
     *
     * @var string
     */
    protected $pluginName;

    /**
     * Check if running in Tauri mobile environment.
     */
    protected function isTauriMobile(): bool
    {
        // Check if window.__TAURI__ exists via JavaScript injection
        // This would be set by the frontend JavaScript
        return session()->has('tauri_mobile_active');
    }

    /**
     * Execute a plugin command.
     *
     * @param  string  $command  The command name
     * @param  array  $args  Command arguments
     * @return array Response from the plugin
     *
     * @throws TauriPhpException
     */
    protected function invoke(string $command, array $args = []): array
    {
        if (! $this->isTauriMobile()) {
            throw TauriPhpException::pluginError(
                $this->pluginName,
                'Tauri mobile environment not detected'
            );
        }

        // This will be called via JavaScript bridge
        // The actual implementation depends on frontend integration
        // For now, we'll use session to simulate the call
        $sessionKey = "tauri_plugin_{$this->pluginName}_{$command}";

        if (! session()->has($sessionKey)) {
            throw TauriPhpException::pluginError(
                $this->pluginName,
                "Command '{$command}' not available"
            );
        }

        return session()->get($sessionKey, []);
    }

    /**
     * Request permissions for this plugin.
     *
     * @return array Permission status
     *
     * @throws TauriPhpException
     */
    public function requestPermissions(): array
    {
        return $this->invoke('requestPermissions', []);
    }

    /**
     * Check current permissions for this plugin.
     *
     * @return array Permission status
     *
     * @throws TauriPhpException
     */
    public function checkPermissions(): array
    {
        return $this->invoke('checkPermissions', []);
    }

    /**
     * Get the plugin name.
     */
    public function getPluginName(): string
    {
        return $this->pluginName;
    }
}
