<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\AllianceOperationRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Entities\User;
use App\Models\Entities\Operation;

class AllianceOperationService
{
    public function __construct(
        private AllianceOperationRepository $opsRepo,
        private ResourceRepository $resourceRepo,
        private StatsRepository $statsRepo,
        private AllianceRepository $allianceRepo,
        private UserRepository $userRepo
    ) {}

    /**
     * Processes a direct donation of turns to Alliance Energy.
     */
    public function donateEnergy(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) return ServiceResponse::error('Invalid amount.');

        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->alliance_id) return ServiceResponse::error('Not in an alliance.');

        $stats = $this->statsRepo->findByUserId($userId);
        if ($stats->attack_turns < $amount) return ServiceResponse::error('Not enough turns.');

        $this->statsRepo->updateAttackTurns($userId, $stats->attack_turns - $amount);
        $this->allianceRepo->updateEnergyRelative($user->alliance_id, $amount);
        
        // Log it (Using a repo method that creates an energy log)
        // I need to add logEnergy to AllianceOperationRepository interface/implementation if not accessible?
        // Wait, I can inject opsRepo.
        $this->opsRepo->logEnergy($user->alliance_id, $userId, 'donation_turns', $amount, 'Donated Turns');

        return ServiceResponse::success("Donated {$amount} Turns (+{$amount} AE).");
    }

    /**
     * Processes a contribution to an active operation.
     * Handles resource deduction and progress updates polymorphically.
     */
    public function processContribution(int $userId, int $opId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Contribution amount must be positive.');
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->alliance_id) {
            return ServiceResponse::error('You must be in an alliance.');
        }

        $op = $this->opsRepo->findActiveByAllianceId($user->alliance_id);
        if (!$op || $op->id !== $opId) {
            return ServiceResponse::error('Operation not found or expired.');
        }

        // 1. Determine Requirement Type (Polymorphic Logic)
        // Default to 'turns' if not specified (legacy support)
        // In the future, this can be a column in the DB, but for now we map active types.
        $reqType = $this->getRequirementType($op->type);

        // 2. Validate & Deduct Resources
        if (!$this->deductResources($userId, $reqType, $amount)) {
            return ServiceResponse::error("Insufficient {$reqType} to contribute.");
        }

        // 3. Update Operation Progress
        $this->opsRepo->updateProgress($op->id, $amount);
        $this->opsRepo->trackContribution($op->id, $userId, $amount);

        // 4. Handle Rewards (XP Kickback)
        // 1 XP per unit/turn contributed
        $this->awardExperience($userId, $amount);

        return ServiceResponse::success("Contributed {$amount} {$reqType}.");
    }

    /**
     * Maps operation types to resource requirements.
     */
    private function getRequirementType(string $opType): string
    {
        return match($opType) {
            'deployment_drill' => 'soldiers',
            'resource_drive'   => 'credits',
            'energy_boost'     => 'turns',
            default            => 'turns'
        };
    }

    /**
     * Deducts the required resource from the user.
     */
    private function deductResources(int $userId, string $reqType, int $amount): bool
    {
        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);

        switch ($reqType) {
            case 'soldiers':
                if ($resources->soldiers < $amount) return false;
                $this->resourceRepo->updateSoldiers($userId, $resources->soldiers - $amount);
                return true;

            case 'credits':
                if ($resources->credits < $amount) return false;
                $this->resourceRepo->updateCredits($userId, $resources->credits - $amount);
                return true;
            
            case 'turns':
                if ($stats->attack_turns < $amount) return false;
                $this->statsRepo->updateAttackTurns($userId, $stats->attack_turns - $amount);
                return true;

            default:
                return false;
        }
    }

    /**
     * Awards generic experience for participation.
     */
    private function awardExperience(int $userId, int $amount): void
    {
        $stats = $this->statsRepo->findByUserId($userId);
        // Simple 1:1 ratio for now
        $xpGain = $amount; 
        
        $this->statsRepo->updateLevelProgress(
            $userId, 
            $stats->experience + $xpGain, 
            $stats->level, 
            $stats->level_up_points
        );
    }
}
