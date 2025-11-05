<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Symfony\Component\Filesystem\Filesystem;

class CleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:clean
                            {--all : Clean everything including dependencies}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean build artifacts and temporary files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('🧹 Cleaning Tauri-PHP Build Artifacts');
            $this->newLine();

            if (!$this->option('force')) {
                if (!$this->confirm('This will remove all build artifacts and temporary files. Continue?')) {
                    $this->info('Aborted.');

                    return Command::SUCCESS;
                }
            }

            $filesystem = new Filesystem();

            // Paths to clean
            $pathsToClean = [
                'tauri-temp' => 'Temporary build directory',
                'binaries' => 'FrankenPHP binaries',
                'src-tauri/target' => 'Rust build artifacts',
                'tauri-builds' => 'Distribution packages',
            ];

            if ($this->option('all')) {
                $pathsToClean['node_modules'] = 'Node.js dependencies';
                $pathsToClean['vendor'] = 'Composer dependencies';
            }

            $cleaned = 0;
            $totalSize = 0;

            foreach ($pathsToClean as $path => $description) {
                $fullPath = base_path($path);

                if (!file_exists($fullPath)) {
                    $this->line("  ⊘ {$description}: not found");
                    continue;
                }

                $size = $this->getDirectorySize($fullPath);
                $totalSize += $size;

                $filesystem->remove($fullPath);
                $cleaned++;

                $formattedSize = $this->formatFileSize($size);
                $this->line("  ✓ {$description}: {$formattedSize} freed");
            }

            $this->newLine();

            if ($cleaned > 0) {
                $this->info("✅ Cleaned {$cleaned} location(s), freed ".$this->formatFileSize($totalSize));
            } else {
                $this->info('No artifacts to clean');
            }

            if ($this->option('all')) {
                $this->newLine();
                $this->warn('Dependencies were removed. Run "composer install" and "npm install" to restore them.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Get the size of a directory.
     *
     * @param  string  $path
     * @return int
     */
    protected function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return is_file($path) ? filesize($path) : 0;
        }

        $size = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Directory might be inaccessible
            return 0;
        }

        return $size;
    }

    /**
     * Format file size.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
