<?php

namespace App\Middleware;

use App\Core\MobileDetectionService;
use App\Core\Session;

class MobileViewMiddleware
{
    protected MobileDetectionService $mobileDetectionService;
    protected Session $session;

    public function __construct(MobileDetectionService $mobileDetectionService, Session $session)
    {
        $this->mobileDetectionService = $mobileDetectionService;
        $this->session = $session;
    }

    public function handle(callable $next)
    {
        if ($this->mobileDetectionService->isMobile()) {
            $this->session->set('is_mobile', true);
        } else {
            $this->session->set('is_mobile', false);
        }

        return $next();
    }
}
