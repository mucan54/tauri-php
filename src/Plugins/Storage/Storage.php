<?php

namespace Mucan54\TauriPhp\Plugins\Storage;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Plugins\Plugin;

/**
 * Storage plugin for Tauri mobile applications.
 *
 * Provides persistent key-value storage:
 * - Store and retrieve data
 * - Support for complex data types (auto JSON encode/decode)
 * - Encrypted storage support
 */
class Storage extends Plugin
{
    /**
     * The plugin name.
     *
     * @var string
     */
    protected $pluginName = 'storage';

    /**
     * Set a value in storage.
     *
     * @param  string  $key  Storage key
     * @param  mixed  $value  Value to store (will be JSON encoded)
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function set(string $key, $value): array
    {
        return $this->invoke('set', [
            'key' => $key,
            'value' => json_encode($value),
        ]);
    }

    /**
     * Get a value from storage.
     *
     * @param  string  $key  Storage key
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed Stored value (JSON decoded) or default
     *
     * @throws TauriPhpException
     */
    public function get(string $key, $default = null)
    {
        try {
            $result = $this->invoke('get', ['key' => $key]);

            if (! isset($result['value'])) {
                return $default;
            }

            $decoded = json_decode($result['value'], true);

            return $decoded !== null ? $decoded : $default;
        } catch (TauriPhpException $e) {
            return $default;
        }
    }

    /**
     * Remove a value from storage.
     *
     * @param  string  $key  Storage key
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function remove(string $key): array
    {
        return $this->invoke('remove', ['key' => $key]);
    }

    /**
     * Clear all values from storage.
     *
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function clear(): array
    {
        return $this->invoke('clear', []);
    }

    /**
     * Get all keys in storage.
     *
     * @return array List of keys
     *
     * @throws TauriPhpException
     */
    public function keys(): array
    {
        $result = $this->invoke('keys', []);

        return $result['keys'] ?? [];
    }

    /**
     * Get number of items in storage.
     *
     * @return int Number of items
     *
     * @throws TauriPhpException
     */
    public function length(): int
    {
        $result = $this->invoke('length', []);

        return $result['length'] ?? 0;
    }

    /**
     * Check if a key exists in storage.
     *
     * @param  string  $key  Storage key
     * @return bool True if key exists
     *
     * @throws TauriPhpException
     */
    public function has(string $key): bool
    {
        return in_array($key, $this->keys());
    }

    /**
     * Set multiple values at once.
     *
     * @param  array  $items  Key-value pairs to store
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function setMultiple(array $items): array
    {
        $encoded = [];
        foreach ($items as $key => $value) {
            $encoded[$key] = json_encode($value);
        }

        return $this->invoke('setMultiple', ['items' => $encoded]);
    }

    /**
     * Get multiple values at once.
     *
     * @param  array  $keys  Array of keys to retrieve
     * @return array Key-value pairs
     *
     * @throws TauriPhpException
     */
    public function getMultiple(array $keys): array
    {
        $result = $this->invoke('getMultiple', ['keys' => $keys]);

        $decoded = [];
        foreach ($result['items'] ?? [] as $key => $value) {
            $decoded[$key] = json_decode($value, true);
        }

        return $decoded;
    }

    /**
     * Remove multiple keys at once.
     *
     * @param  array  $keys  Array of keys to remove
     * @return array Operation result
     *
     * @throws TauriPhpException
     */
    public function removeMultiple(array $keys): array
    {
        return $this->invoke('removeMultiple', ['keys' => $keys]);
    }
}
