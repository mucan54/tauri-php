<?php

namespace Mucan54\TauriPhp\Traits;

use Mucan54\TauriPhp\Exceptions\TauriPhpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

trait RunsProcesses
{
    /**
     * Run a process synchronously.
     *
     * @param  array  $command
     * @param  string  $message
     * @param  int|null  $timeout
     * @param  string|null  $cwd
     * @return string
     *
     * @throws TauriPhpException
     */
    protected function runProcess(
        array $command,
        string $message = '',
        ?int $timeout = 300,
        ?string $cwd = null
    ): string {
        if ($message && method_exists($this, 'line')) {
            $this->line("  → {$message}");
        }

        $process = new Process(
            $command,
            $cwd ?? base_path(),
            null,
            null,
            $timeout
        );

        $output = '';

        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;

            if (method_exists($this, 'option') && $this->option('verbose')) {
                echo $buffer;
            }
        });

        if (!$process->isSuccessful()) {
            throw TauriPhpException::processExecutionFailed(
                implode(' ', $command),
                $process->getErrorOutput() ?: $output
            );
        }

        return $output;
    }

    /**
     * Run a process asynchronously.
     *
     * @param  array  $command
     * @param  string|null  $cwd
     * @return Process
     */
    protected function runProcessAsync(array $command, ?string $cwd = null): Process
    {
        $process = new Process($command, $cwd ?? base_path());
        $process->setTimeout(null);
        $process->start();

        return $process;
    }

    /**
     * Check if a command is available.
     *
     * @param  string  $command
     * @return bool
     */
    protected function commandExists(string $command): bool
    {
        $process = Process::fromShellCommandline(
            PHP_OS_FAMILY === 'Windows' ? "where {$command}" : "which {$command}"
        );

        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Get the version of a command.
     *
     * @param  string  $command
     * @param  string  $versionFlag
     * @return string|null
     */
    protected function getCommandVersion(string $command, string $versionFlag = '--version'): ?string
    {
        try {
            $process = new Process([$command, $versionFlag]);
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        } catch (\Exception $e) {
            // Command not found or failed
        }

        return null;
    }

    /**
     * Wait for a URL to become available.
     *
     * @param  string  $url
     * @param  int  $timeout
     * @param  int  $interval
     * @return bool
     */
    protected function waitForUrl(string $url, int $timeout = 30, int $interval = 1): bool
    {
        $start = time();

        while (time() - $start < $timeout) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1,
                ],
            ]);

            if (@file_get_contents($url, false, $context) !== false) {
                return true;
            }

            sleep($interval);
        }

        return false;
    }
}
