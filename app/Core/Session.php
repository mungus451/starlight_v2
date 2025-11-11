<?php

namespace App\Core;

/**
 * A simple wrapper for PHP's $_SESSION.
 * Assumes session_start() has been called (which it has, in public/index.php).
 */
class Session
{
    /**
     * Set a value in the session.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a key exists in the session.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set a "flash" message that only lasts for one page load.
     *
     * @param string $key (e.g., 'error', 'success')
     * @param string $message
     */
    public function setFlash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get a "flash" message and remove it from the session.
     *
     * @param string $key
     * @return string|null
     */
    public function getFlash(string $key): ?string
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    /**
     * Destroy the entire session.
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }
}