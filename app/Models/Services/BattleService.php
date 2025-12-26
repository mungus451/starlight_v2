<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\EdictRepository;
use PDO;

class BattleService
{
    private PDO $db;
    private Config $config;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private PowerCalculatorService $powerCalculatorService;
    private BattleRepository $battleRepo;
    private EdictRepository $edictRepo;

    public function __construct(
        PDO $db,
        Config $config,
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        PowerCalculatorService $powerCalculatorService,
        BattleRepository $battleRepo,
        EdictRepository $edictRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->powerCalculatorService = $powerCalculatorService;
        $this->battleRepo = $battleRepo;
        $this->edictRepo = $edictRepo;
    }

    public function initiateAttack(int $attackerId, int $defenderId, string $attackType): ServiceResponse
    {
        // 1. Check for Prime Directive Edict
        $activeEdicts = $this->edictRepo->findActiveByUserId($attackerId);
        foreach ($activeEdicts as $edict) {
            if ($edict->edict_key === 'prime_directive') {
                return ServiceResponse::error('You cannot attack while the Prime Directive is active.');
            }
        }

        return ServiceResponse::success('Attack initiated.');
    }
}
