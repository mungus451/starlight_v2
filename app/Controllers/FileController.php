<?php

namespace App\Controllers;

use App\Core\Session;

/**
 * Handles securely serving private files from the 'storage' directory.
 */
class FileController extends BaseController
{
    private string $storageRoot;

    public function __construct()
    {
        parent::__construct();
        // Define the storage root relative to this file
        // __DIR__ is /app/Controllers, so ../../ is /
        $this->storageRoot = realpath(__DIR__ . '/../../storage');
    }

    /**
     * Serves a user's avatar.
     * This route is protected by AuthMiddleware in index.php.
     *
     * @param array $vars ['filename' => string]
     */
    public function showAvatar(array $vars): void
    {
        $filename = $vars['filename'] ?? '';

        // --- Security Checks ---
        // 1. Basic validation to prevent directory traversal
        if (empty($filename) || str_contains($filename, '..') || str_contains($filename, '/')) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        $filePath = $this->storageRoot . '/avatars/' . $filename;

        // 2. Check if file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        // --- Serve the file ---
        // 3. Get MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        // 4. Validate MIME type is an image
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(404);
            echo '404 - Not a valid image';
            return;
        }

        // 5. Stream the file
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        // Disables output buffering and streams the file
        readfile($filePath);
        exit;
    }
}