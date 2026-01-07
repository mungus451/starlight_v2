<?php

namespace App\Models\Services;

use App\Models\Repositories\BattleRepository;

class BattleService
{
    private BattleRepository $battleRepository;

    public function __construct(BattleRepository $battleRepository)
    {
        $this->battleRepository = $battleRepository;
    }

    /**
     * Gets the 5 most recent global battle reports.
     *
     * @return array
     */
    public function getLatestGlobalBattles(): array
    {
        return $this->battleRepository->findLatestGlobal();
    }
}
