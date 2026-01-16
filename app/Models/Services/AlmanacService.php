<?php

namespace App\Models\Services;

use App\Models\Repositories\AlmanacRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;

/**
 * Business logic for the Almanac (Dossier System).
 * Prepares aggregated statistics for single-entity visualization.
 */
class AlmanacService
{
    public function __construct(
        private AlmanacRepository $almanacRepo,
        private UserRepository $userRepo,
        private AllianceRepository $allianceRepo
    ) {}

    /**
     * Prepares the full dossier for a single player.
     */
    public function getPlayerDossier(int $playerId): ?array
    {
        $player = $this->userRepo->findById($playerId);
        if (!$player) {
            return null;
        }

        $stats = $this->almanacRepo->getPlayerDossier($playerId);

        // Chart Data: Wins vs Losses
        $chartWinLoss = [
            'labels' => ['Wins', 'Losses'],
            'datasets' => [[
                'data' => [$stats['battles_won'], $stats['battles_lost']],
                'backgroundColor' => ['#198754', '#dc3545'] // Green, Red
            ]]
        ];

        // Chart Data: Units Killed vs Lost
        $chartUnits = [
            'labels' => ['Units Killed', 'Casualties'],
            'datasets' => [[
                'label' => 'Count',
                'data' => [$stats['units_killed'], $stats['units_lost']],
                'backgroundColor' => ['#0dcaf0', '#fd7e14'] // Cyan, Orange
            ]]
        ];

        // Chart Data: Casualty Breakdown (Detailed)
        $chartCasualtyBreakdown = [
            'labels' => ['Enemies Killed', 'Army Lost (Att)', 'Army Lost (Def)', 'Spies Lost', 'Sentries Lost'],
            'datasets' => [[
                'label' => 'Units',
                'data' => [
                    $stats['units_killed'],
                    $stats['units_lost_attacking'] ?? 0,
                    $stats['units_lost_defending'] ?? 0,
                    $stats['spies_lost'] ?? 0,
                    $stats['sentries_lost'] ?? 0
                ],
                'backgroundColor' => ['#0dcaf0', '#fd7e14', '#dc3545', '#d63384', '#6610f2'], // Cyan, Orange, Red, Pink, Purple
                'borderColor' => '#222',
                'borderWidth' => 2
            ]]
        ];

        // Chart Data: Espionage Success Rate
        $chartSpySuccess = [
            'labels' => ['Success', 'Failed'],
            'datasets' => [[
                'data' => [
                    $stats['spy_missions_success'] ?? 0,
                    $stats['spy_missions_failed'] ?? 0
                ],
                'backgroundColor' => ['#0d6efd', '#6c757d'], // Blue, Grey
                'borderColor' => '#222',
                'borderWidth' => 2
            ]]
        ];

        // Chart Data: Spy K/D (Intel Efficiency)
        $intelKills = ($stats['enemy_sentries_killed'] ?? 0) + ($stats['enemy_spies_caught'] ?? 0);
        $intelDeaths = ($stats['spies_lost'] ?? 0) + ($stats['sentries_lost'] ?? 0);

        $chartSpyKD = [
            'labels' => ['Intel Kills', 'Intel Deaths'],
            'datasets' => [[
                'data' => [$intelKills, $intelDeaths],
                'backgroundColor' => ['#0dcaf0', '#dc3545'], // Cyan (Kills), Red (Deaths)
                'borderColor' => '#222',
                'borderWidth' => 2
            ]]
        ];

        return [
            'player' => $player,
            'stats' => $stats,
            'charts' => [
                'win_loss' => $chartWinLoss,
                'units' => $chartUnits,
                'casualty_breakdown' => $chartCasualtyBreakdown,
                'spy_success' => $chartSpySuccess,
                'spy_kd' => $chartSpyKD
            ]
        ];
    }

    /**
     * Prepares the full dossier for an alliance.
     */
    public function getAllianceDossier(int $allianceId): ?array
    {
        $alliance = $this->allianceRepo->findById($allianceId);
        if (!$alliance) {
            return null;
        }

        $stats = $this->almanacRepo->getAllianceDossier($allianceId);
        $members = $this->userRepo->findAllByAllianceId($allianceId);

        // Chart Data: Total Member Wins vs Losses
        $chartWinLoss = [
            'labels' => ['Member Victories', 'Member Defeats'],
            'datasets' => [[
                'data' => [$stats['total_wins'], $stats['total_losses']],
                'backgroundColor' => ['#ffc107', '#dc3545'] // Warning (Yellow), Red
            ]]
        ];

        return [
            'alliance' => $alliance,
            'members' => $members,
            'stats' => $stats,
            'charts' => [
                'win_loss' => $chartWinLoss
            ]
        ];
    }

    /**
     * Searches for players for the Autocomplete dropdown.
     */
    public function searchPlayers(string $query): array
    {
        // If empty, return top 20 (default behavior)
        if (empty($query)) {
            return $this->userRepo->searchByCharacterName('', 20);
        }
        return $this->userRepo->searchByCharacterName($query, 10);
    }

    /**
     * Searches for alliances for the Autocomplete dropdown.
     */
    public function searchAlliances(string $query): array
    {
        // If empty, return top 20 (default behavior)
        if (empty($query)) {
            return $this->allianceRepo->searchByName('', 20);
        }
        return $this->allianceRepo->searchByName($query, 10);
    }

    /**
     * Gets a simple list of all players for the dropdown.
     */
    public function getAllPlayersList(): array
    {
        return $this->userRepo->getAllPlayersSimple();
    }

    /**
     * Gets a simple list of all alliances for the dropdown.
     */
    public function getAllAlliancesList(): array
    {
        return $this->allianceRepo->getAllAlliancesSimple();
    }
}
