<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;

/**
 * Handles logic for retrieving and paginating leaderboard data.
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
     * Retrieves paginated leaderboard data for either players or alliances.
     *
     * @param string $type 'players' or 'alliances'
     * @param int $page Current page number
     * @return ServiceResponse
     */
    public function getLeaderboardData(string $type, int $page): ServiceResponse
    {
        // 1. Configuration
        $perPage = $this->config->get('app.leaderboard.per_page', 25);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $data = [];
        $totalItems = 0;

        // 2. Fetch Data based on Type
        if ($type === 'alliances') {
            $totalItems = $this->allianceRepo->getTotalCount();
            $data = $this->allianceRepo->getLeaderboardAlliances($perPage, $offset);
        } else {
            // Default to players
            $type = 'players'; 
            $totalItems = $this->statsRepo->getTotalPlayerCount();
            $data = $this->statsRepo->getLeaderboardPlayers($perPage, $offset);
        }

        // 3. Calculate Pagination Metadata
        $totalPages = (int)ceil($totalItems / $perPage);
        // Ensure page doesn't exceed max (unless total is 0)
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
            // Recalculate offset if page changed, though technically we already fetched data 
            // for the requested page. For strict correctness in a redirect scenario 
            // we might return an error, but here we just cap the pagination metadata.
        }

        // 4. Enrich Data with Rank
        // We calculate the rank based on offset + index + 1
        foreach ($data as $index => &$row) {
            $row['rank'] = $offset + $index + 1;
        }
        unset($row); // Break reference

        return ServiceResponse::success('Leaderboard retrieved', [
            'type' => $type,
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