<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;

/**
 * Handles logic for retrieving and paginating leaderboard data.
 * Updated to support dynamic sorting.
 */
class LeaderboardService
{
    private Config $config;
    private StatsRepository $statsRepo;
    private AllianceRepository $allianceRepo;

    public function __construct(
        Config $config,
        StatsRepository $statsRepo,
        AllianceRepository $allianceRepo
    ) {
        $this->config = $config;
        $this->statsRepo = $statsRepo;
        $this->allianceRepo = $allianceRepo;
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