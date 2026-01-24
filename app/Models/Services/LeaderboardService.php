<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Services\NetWorthCalculatorService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;

/**
 * Handles logic for retrieving and paginating leaderboard data.
 * Updated to support dynamic sorting.
 */
class LeaderboardService
{
    private Config $config;
    private StatsRepository $statsRepo;
    private AllianceRepository $allianceRepo;
    private NetWorthCalculatorService $nwCalculator;
    private UserRepository $userRepo;
    private PowerCalculatorService $powerCalculator;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;

    public function __construct(
        Config $config,
        StatsRepository $statsRepo,
        AllianceRepository $allianceRepo,
        NetWorthCalculatorService $nwCalculator,
        UserRepository $userRepo,
        PowerCalculatorService $powerCalculator,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo
    ) {
        $this->config = $config;
        $this->statsRepo = $statsRepo;
        $this->allianceRepo = $allianceRepo;
        $this->nwCalculator = $nwCalculator;
        $this->userRepo = $userRepo;
        $this->powerCalculator = $powerCalculator;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
    }

    /**
     * Retrieves paginated leaderboard data.
     *
     * @param string $type 'players' or 'alliances'
     * @param int $page Current page number
     * @param string $sortKey The column to sort by (e.g., 'net_worth', 'army', 'battles_won')
     * @return ServiceResponse
     */
    public function getLeaderboardData(string $type, int $page, string $sortKey = 'net_worth'): ServiceResponse
    {
        // 1. Configuration
        $perPage = $this->config->get('app.leaderboard.per_page', 25);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $data = [];
        $totalItems = 0;

        // 2. Fetch Data based on Type
        if ($type === 'alliances') {
            // Alliances currently only sort by Net Worth (default)
            $totalItems = $this->allianceRepo->getTotalCount();
            $data = $this->allianceRepo->getLeaderboardAlliances($perPage, $offset);

            // Recalculate Net Worth for current page of alliances
            foreach ($data as &$alliance) {
                if (isset($alliance['id'])) {
                    $memberIds = $this->userRepo->getMemberIdsByAllianceId($alliance['id']);
                    $totalNw = 0;
                    foreach ($memberIds as $memberId) {
                        $totalNw += $this->nwCalculator->calculateTotalNetWorth($memberId);
                    }
                    $alliance['net_worth'] = $totalNw;
                }
            }
            unset($alliance);

        } else {
            // Players support dynamic sorting
            $type = 'players';
            
            // Whitelist sort keys to prevent SQL errors/injection issues
            $allowedSorts = [
                'net_worth', 'prestige', 'army', 'population', 
                'battles_won', 'battles_lost', 'spy_success', 'spy_fail',
                'level', 'overall_power'
            ];
            
            if (!in_array($sortKey, $allowedSorts)) {
                $sortKey = 'net_worth';
            }

            if ($sortKey === 'overall_power') {
                $allPlayers = $this->userRepo->getAllActivePlayerIdsAndData();
                $totalItems = count($allPlayers);

                foreach ($allPlayers as &$player) {
                    $userId = $player['id'];
                    $stats = $this->statsRepo->findByUserId($userId);
                    $resources = $this->resourceRepo->findByUserId($userId);
                    $structures = $this->structureRepo->findByUserId($userId);

                    if ($stats && $resources && $structures) {
                        $offense_power = $this->powerCalculator->calculateOffensePower($userId, $resources, $stats, $structures, $player['alliance_id'])['total'];
                        $defense_power = $this->powerCalculator->calculateDefensePower($userId, $resources, $stats, $structures, $player['alliance_id'])['total'];
                        $player['overall_power'] = $offense_power + $defense_power;
                    } else {
                        $player['overall_power'] = 0;
                    }
                    $player['net_worth'] = $this->nwCalculator->calculateTotalNetWorth($userId);
                    // Add all other required stats for the view
                    $player['level'] = $stats->level ?? 0;
                    $player['battles_won'] = $stats->battles_won ?? 0;
                    $player['battles_lost'] = $stats->battles_lost ?? 0;
                    $player['war_prestige'] = $stats->war_prestige ?? 0;
                }
                unset($player);

                usort($allPlayers, function ($a, $b) {
                    return $b['overall_power'] <=> $a['overall_power'];
                });

                $data = array_slice($allPlayers, $offset, $perPage);
            } else {
                $totalItems = $this->statsRepo->getTotalPlayerCount();
                $data = $this->statsRepo->getLeaderboardPlayers($sortKey, $perPage, $offset);

                foreach ($data as &$player) {
                    if (isset($player['id'])) {
                        $userId = $player['id'];
                        $player['net_worth'] = $this->nwCalculator->calculateTotalNetWorth($userId);
                        
                        $stats = $this->statsRepo->findByUserId($userId);
                        $resources = $this->resourceRepo->findByUserId($userId);
                        $structures = $this->structureRepo->findByUserId($userId);

                        if ($stats && $resources && $structures) {
                            $offense_power = $this->powerCalculator->calculateOffensePower($userId, $resources, $stats, $structures, $player['alliance_id'])['total'];
                            $defense_power = $this->powerCalculator->calculateDefensePower($userId, $resources, $stats, $structures, $player['alliance_id'])['total'];
                            $player['overall_power'] = $offense_power + $defense_power;
                        } else {
                            $player['overall_power'] = 0;
                        }
                    }
                }
                unset($player);
            }
        }

        // 3. Calculate Pagination Metadata
        $totalPages = (int)ceil($totalItems / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        // 4. Enrich Data with Rank
        foreach ($data as $index => &$row) {
            $row['rank'] = $offset + $index + 1;
        }
        unset($row);

        return ServiceResponse::success('Leaderboard retrieved', [
            'type' => $type,
            'currentSort' => $sortKey,
            'data' => $data,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'perPage' => $perPage
            ]
        ]);
    }
}