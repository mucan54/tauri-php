<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Symfony\Component\Filesystem\Filesystem;

class StubManager
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The stubs directory path.
     *
     * @var string
     */
    protected $stubsPath;

    /**
     * Create a new StubManager instance.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->stubsPath = __DIR__.'/../../stubs';
    }

    /**
     * Get the content of a stub file with replacements.
     *
     * @param  string  $stub
     * @param  array  $replacements
     * @return string
     *
     * @throws TauriPhpException
     */
    public function get(string $stub, array $replacements = []): string
    {
        $path = $this->getStubPath($stub);

        if (!$this->exists($stub)) {
            throw TauriPhpException::fileOperationFailed('read', $path, 'Stub file does not exist');
        }

        $content = file_get_contents($path);

        return $this->replaceVariables($content, $replacements);
    }

    /**
     * Copy a stub file to a destination with replacements.
     *
     * @param  string  $stub
     * @param  string  $destination
     * @param  array  $replacements
     * @return void
     *
     * @throws TauriPhpException
     */
    public function copy(string $stub, string $destination, array $replacements = []): void
    {
        $content = $this->get($stub, $replacements);

        $destinationDir = dirname($destination);

        if (!is_dir($destinationDir)) {
            $this->filesystem->mkdir($destinationDir, 0755);
        }

        if (file_put_contents($destination, $content) === false) {
            throw TauriPhpException::fileOperationFailed('write', $destination);
        }
    }

    /**
     * Copy an entire directory recursively with replacements.
     *
     * @param  string  $stubDir
     * @param  string  $destination
     * @param  array  $replacements
     * @return void
     *
     * @throws TauriPhpException
     */
    public function copyDirectory(string $stubDir, string $destination, array $replacements = []): void
    {
        $sourcePath = $this->getStubPath($stubDir);

        if (!is_dir($sourcePath)) {
            throw TauriPhpException::fileOperationFailed('read', $sourcePath, 'Directory does not exist');
        }

        if (!is_dir($destination)) {
            $this->filesystem->mkdir($destination, 0755);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($sourcePath) + 1);
            $destinationPath = $destination.'/'.$relativePath;

            if ($item->isDir()) {
                $this->filesystem->mkdir($destinationPath, 0755);
            } else {
                $content = file_get_contents($item->getPathname());
                $content = $this->replaceVariables($content, $replacements);

                if (file_put_contents($destinationPath, $content) === false) {
                    throw TauriPhpException::fileOperationFailed('write', $destinationPath);
                }
            }
        }
    }

    /**
     * Check if a stub exists.
     *
     * @param  string  $stub
     * @return bool
     */
    public function exists(string $stub): bool
    {
        return file_exists($this->getStubPath($stub));
    }

    /**
     * Replace variables in content.
     *
     * @param  string  $content
     * @param  array  $replacements
     * @return string
     */
    protected function replaceVariables(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return $content;
    }

    /**
     * Get the full path to a stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function getStubPath(string $stub): string
    {
        // Remove leading slash if present
        $stub = ltrim($stub, '/');

        return $this->stubsPath.'/'.$stub;
    }

    /**
     * Get the stubs directory path.
     *
     * @return string
     */
    public function getStubsPath(): string
    {
        return $this->stubsPath;
    }
}
