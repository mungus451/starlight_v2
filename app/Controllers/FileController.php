<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;

/**
 * Handles securely serving private files from the 'storage' directory.
 * * Refactored for Strict Dependency Injection.
 * * Updated Phase 16: Added Alliance Avatar serving.
 */
class FileController extends BaseController
{
    private string $storageRoot;

    /**
     * DI Constructor.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        
        // Define the storage root relative to this file
        $this->storageRoot = realpath(__DIR__ . '/../../storage');
    }

    /**
     * Serves a user's avatar.
     *
     * @param array $vars ['filename' => string]
     */
    public function showAvatar(array $vars): void
    {
        $this->serveFile($vars['filename'] ?? '', 'avatars');
    }
    
    /**
     * Serves an alliance's avatar.
     *
     * @param array $vars ['filename' => string]
     */
    public function showAllianceAvatar(array $vars): void
    {
        $this->serveFile($vars['filename'] ?? '', 'alliance_avatars');
    }

    /**
     * Internal helper to serve files securely.
     */
    private function serveFile(string $filename, string $subDir): void
    {
        // 1. Basic validation to prevent directory traversal
        if (empty($filename) || str_contains($filename, '..') || str_contains($filename, '/')) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        $filePath = $this->storageRoot . '/' . $subDir . '/' . $filename;

        // 2. Check if file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

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
        header('Cache-Control: public, max-age=86400'); // Cache for 1 day
        readfile($filePath);
        exit;
    }
}