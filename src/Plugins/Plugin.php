<?php

namespace Mucan54\TauriPhp\Plugins;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Http\Controllers\TauriPluginController;

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
     * Plugin call timeout in seconds.
     *
     * @var int
     */
    protected $timeout = 30;

    /**
     * Check if running in Tauri mobile environment.
     */
    protected function isTauriMobile(): bool
    {
        $sessionId = session()->getId();

        return TauriPluginController::isTauriActive($sessionId);
    }

    /**
     * Execute a plugin command.
     *
     * This method queues a plugin call for the JavaScript bridge to pick up,
     * then waits for the result to be returned.
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
                'Tauri mobile environment not detected. Ensure the Tauri bridge is initialized in your frontend.'
            );
        }

        $sessionId = session()->getId();

        // Queue the plugin call for the JavaScript bridge
        $callId = TauriPluginController::queuePluginCall(
            $this->pluginName,
            $command,
            $args,
            $sessionId
        );

        try {
            // Wait for the result from the JavaScript bridge
            return TauriPluginController::waitForResult(
                $callId,
                $sessionId,
                $this->timeout
            );
        } catch (\Exception $e) {
            throw TauriPhpException::pluginError(
                $this->pluginName,
                $e->getMessage()
            );
        }
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
