<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Thrown when the application should redirect and stop further processing.
 * Handled by index.php.
 */
class RedirectException extends Exception
{
    public function __construct(string $url)
    {
        parent::__construct($url);
    }
}