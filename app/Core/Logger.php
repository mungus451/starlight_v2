<?php

namespace App\Core;

/**
 * A simple logger service to decouple I/O from Business Logic.
 * Replaces direct 'echo' and 'file_put_contents' in Services.
 */
class Logger
{
    private string $logFile;
    private bool $echoToStdout;

    /**
     * @param string $logFile Absolute path to the log file.
     * @param bool $echoToStdout If true, also prints formatted messages to the CLI output.
     */
    public function __construct(string $logFile, bool $echoToStdout = false)
    {
        $this->logFile = $logFile;
        $this->echoToStdout = $echoToStdout;

        // Ensure directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Log an informational message.
     *
     * @param string $message
     */
    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     */
    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    /**
     * Internal write handler.
     */
    private function write(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] [$level] $message" . PHP_EOL;

        // 1. Write to file (Atomic append)
        file_put_contents($this->logFile, $formatted, FILE_APPEND | LOCK_EX);

        // 2. Optional CLI Output
        if ($this->echoToStdout && php_sapi_name() === 'cli') {
            echo $formatted;
        }
    }
}