<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Symfony\Component\Filesystem\Filesystem;

class TauriPhpService
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new TauriPhpService instance.
     *
     * @param  string|null  $basePath
     */
    public function __construct(?string $basePath = null)
    {
        $this->filesystem = new Filesystem();
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Initialize a new Tauri project.
     *
     * @param  string  $appName
     * @param  array  $options
     * @return void
     *
     * @throws TauriPhpException
     */
    public function initialize(string $appName, array $options = []): void
    {
        // This method will be called by InitCommand
        // The actual logic will be in the command itself
    }

    /**
     * Build the Tauri application for specified platforms.
     *
     * @param  string|array  $platforms
     * @param  array  $options
     * @return array
     *
     * @throws TauriPhpException
     */
    public function build($platforms = 'all', array $options = []): array
    {
        // This method will be called by BuildCommand
        // The actual logic will be in the command itself
        return [];
    }

    /**
     * Start the development server.
     *
     * @param  array  $options
     * @return void
     *
     * @throws TauriPhpException
     */
    public function dev(array $options = []): void
    {
        // This method will be called by DevCommand
        // The actual logic will be in the command itself
    }

    /**
     * Create distribution packages.
     *
     * @param  array  $options
     * @return array
     *
     * @throws TauriPhpException
     */
    public function package(array $options = []): array
    {
        // This method will be called by PackageCommand
        // The actual logic will be in the command itself
        return [];
    }

    /**
     * Get the package version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Check if Tauri is initialized in the project.
     *
     * @return bool
     */
    public function isTauriInitialized(): bool
    {
        $tauriDir = $this->basePath.'/src-tauri';
        $configFile = $tauriDir.'/tauri.conf.json';
        $cargoFile = $tauriDir.'/Cargo.toml';

        return is_dir($tauriDir) && file_exists($configFile) && file_exists($cargoFile);
    }

    /**
     * Get build information.
     *
     * @return array
     */
    public function getBuildInfo(): array
    {
        $info = [
            'initialized' => $this->isTauriInitialized(),
            'version' => $this->getVersion(),
        ];

        if ($info['initialized']) {
            $envManager = new EnvTauriManager($this->basePath);

            if ($envManager->exists()) {
                $env = $envManager->all();
                $info['app_name'] = $env['TAURI_APP_NAME'] ?? 'Unknown';
                $info['app_version'] = $env['TAURI_APP_VERSION'] ?? '0.1.0';
                $info['app_identifier'] = $env['TAURI_APP_IDENTIFIER'] ?? 'Unknown';
            }

            // Check for built binaries
            $binariesDir = $this->basePath.'/binaries';

            if (is_dir($binariesDir)) {
                $binaries = glob($binariesDir.'/frankenphp-*');
                $info['binaries'] = array_map('basename', $binaries);
            }

            // Check for build artifacts
            $buildDir = $this->basePath.'/src-tauri/target/release/bundle';

            if (is_dir($buildDir)) {
                $info['has_build_artifacts'] = true;
            }
        }

        return $info;
    }

    /**
     * Clean build artifacts and temporary files.
     *
     * @return void
     */
    public function clean(): void
    {
        // This method will be called by CleanCommand
        // The actual logic will be in the command itself
    }

    /**
     * Prepare the Laravel application for embedding.
     *
     * @param  bool  $optimize
     * @return string
     *
     * @throws TauriPhpException
     */
    public function prepareEmbeddedApp(bool $optimize = true): string
    {
        $tempDir = $this->basePath.'/tauri-temp';
        $appDir = $tempDir.'/app-embedded';

        // Remove old directory if exists
        if (is_dir($appDir)) {
            $this->filesystem->remove($appDir);
        }

        $this->filesystem->mkdir($appDir, 0755);

        // Copy Laravel application
        $this->copyLaravelApp($this->basePath, $appDir);

        // Create embedded .env file
        $this->createEmbeddedEnv($appDir);

        return $appDir;
    }

    /**
     * Copy Laravel application to destination.
     *
     * @param  string  $source
     * @param  string  $destination
     * @return void
     */
    protected function copyLaravelApp(string $source, string $destination): void
    {
        $excludePaths = [
            '.git',
            '.github',
            'node_modules',
            'tauri-temp',
            'src-tauri',
            'desktop-frontend',
            'binaries',
            'tauri-builds',
            'tests',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);

            // Skip excluded paths
            $skip = false;

            foreach ($excludePaths as $excludePath) {
                if (str_starts_with($relativePath, $excludePath)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $destinationPath = $destination.'/'.$relativePath;

            if ($item->isDir()) {
                $this->filesystem->mkdir($destinationPath, 0755);
            } else {
                $this->filesystem->copy($item->getPathname(), $destinationPath);
            }
        }

        // Create necessary storage directories
        $storageDirs = [
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ];

        foreach ($storageDirs as $dir) {
            $this->filesystem->mkdir($destination.'/'.$dir, 0755);
        }
    }

    /**
     * Create embedded .env file.
     *
     * @param  string  $appDir
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function createEmbeddedEnv(string $appDir): void
    {
        $stubManager = new StubManager();

        $appKey = $this->getAppKey();

        $replacements = [
            'APP_NAME' => config('app.name', 'Laravel'),
            'APP_KEY' => $appKey,
        ];

        $stubManager->copy('env/embedded.env', $appDir.'/.env', $replacements);
    }

    /**
     * Get or generate application key.
     *
     * @return string
     */
    protected function getAppKey(): string
    {
        $key = config('app.key');

        if (empty($key)) {
            // Generate a new key
            $key = 'base64:'.base64_encode(random_bytes(32));
        }

        return $key;
    }
}
