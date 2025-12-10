<?php

namespace App\Core;

class JsonResponse
{
    /**
     * Sends a JSON response and terminates execution.
     *
     * @param array $data The data to encode
     * @param int $statusCode HTTP status code (default 200)
     */
    public static function send(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
