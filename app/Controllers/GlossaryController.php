<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Presenters\GlossaryPresenter;

/**
 * GlossaryController
 * 
 * Displays the game glossary/codex, providing a read-only reference
 * for Structures, Units, Armory items, and Resources.
 */
class GlossaryController extends BaseController
{
    private GlossaryPresenter $presenter;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        GlossaryPresenter $presenter
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->presenter = $presenter;
    }

    /**
     * Show the main glossary page.
     */
    public function index(): void
    {
        $data = $this->presenter->getGlossaryData();
        
        $this->render('glossary/index.php', array_merge(
            ['title' => 'Game Glossary'],
            $data
        ));
    }
}