<?php

namespace App\Controllers;

use App\Core\Config;
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
    private Config $config;
    private GlossaryPresenter $presenter;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        Config $config,
        GlossaryPresenter $presenter
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config    = $config;
        $this->presenter = $presenter;
    }

    /**
     * Show the main glossary page.
     */
    public function index(): void
    {
        $data = $this->presenter->getGlossaryData();

        $this->render('glossary/index.php', array_merge(
            [
                'title'               => 'Game Glossary',
                'spa_glossary_enabled' => $this->config->get('spa.glossary_enabled'),
            ],
            $data
        ));
    }

    /**
     * API endpoint: return all glossary data as JSON.
     * No auth guard — data is identical for every user (static config).
     */
    public function apiData(): void
    {
        $this->jsonResponse($this->presenter->getGlossaryData());
    }
}
