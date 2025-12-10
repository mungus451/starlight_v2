<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Thrown when the application has finished sending a response (File, JSON, Error)
 * and should terminate execution gracefully without calling exit().
 */
class TerminateException extends Exception
{
}