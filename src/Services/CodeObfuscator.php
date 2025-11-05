<?php

namespace Mucan54\TauriPhp\Services;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

class CodeObfuscator
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
     * The obfuscation tool to use.
     *
     * @var string
     */
    protected $tool;

    /**
     * Paths to exclude from obfuscation.
     *
     * @var array
     */
    protected $excludePaths = [];

    /**
     * Create a new CodeObfuscator instance.
     *
     * @param  string|null  $basePath
     * @param  string  $tool
     */
    public function __construct(?string $basePath = null, string $tool = 'yakpro-po')
    {
        $this->filesystem = new Filesystem();
        $this->basePath = $basePath ?? base_path();
        $this->tool = $tool;
        $this->excludePaths = config('tauri-php.obfuscation.exclude_paths', [
            'vendor',
            'storage',
            'bootstrap/cache',
        ]);
    }

    /**
     * Obfuscate PHP code in the embedded app directory.
     *
     * @return void
     *
     * @throws TauriPhpException
     */
    public function obfuscate(): void
    {
        $appDir = $this->basePath.'/tauri-temp/app-embedded';

        if (!is_dir($appDir)) {
            throw TauriPhpException::fileOperationFailed('read', $appDir, 'Directory does not exist');
        }

        switch ($this->tool) {
            case 'yakpro-po':
                $this->obfuscateWithYakProPo($appDir);
                break;

            default:
                throw TauriPhpException::configurationError("Unsupported obfuscation tool: {$this->tool}");
        }
    }

    /**
     * Obfuscate code using YakPro-Po.
     *
     * @param  string  $appDir
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function obfuscateWithYakProPo(string $appDir): void
    {
        $tempDir = $this->basePath.'/tauri-temp';
        $yakProPoDir = $tempDir.'/yakpro-po';

        // Clone YakPro-Po if not already present
        if (!is_dir($yakProPoDir)) {
            $process = new Process([
                'git',
                'clone',
                '--depth=1',
                'https://github.com/pk-fr/yakpro-po.git',
                $yakProPoDir,
            ]);

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw TauriPhpException::buildFailed('obfuscation', 'Failed to clone YakPro-Po repository');
            }
        }

        // Create obfuscated output directory
        $obfuscatedDir = $tempDir.'/app-obfuscated';
        $this->filesystem->remove($obfuscatedDir);
        $this->filesystem->mkdir($obfuscatedDir, 0755);

        // Generate YakPro-Po configuration
        $configPath = $yakProPoDir.'/yakpro-po.cnf';
        $this->generateYakProPoConfig($configPath, $appDir, $obfuscatedDir);

        // Run obfuscation
        $process = new Process([
            'php',
            $yakProPoDir.'/yakpro-po.php',
            $appDir,
        ], $yakProPoDir);

        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw TauriPhpException::buildFailed('obfuscation', 'YakPro-Po obfuscation failed: '.$process->getErrorOutput());
        }

        // Replace original with obfuscated
        $this->filesystem->remove($appDir);
        $this->filesystem->rename($obfuscatedDir, $appDir);
    }

    /**
     * Generate YakPro-Po configuration file.
     *
     * @param  string  $configPath
     * @param  string  $sourceDir
     * @param  string  $targetDir
     * @return void
     *
     * @throws TauriPhpException
     */
    protected function generateYakProPoConfig(string $configPath, string $sourceDir, string $targetDir): void
    {
        $excludePatterns = array_map(function ($path) use ($sourceDir) {
            return "'{$sourceDir}/{$path}/*'";
        }, $this->excludePaths);

        $config = <<<PHP
<?php
// YakPro-Po Configuration

\$conf->t_scramble_mode                     = 'identifier';
\$conf->scramble_length                    = 8;
\$conf->scramble_variable                  = true;
\$conf->scramble_function                  = true;
\$conf->scramble_class                     = true;
\$conf->scramble_class_name               = true;
\$conf->scramble_method                    = true;
\$conf->scramble_property                  = true;
\$conf->scramble_constant                  = false;
\$conf->scramble_string                    = false;

\$conf->strip_indentation                  = true;
\$conf->strip_comments                     = true;

\$conf->source_directory                   = '{$sourceDir}';
\$conf->target_directory                   = '{$targetDir}';

\$conf->obfuscate_loop_counter            = 3;

// Exclude paths
\$conf->t_ignore_files = [
PHP;

        $config .= "\n    ".implode(",\n    ", $excludePatterns);

        $config .= <<<'PHP'

];
PHP;

        if (file_put_contents($configPath, $config) === false) {
            throw TauriPhpException::fileOperationFailed('write', $configPath);
        }
    }

    /**
     * Set paths to exclude from obfuscation.
     *
     * @param  array  $paths
     * @return void
     */
    public function setExcludePaths(array $paths): void
    {
        $this->excludePaths = $paths;
    }

    /**
     * Get the obfuscation tool.
     *
     * @return string
     */
    public function getTool(): string
    {
        return $this->tool;
    }

    /**
     * Set the obfuscation tool.
     *
     * @param  string  $tool
     * @return void
     */
    public function setTool(string $tool): void
    {
        $this->tool = $tool;
    }
}
