<?php

namespace App\Core;

/**
 * Standardized response object for Service layer interactions.
 * Decouples business logic from HTTP/Session concerns.
 */
class ServiceResponse
{
    public readonly bool $success;
    public readonly string $message;
    public readonly array $data;

    /**
     * @param bool $success Did the operation succeed?
     * @param string $message Internal or user-facing message regarding the result.
     * @param array $data Any payload to return (e.g., created ID, updated resource counts).
     */
    public function __construct(bool $success, string $message = '', array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Factory method for a successful result.
     */
    public static function success(string $message = '', array $data = []): self
    {
        return new self(true, $message, $data);
    }

    /**
     * Factory method for a failure result.
     */
    public static function error(string $message = '', array $data = []): self
    {
        return new self(false, $message, $data);
    }

    /**
     * Helper to check success state.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
}