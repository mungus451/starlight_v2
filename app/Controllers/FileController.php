<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles securely serving private files from the 'storage' directory.
 * * Refactored for Strict Dependency Injection.
 */
class FileController extends BaseController
{
    private string $storageRoot;

    /**
     * DI Constructor.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        
        // Define the storage root relative to this file
        // __DIR__ is /app/Controllers, so ../../ is the project root
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
        readfile($filePath);
        exit;
    }
    
    /**
     * Serves an alliance's avatar.
     *
     * @param array $vars ['filename' => string]
     */
    public function showAllianceAvatar(array $vars): void
    {
        $filename = $vars['filename'] ?? '';

        if (empty($filename) || str_contains($filename, '..') || str_contains($filename, '/')) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        $filePath = $this->storageRoot . '/alliance_avatars/' . $filename;
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            echo '404 - Not Found';
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(404);
            echo '404 - Not a valid image';
            return;
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}