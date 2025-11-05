<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;

class EnvTauriManager
{
    /**
     * The path to the .env.tauri file.
     *
     * @var string
     */
    protected $envPath;

    /**
     * The loaded environment variables.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Whether the environment file has been loaded.
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Create a new EnvTauriManager instance.
     *
     * @param  string|null  $basePath
     */
    public function __construct(?string $basePath = null)
    {
        $basePath = $basePath ?? base_path();
        $this->envPath = $basePath.'/.env.tauri';
    }

    /**
     * Create .env.tauri file from template.
     *
     * @param  string  $appName
     * @param  array  $options
     * @return void
     *
     * @throws TauriPhpException
     */
    public function createFromTemplate(string $appName, array $options = []): void
    {
        $stubManager = new StubManager();

        $replacements = array_merge([
            'APP_NAME' => $appName,
            'APP_IDENTIFIER' => $options['identifier'] ?? 'com.example.'.strtolower(str_replace(' ', '', $appName)),
            'APP_VERSION' => $options['version'] ?? '0.1.0',
            'WINDOW_TITLE' => $options['window_title'] ?? $appName,
            'WINDOW_WIDTH' => $options['window_width'] ?? '1200',
            'WINDOW_HEIGHT' => $options['window_height'] ?? '800',
            'DEV_HOST' => $options['dev_host'] ?? '127.0.0.1',
            'DEV_PORT' => $options['dev_port'] ?? '8080',
            'PHP_VERSION' => $options['php_version'] ?? '8.3',
            'PHP_EXTENSIONS' => $options['php_extensions'] ?? 'opcache,pdo_sqlite,mbstring,openssl,tokenizer,xml,ctype,json,bcmath,fileinfo',
            'FRANKENPHP_VERSION' => $options['frankenphp_version'] ?? 'latest',
        ], $options);

        $stubManager->copy('env/tauri.env', $this->envPath, $replacements);

        $this->loaded = false;
        $this->variables = [];
    }

    /**
     * Load the .env.tauri file.
     *
     * @return array
     *
     * @throws TauriPhpException
     */
    public function load(): array
    {
        if (!$this->exists()) {
            throw TauriPhpException::configurationError('.env.tauri file not found. Run tauri:init first.');
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->variables = [];

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes
                $value = trim($value, '"\'');

                $this->variables[$key] = $value;

                // Also set as environment variable
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        $this->loaded = true;

        return $this->variables;
    }

    /**
     * Get a configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->variables[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param  string  $key
     * @param  string  $value
     * @return void
     *
     * @throws TauriPhpException
     */
    public function set(string $key, string $value): void
    {
        if (!$this->exists()) {
            throw TauriPhpException::configurationError('.env.tauri file not found.');
        }

        $content = file_get_contents($this->envPath);

        // Check if key exists
        $pattern = '/^'.$key.'=.*/m';

        if (preg_match($pattern, $content)) {
            // Update existing key
            $content = preg_replace($pattern, $key.'='.$value, $content);
        } else {
            // Append new key
            $content .= "\n".$key.'='.$value;
        }

        if (file_put_contents($this->envPath, $content) === false) {
            throw TauriPhpException::fileOperationFailed('write', $this->envPath);
        }

        // Update loaded variables
        $this->variables[$key] = $value;
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * Check if .env.tauri exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->envPath);
    }

    /**
     * Get all configuration values.
     *
     * @return array
     */
    public function all(): array
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->variables;
    }

    /**
     * Get the path to the .env.tauri file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->envPath;
    }
}
