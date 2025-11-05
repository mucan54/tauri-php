<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;

class TauriConfigGenerator
{
    /**
     * The EnvTauriManager instance.
     *
     * @var EnvTauriManager
     */
    protected $envManager;

    /**
     * The path to the tauri.conf.json file.
     *
     * @var string
     */
    protected $configPath;

    /**
     * Create a new TauriConfigGenerator instance.
     *
     * @param  EnvTauriManager  $envManager
     * @param  string|null  $basePath
     */
    public function __construct(EnvTauriManager $envManager, ?string $basePath = null)
    {
        $this->envManager = $envManager;
        $basePath = $basePath ?? base_path();
        $this->configPath = $basePath.'/src-tauri/tauri.conf.json';
    }

    /**
     * Generate tauri.conf.json from .env.tauri.
     *
     * @param  array  $overrides
     * @return array
     *
     * @throws TauriPhpException
     */
    public function generate(array $overrides = []): array
    {
        $env = $this->envManager->all();

        $config = [
            'productName' => $env['TAURI_APP_NAME'] ?? 'My Desktop App',
            'version' => $env['TAURI_APP_VERSION'] ?? '0.1.0',
            'identifier' => $env['TAURI_APP_IDENTIFIER'] ?? 'com.example.myapp',
            'build' => [
                'beforeDevCommand' => '',
                'beforeBuildCommand' => 'php artisan config:cache && php artisan route:cache && php artisan view:cache',
                'devUrl' => 'http://'.$env['TAURI_DEV_HOST'].':'.$env['TAURI_DEV_PORT'],
                'frontendDist' => '../desktop-frontend',
            ],
            'bundle' => [
                'active' => true,
                'targets' => 'all',
                'icon' => [
                    'src-tauri/icons/32x32.png',
                    'src-tauri/icons/128x128.png',
                    'src-tauri/icons/128x128@2x.png',
                    'src-tauri/icons/icon.icns',
                    'src-tauri/icons/icon.ico',
                ],
                'resources' => [],
                'externalBin' => [
                    'binaries/frankenphp',
                ],
                'copyright' => '',
                'category' => 'DeveloperTool',
                'shortDescription' => '',
                'longDescription' => '',
            ],
            'app' => [
                'windows' => [
                    [
                        'title' => $env['TAURI_WINDOW_TITLE'] ?? 'My Desktop App',
                        'width' => (int) ($env['TAURI_WINDOW_WIDTH'] ?? 1200),
                        'height' => (int) ($env['TAURI_WINDOW_HEIGHT'] ?? 800),
                        'resizable' => filter_var($env['TAURI_WINDOW_RESIZABLE'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'fullscreen' => filter_var($env['TAURI_WINDOW_FULLSCREEN'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ],
                ],
                'security' => [
                    'csp' => null,
                ],
            ],
            'plugins' => [
                'shell' => [
                    'open' => true,
                ],
            ],
        ];

        // Apply overrides
        $config = array_replace_recursive($config, $overrides);

        return $config;
    }

    /**
     * Update configuration for development mode.
     *
     * @param  string  $host
     * @param  int  $port
     * @return void
     *
     * @throws TauriPhpException
     */
    public function updateForDevelopment(string $host, int $port): void
    {
        $config = $this->generate([
            'build' => [
                'devUrl' => "http://{$host}:{$port}",
                'beforeDevCommand' => '',
            ],
        ]);

        $this->save($config);
    }

    /**
     * Update configuration for production mode.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    public function updateForProduction(): void
    {
        $config = $this->generate([
            'build' => [
                'beforeBuildCommand' => 'php artisan config:cache && php artisan route:cache && php artisan view:cache',
            ],
        ]);

        $this->save($config);
    }

    /**
     * Save configuration to tauri.conf.json.
     *
     * @param  array  $config
     * @return void
     *
     * @throws TauriPhpException
     */
    public function save(array $config): void
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw TauriPhpException::configurationError('Failed to encode configuration to JSON');
        }

        $directory = dirname($this->configPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_put_contents($this->configPath, $json) === false) {
            throw TauriPhpException::fileOperationFailed('write', $this->configPath);
        }
    }

    /**
     * Load configuration from tauri.conf.json.
     *
     * @return array
     *
     * @throws TauriPhpException
     */
    public function load(): array
    {
        if (!file_exists($this->configPath)) {
            throw TauriPhpException::fileOperationFailed('read', $this->configPath, 'File does not exist');
        }

        $json = file_get_contents($this->configPath);
        $config = json_decode($json, true);

        if ($config === null) {
            throw TauriPhpException::configurationError('Failed to parse tauri.conf.json');
        }

        return $config;
    }

    /**
     * Check if tauri.conf.json exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->configPath);
    }
}
