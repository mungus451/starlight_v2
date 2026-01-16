<?php

namespace App\Core;

use DateTime;
use Exception;

/**
 * A utility class for common static helper functions.
 */
class StarlightUtils
{
    /**
     * Converts a timestamp into a human-readable "time ago" string.
     *
     * @param string $datetime The timestamp string (e.g., 'YYYY-MM-DD HH:MM:SS')
     * @return string The formatted time ago string (e.g., "5m ago", "1h ago")
     */
    public static function time_ago(string $datetime): string
    {
        try {
            $now = new DateTime();
            $ago = new DateTime($datetime);
            $diff = $now->diff($ago);

            if ($diff->y > 0) return $diff->y . 'y ago';
            if ($diff->m > 0) return $diff->m . 'mo ago';
            if ($diff->d > 0) return $diff->d . 'd ago';
            if ($diff->h > 0) return $diff->h . 'h ago';
            if ($diff->i > 0) return $diff->i . 'm ago';
            if ($diff->s > 0) return $diff->s . 's ago';

            return 'just now';
        } catch (Exception $e) {
            return ''; // Return empty string on invalid date format
        }
    }

    /**
     * Formats a large number into a short, human-readable string with a suffix.
     * e.g., 12345 -> 12.3K, 1234567 -> 1.2M
     *
     * @param float|int $number The number to format
     * @param bool $showSign Whether to show a '+' for positive numbers
     * @return string
     */
    public static function format_short($number, bool $showSign = false): string
    {
        $number = (float)$number;
        $sign = '';
        if ($showSign && $number > 0) {
            $sign = '+';
        } elseif ($number < 0) {
            $sign = '-';
        }
        
        $abs_number = abs($number);

        if ($abs_number < 1000) {
            return $sign . number_format($abs_number, 0);
        }

        $suffixes = ['K', 'M', 'B', 'T'];
        $power = floor(log($abs_number, 1000));
        $suffix = $suffixes[$power - 1] ?? '';

        $formatted = number_format($abs_number / (1000 ** $power), 1);
        
        // Remove trailing .0
        $formatted = rtrim($formatted, '.0');

        return $sign . $formatted . $suffix;
    }
}
