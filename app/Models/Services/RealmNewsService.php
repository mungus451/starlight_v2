<?php

namespace App\Models\Services;

use App\Models\Repositories\RealmNewsRepository;

class RealmNewsService
{
    private RealmNewsRepository $realmNewsRepository;

    public function __construct(RealmNewsRepository $realmNewsRepository)
    {
        $this->realmNewsRepository = $realmNewsRepository;
    }

    /**
     * Gets the latest news for the advisor panel.
     *
     * @return object|null
     */
    public function getLatestNews(): ?object
    {
        return $this->realmNewsRepository->findLatestActive();
    }
}
