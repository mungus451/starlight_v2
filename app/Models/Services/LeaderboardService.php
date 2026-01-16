<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Services\NetWorthCalculatorService;

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

    public function __construct(
        Config $config,
        StatsRepository $statsRepo,
        AllianceRepository $allianceRepo,
        NetWorthCalculatorService $nwCalculator,
        UserRepository $userRepo
    ) {
        $this->config = $config;
        $this->statsRepo = $statsRepo;
        $this->allianceRepo = $allianceRepo;
        $this->nwCalculator = $nwCalculator;
        $this->userRepo = $userRepo;
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
                'battles_won', 'battles_lost', 'spy_success', 'spy_fail'
            ];
            
            if (!in_array($sortKey, $allowedSorts)) {
                $sortKey = 'net_worth';
            }

            $totalItems = $this->statsRepo->getTotalPlayerCount();
            $data = $this->statsRepo->getLeaderboardPlayers($sortKey, $perPage, $offset);

            // Recalculate Net Worth for current page
            foreach ($data as &$player) {
                if (isset($player['user_id'])) {
                    $player['net_worth'] = $this->nwCalculator->calculateTotalNetWorth($player['user_id']);
                }
            }
            unset($player);
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