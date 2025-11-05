<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FrankenPhpBuilder
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
     * The EnvTauriManager instance.
     *
     * @var EnvTauriManager
     */
    protected $envManager;

    /**
     * Create a new FrankenPhpBuilder instance.
     */
    public function __construct(EnvTauriManager $envManager, ?string $basePath = null)
    {
        $this->filesystem = new Filesystem;
        $this->basePath = $basePath ?? base_path();
        $this->envManager = $envManager;
    }

    /**
     * Build FrankenPHP binary for a specific platform.
     *
     *
     * @throws TauriPhpException
     */
    public function build(string $platform, string $targetTriple, array $options = []): string
    {
        $env = $this->envManager->all();

        $phpVersion = $env['TAURI_PHP_VERSION'] ?? '8.3';
        $phpExtensions = $env['TAURI_PHP_EXTENSIONS'] ?? 'opcache,pdo_sqlite,mbstring';
        $frankenphpVersion = $env['TAURI_FRANKENPHP_VERSION'] ?? 'latest';

        $binariesDir = $this->basePath.'/binaries';

        if (! is_dir($binariesDir)) {
            $this->filesystem->mkdir($binariesDir, 0755);
        }

        $binaryName = $this->getBinaryName($targetTriple);
        $binaryPath = $binariesDir.'/'.$binaryName;

        // Check if binary already exists
        if (file_exists($binaryPath) && ! ($options['force'] ?? false)) {
            return $binaryPath;
        }

        // Determine build strategy
        if ($this->isCurrentPlatform($platform)) {
            return $this->buildNative($platform, $targetTriple, $phpVersion, $phpExtensions, $frankenphpVersion, $binaryPath, $options);
        } else {
            return $this->buildWithDocker($platform, $targetTriple, $phpVersion, $phpExtensions, $frankenphpVersion, $binaryPath, $options);
        }
    }

    /**
     * Build FrankenPHP natively on the current platform.
     *
     *
     * @throws TauriPhpException
     */
    protected function buildNative(
        string $platform,
        string $targetTriple,
        string $phpVersion,
        string $phpExtensions,
        string $frankenphpVersion,
        string $binaryPath,
        array $options
    ): string {
        $tempDir = $this->basePath.'/tauri-temp';
        $frankenphpDir = $tempDir.'/frankenphp';

        // Clone FrankenPHP repository if needed
        if (! is_dir($frankenphpDir)) {
            $this->filesystem->mkdir($tempDir, 0755);

            $process = new Process([
                'git',
                'clone',
                '--depth=1',
                '--branch='.$frankenphpVersion,
                'https://github.com/dunglas/frankenphp.git',
                $frankenphpDir,
            ]);

            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                throw TauriPhpException::buildFailed($platform, 'Failed to clone FrankenPHP repository');
            }
        }

        // Copy Laravel app to dist/app
        $appSourceDir = $this->basePath.'/tauri-temp/app-embedded';
        $appDestDir = $frankenphpDir.'/dist/app';

        if (! is_dir($appSourceDir)) {
            throw TauriPhpException::buildFailed($platform, 'Embedded app directory not found');
        }

        $this->filesystem->remove($appDestDir);
        $this->filesystem->mirror($appSourceDir, $appDestDir);

        // Run build-static.sh
        $buildScript = $frankenphpDir.'/build-static.sh';

        if (! file_exists($buildScript)) {
            throw TauriPhpException::buildFailed($platform, 'build-static.sh not found');
        }

        $env = [
            'PHP_VERSION' => $phpVersion,
            'PHP_EXTENSIONS' => $phpExtensions,
            'EMBED' => 'dist/app',
        ];

        $process = new Process(['bash', $buildScript], $frankenphpDir, $env);
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) use ($options) {
            if ($options['verbose'] ?? false) {
                echo $buffer;
            }
        });

        if (! $process->isSuccessful()) {
            throw TauriPhpException::buildFailed($platform, 'FrankenPHP build failed: '.$process->getErrorOutput());
        }

        // Find and copy the built binary
        $builtBinary = $frankenphpDir.'/dist/frankenphp-linux-x86_64';

        if (! file_exists($builtBinary)) {
            throw TauriPhpException::buildFailed($platform, 'Built binary not found');
        }

        $this->filesystem->copy($builtBinary, $binaryPath);
        $this->filesystem->chmod($binaryPath, 0755);

        return $binaryPath;
    }

    /**
     * Build FrankenPHP using Docker for cross-compilation.
     *
     *
     * @throws TauriPhpException
     */
    protected function buildWithDocker(
        string $platform,
        string $targetTriple,
        string $phpVersion,
        string $phpExtensions,
        string $frankenphpVersion,
        string $binaryPath,
        array $options
    ): string {
        // Check if Docker is available
        $process = new Process(['docker', '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw TauriPhpException::prerequisiteMissing('Docker', 'Install Docker from https://www.docker.com/');
        }

        $appSourceDir = $this->basePath.'/tauri-temp/app-embedded';
        $binariesDir = dirname($binaryPath);

        // Determine Docker image based on platform
        $dockerImage = str_contains($targetTriple, 'musl')
            ? 'dunglas/frankenphp:static-builder'
            : 'dunglas/frankenphp:static-builder-gnu';

        // Build using Docker
        $command = [
            'docker',
            'run',
            '--rm',
            '-v',
            $appSourceDir.':/go/src/app/dist/app:ro',
            '-v',
            $binariesDir.':/output',
            '-e',
            'PHP_VERSION='.$phpVersion,
            '-e',
            'PHP_EXTENSIONS='.$phpExtensions,
            '-e',
            'EMBED=dist/app',
            $dockerImage,
            'sh',
            '-c',
            './build-static.sh && cp dist/frankenphp-* /output/'.basename($binaryPath),
        ];

        $process = new Process($command, $this->basePath);
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) use ($options) {
            if ($options['verbose'] ?? false) {
                echo $buffer;
            }
        });

        if (! $process->isSuccessful()) {
            throw TauriPhpException::buildFailed($platform, 'Docker build failed: '.$process->getErrorOutput());
        }

        if (! file_exists($binaryPath)) {
            throw TauriPhpException::buildFailed($platform, 'Built binary not found after Docker build');
        }

        $this->filesystem->chmod($binaryPath, 0755);

        return $binaryPath;
    }

    /**
     * Verify that a binary is valid.
     */
    public function verifyBinary(string $binaryPath): bool
    {
        if (! file_exists($binaryPath)) {
            return false;
        }

        // Check file size (should be at least 10MB for a valid binary)
        $size = filesize($binaryPath);

        if ($size < 10 * 1024 * 1024) {
            return false;
        }

        // Check if file is executable
        if (! is_executable($binaryPath)) {
            return false;
        }

        return true;
    }

    /**
     * Get the binary name for a target triple.
     */
    protected function getBinaryName(string $targetTriple): string
    {
        $name = 'frankenphp-'.$targetTriple;

        if (str_contains($targetTriple, 'windows')) {
            $name .= '.exe';
        }

        return $name;
    }

    /**
     * Check if we're building for the current platform.
     */
    protected function isCurrentPlatform(string $platform): bool
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        // Map current system to platform identifier
        $currentPlatform = match ($os) {
            'Linux' => str_contains($arch, 'aarch64') ? 'linux-arm64' : 'linux-x64',
            'Darwin' => str_contains($arch, 'arm64') ? 'macos-arm64' : 'macos-x64',
            'Windows' => 'windows-x64',
            default => 'unknown',
        };

        return $currentPlatform === $platform;
    }
}
