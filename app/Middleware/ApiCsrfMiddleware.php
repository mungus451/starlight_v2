<?php

namespace App\Middleware;

use App\Core\CSRFService;
use App\Core\JsonResponse;
use App\Core\Session;
use App\Core\Exceptions\TerminateException;

class ApiCsrfMiddleware
{
    private Session $session;
    private CSRFService $csrfService;

    public function __construct(Session $session, CSRFService $csrfService)
    {
        $this->session = $session;
        $this->csrfService = $csrfService;
    }

    public function handle(): void
    {
        $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (!in_array($method, $mutatingMethods, true)) {
            return;
        }

        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        if (!str_starts_with($uri, '/api/v1/')) {
            return;
        }

        // Preserve existing endpoint behavior: unauthenticated requests return 401 in controllers.
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            return;
        }

        $headerToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $postToken = (string)($_POST['csrf_token'] ?? '');
        $token = $headerToken !== '' ? $headerToken : $postToken;

        if ($token === '' || !$this->csrfService->validateToken($token)) {
            JsonResponse::send(['error' => 'Invalid CSRF token'], 422);
            throw new TerminateException();
        }
    }
}
