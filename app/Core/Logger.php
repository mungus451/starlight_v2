<?php

namespace App\Core;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A simple logger service that implements PSR-3.
 */
class Logger implements LoggerInterface
{
    private string $logFile;
    private bool $echoToStdout;

    public function __construct(string $logFile, bool $echoToStdout = false)
    {
        $this->logFile = $logFile;
        $this->echoToStdout = $echoToStdout;

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // For this simple logger, we won't do context interpolation.
        // We'll just write the message.
        $this->write(strtoupper($level), $message);
    }

    private function write(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] [$level] $message" . PHP_EOL;

        file_put_contents($this->logFile, $formatted, FILE_APPEND | LOCK_EX);

        if ($this->echoToStdout && php_sapi_name() === 'cli') {
            echo $formatted;
        }
    }
}