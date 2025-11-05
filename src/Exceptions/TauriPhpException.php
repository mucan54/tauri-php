<?php

namespace Mucan54\TauriPhp\Exceptions;

use Exception;

class TauriPhpException extends Exception
{
    /**
     * Create a new exception instance for prerequisite failure.
     */
    public static function prerequisiteMissing(string $prerequisite, string $instruction): self
    {
        return new self(
            "Missing prerequisite: {$prerequisite}. {$instruction}"
        );
    }

    /**
     * Create a new exception instance for build failure.
     */
    public static function buildFailed(string $platform, string $reason): self
    {
        return new self(
            "Build failed for platform '{$platform}': {$reason}"
        );
    }

    /**
     * Create a new exception instance for configuration error.
     */
    public static function configurationError(string $message): self
    {
        return new self("Configuration error: {$message}");
    }

    /**
     * Create a new exception instance for file operation error.
     */
    public static function fileOperationFailed(string $operation, string $path, string $reason = ''): self
    {
        $message = "File operation '{$operation}' failed for '{$path}'";

        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    /**
     * Create a new exception instance for process execution error.
     */
    public static function processExecutionFailed(string $command, string $output): self
    {
        return new self(
            "Process execution failed for command '{$command}': {$output}"
        );
    }
}
