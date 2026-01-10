<?php

namespace App\Services;

use App\Models\Entities\Alliance;
use App\Models\Entities\War;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\WarSpyLogRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\DTO\WarScoreDTO;

class WarMetricsService
{
    private WarBattleLogRepository $warBattleLogRepo;
    private WarSpyLogRepository $warSpyLogRepo;
    private AllianceRepository $allianceRepo;

    public function __construct(WarBattleLogRepository $warBattleLogRepo, WarSpyLogRepository $warSpyLogRepo, AllianceRepository $allianceRepo)
    {
        $this->warBattleLogRepo = $warBattleLogRepo;
        $this->warSpyLogRepo = $warSpyLogRepo;
        $this->allianceRepo = $allianceRepo;
    }

    /**
     * Calculates and returns the war score for a given war.
     * @param War $war
     * @return WarScoreDTO
     */
    public function getWarScore(War $war): WarScoreDTO
    {
        $allianceA = $this->allianceRepo->findById($war->alliance_id_1);
        $allianceB = $this->allianceRepo->findById($war->alliance_id_2);

        $battleLogs = $this->warBattleLogRepo->findByWarId($war->id);
        $spyLogs = $this->warSpyLogRepo->findByWarId($war->id);

        // Initialize raw metrics
        $metrics = [
            'allianceA' => [
                'plunder' => 0,
                'attack_victories' => 0,
                'attack_losses' => 0,
                'spy_victories' => 0,
                'spy_losses' => 0,
                'defense_victories' => 0,
                'defense_losses' => 0,
            ],
            'allianceB' => [
                'plunder' => 0,
                'attack_victories' => 0,
                'attack_losses' => 0,
                'spy_victories' => 0,
                'spy_losses' => 0,
                'defense_victories' => 0,
                'defense_losses' => 0,
            ],
        ];

        // Aggregate Battle Logs
        foreach ($battleLogs as $log) {
            // Offensive Battles
            if ($log->attacker_alliance_id === $allianceA->id) {
                if ($log->is_victory) {
                    $metrics['allianceA']['attack_victories']++;
                    $metrics['allianceA']['plunder'] += $log->plundered_credits;
                } else {
                    $metrics['allianceA']['attack_losses']++;
                }
            } elseif ($log->attacker_alliance_id === $allianceB->id) {
                if ($log->is_victory) {
                    $metrics['allianceB']['attack_victories']++;
                    $metrics['allianceB']['plunder'] += $log->plundered_credits;
                } else {
                    $metrics['allianceB']['attack_losses']++;
                }
            }

            // Defensive Battles
            if ($log->defender_alliance_id === $allianceA->id) {
                if (!$log->is_victory) { // Defender loses if attacker wins
                    $metrics['allianceA']['defense_losses']++;
                } else {
                    $metrics['allianceA']['defense_victories']++;
                }
            } elseif ($log->defender_alliance_id === $allianceB->id) {
                if (!$log->is_victory) {
                    $metrics['allianceB']['defense_losses']++;
                } else {
                    $metrics['allianceB']['defense_victories']++;
                }
            }
        }

        // Aggregate Spy Logs
        foreach ($spyLogs as $log) {
            // Offensive Spy Missions
            if ($log->attacker_alliance_id === $allianceA->id) {
                if ($log->is_success) {
                    $metrics['allianceA']['spy_victories']++;
                } else {
                    $metrics['allianceA']['spy_losses']++;
                }
            } elseif ($log->attacker_alliance_id === $allianceB->id) {
                if ($log->is_success) {
                    $metrics['allianceB']['spy_victories']++;
                } else {
                    $metrics['allianceB']['spy_losses']++;
                }
            }

            // Defensive Spy Missions
            if ($log->defender_alliance_id === $allianceA->id) {
                if (!$log->is_success) { // Defender loses if attacker succeeds
                    $metrics['allianceA']['defense_losses']++;
                } else {
                    $metrics['allianceA']['defense_victories']++;
                }
            } elseif ($log->defender_alliance_id === $allianceB->id) {
                if (!$log->is_success) {
                    $metrics['allianceB']['defense_losses']++;
                } else {
                    $metrics['allianceB']['defense_victories']++;
                }
            }
        }

        // Calculate points for each category
        $pointsA = [
            'economy' => $this->calculatePoints($metrics['allianceA']['plunder'], $metrics['allianceB']['plunder'], 20),
            'attack_offense' => $this->calculateWinLossPoints($metrics['allianceA']['attack_victories'], $metrics['allianceA']['attack_losses'], $metrics['allianceB']['attack_victories'], $metrics['allianceB']['attack_losses'], 20),
            'spy_offense' => $this->calculateWinLossPoints($metrics['allianceA']['spy_victories'], $metrics['allianceA']['spy_losses'], $metrics['allianceB']['spy_victories'], $metrics['allianceB']['spy_losses'], 20),
            'attack_defense' => $this->calculateWinLossPoints($metrics['allianceA']['defense_victories'], $metrics['allianceA']['defense_losses'], $metrics['allianceB']['defense_victories'], $metrics['allianceB']['defense_losses'], 20),
            'spy_defense' => $this->calculateWinLossPoints($metrics['allianceA']['spy_victories'], $metrics['allianceA']['spy_losses'], $metrics['allianceB']['spy_victories'], $metrics['allianceB']['spy_losses'], 20),
        ];

        $pointsB = [
            'economy' => 20 - $pointsA['economy'],
            'attack_offense' => 20 - $pointsA['attack_offense'],
            'spy_offense' => 20 - $pointsA['spy_offense'],
            'attack_defense' => 20 - $pointsA['attack_defense'],
            'spy_defense' => 20 - $pointsA['spy_defense'],
        ];

        $totalPointsA = array_sum($pointsA);
        $totalPointsB = array_sum($pointsB);

        return new WarScoreDTO(
            $allianceA,
            $allianceB,
            $metrics['allianceA'],
            $metrics['allianceB'],
            $pointsA,
            $pointsB,
            $totalPointsA,
            $totalPointsB
        );
    }

    /**
     * Calculates points for a category where raw values are compared (e.g., plunder).
     * @param float $valueA
     * @param float $valueB
     * @param int $maxPoints
     * @return float
     */
    private function calculatePoints(float $valueA, float $valueB, int $maxPoints): float
    {
        $total = $valueA + $valueB;
        if ($total === 0.0) {
            return $maxPoints / 2; // Split evenly if no activity
        }
        return ($valueA / $total) * $maxPoints;
    }

    /**
     * Calculates points based on win/loss ratios.
     * @param int $victoriesA
     * @param int $lossesA
     * @param int $victoriesB
     * @param int $lossesB
     * @param int $maxPoints
     * @return float
     */
    private function calculateWinLossPoints(int $victoriesA, int $lossesA, int $victoriesB, int $lossesB, int $maxPoints): float
    {
        $totalA = $victoriesA + $lossesA;
        $totalB = $victoriesB + $lossesB;

        if ($totalA === 0 && $totalB === 0) {
            return $maxPoints / 2; // Split evenly if no activity
        }

        // Calculate percentage of victories for each alliance
        $winPercentageA = $totalA > 0 ? $victoriesA / $totalA : 0;
        $winPercentageB = $totalB > 0 ? $victoriesB / $totalB : 0;

        $totalWinPercentage = $winPercentageA + $winPercentageB;

        if ($totalWinPercentage === 0.0) {
            return $maxPoints / 2; // Split evenly if both have 0% win rate (e.g. all losses)
        }

        return ($winPercentageA / $totalWinPercentage) * $maxPoints;
    }
}
