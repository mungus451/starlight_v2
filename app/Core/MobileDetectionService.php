<?php

namespace App\Core;

class MobileDetectionService
{
    /**
     * Detects if the current user agent is a mobile device.
     *
     * @return bool True if it's a mobile device, false otherwise.
     */
    public function isMobile(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $mobileKeywords = [
            'Mobile', 'Android', 'Silk/', 'Kindle', 'BlackBerry', 'Opera Mini', 'Opera Mobi',
            'iPhone', 'iPad', 'iPod', 'Windows Phone', 'iemobile'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
