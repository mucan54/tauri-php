<?php

namespace Mucan54\TauriPhp\Console;

use Illuminate\Console\Command;
use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Mucan54\TauriPhp\Traits\RunsProcesses;
use Symfony\Component\Filesystem\Filesystem;

class PackageCommand extends Command
{
    use RunsProcesses;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tauri:package
                            {--format=all : Package format (dmg|app|msi|nsis|deb|appimage|all)}
                            {--sign : Sign the package}
                            {--output= : Output directory}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create distribution packages for the Tauri-PHP application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('📦 Creating Distribution Packages');
            $this->newLine();

            // Step 1: Verify build exists
            if (! $this->verifyBuild()) {
                throw TauriPhpException::buildFailed('packaging', 'No build found. Run tauri:build first.');
            }

            // Step 2: Determine formats
            $formats = $this->resolveFormats();

            $this->info('Creating packages: '.implode(', ', $formats));
            $this->newLine();

            // Step 3: Copy packages to output directory
            $this->copyPackages($formats);

            $this->newLine();
            $this->info('✅ Packaging completed successfully!');

            return Command::SUCCESS;
        } catch (TauriPhpException $e) {
            $this->error('❌ '.$e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Verify that a build exists.
     */
    protected function verifyBuild(): bool
    {
        $bundleDir = base_path('src-tauri/target/release/bundle');

        return is_dir($bundleDir) && ! empty(glob($bundleDir.'/*'));
    }

    /**
     * Resolve package formats.
     */
    protected function resolveFormats(): array
    {
        $allFormats = ['dmg', 'app', 'msi', 'nsis', 'deb', 'appimage'];
        $requestedFormat = $this->option('format');

        if ($requestedFormat === 'all') {
            return $allFormats;
        }

        if (! in_array($requestedFormat, $allFormats)) {
            $this->warn("Unknown format: {$requestedFormat}. Using all formats.");

            return $allFormats;
        }

        return [$requestedFormat];
    }

    /**
     * Copy packages to output directory.
     *
     *
     * @throws TauriPhpException
     */
    protected function copyPackages(array $formats): void
    {
        $this->line('📁 Copying packages...');

        $bundleDir = base_path('src-tauri/target/release/bundle');
        $outputDir = $this->option('output') ?: base_path('tauri-builds');

        $filesystem = new Filesystem;

        if (! is_dir($outputDir)) {
            $filesystem->mkdir($outputDir, 0755);
        }

        $copied = 0;

        foreach ($formats as $format) {
            $formatDir = $bundleDir.'/'.$format;

            if (! is_dir($formatDir)) {
                continue;
            }

            $files = glob($formatDir.'/*');

            foreach ($files as $file) {
                $filename = basename($file);
                $destination = $outputDir.'/'.$filename;

                if (is_file($file)) {
                    $filesystem->copy($file, $destination, true);
                    $this->line("  ✓ Copied: {$filename}");
                    $copied++;
                } elseif (is_dir($file) && str_ends_with($file, '.app')) {
                    // macOS .app bundles are directories
                    $filesystem->mirror($file, $destination);
                    $this->line("  ✓ Copied: {$filename}");
                    $copied++;
                }
            }
        }

        if ($copied === 0) {
            $this->warn('  No packages found to copy');
        } else {
            $this->newLine();
            $this->info("  Packages saved to: {$outputDir}");
        }
    }
}
