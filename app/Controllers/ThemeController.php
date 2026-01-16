<?php

namespace App\Controllers;

use App\Core\CSRFService;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Services\ViewContextService;

class ThemeController extends BaseController
{
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
    }

    public function switch()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $theme = $_POST['theme'] ?? 'default';
            $this->session->set('theme', $theme);

            // Redirect back to the previous page
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $referer);
            exit;
        }

        // If not a POST request, redirect to home
        header('Location: /');
        exit;
    }
}
