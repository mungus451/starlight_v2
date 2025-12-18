<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\ResourceRepository; // For activation costs if we add them later

class EmbassyService
{
    public function __construct(
        private EdictRepository $edictRepo,
        private StructureRepository $structureRepo
    ) {}

    /**
     * Get the main dashboard data for the Embassy view.
     */
    public function getEmbassyData(int $userId): array
    {
        $structures = $this->structureRepo->findByUserId($userId);
        $embassyLevel = $structures->embassy_level ?? 0;

        $maxSlots = $this->calculateMaxSlots($embassyLevel);
        $activeEdicts = $this->edictRepo->findActiveByUserId($userId);
        $allDefinitions = $this->edictRepo->getAllDefinitions();

        // Map active edicts to their full definitions for the view
        $activeDefinitions = [];
        $activeKeys = [];
        foreach ($activeEdicts as $userEdict) {
            if (isset($allDefinitions[$userEdict->edict_key])) {
                $activeDefinitions[] = $allDefinitions[$userEdict->edict_key];
                $activeKeys[] = $userEdict->edict_key;
            }
        }

        return [
            'embassy_level' => $embassyLevel,
            'max_slots' => $maxSlots,
            'slots_used' => count($activeDefinitions),
            'active_edicts' => $activeDefinitions,
            'active_keys' => $activeKeys, // For easy checking in view
            'available_edicts' => $allDefinitions
        ];
    }

    /**
     * Attempt to activate an edict.
     */
    public function activateEdict(int $userId, string $edictKey): ServiceResponse
    {
        // 1. Verify Edict Exists
        $definition = $this->edictRepo->getDefinition($edictKey);
        if (!$definition) {
            return ServiceResponse::error('Invalid edict.');
        }

        // 2. Check Embassy Level & Slots
        $structures = $this->structureRepo->findByUserId($userId);
        $embassyLevel = $structures->embassy_level ?? 0;

        if ($embassyLevel < 1) {
            return ServiceResponse::error('You must build an Embassy first.');
        }

        $maxSlots = $this->calculateMaxSlots($embassyLevel);
        $activeEdicts = $this->edictRepo->findActiveByUserId($userId);

        if (count($activeEdicts) >= $maxSlots) {
            return ServiceResponse::error("Edict slots full ({$maxSlots}/{$maxSlots}). Revoke an existing edict first.");
        }

        // 3. Check for Conflicts (e.g., can't have conflicting types?)
        // For now, no hard conflicts, but we check if already active
        foreach ($activeEdicts as $active) {
            if ($active->edict_key === $edictKey) {
                return ServiceResponse::error('Edict is already active.');
            }
        }

        // 4. Activate
        if ($this->edictRepo->activate($userId, $edictKey)) {
            return ServiceResponse::success("{$definition->name} enacted successfully.");
        }

        return ServiceResponse::error('Failed to enact edict.');
    }

    /**
     * Revoke an active edict.
     */
    public function revokeEdict(int $userId, string $edictKey): ServiceResponse
    {
        if ($this->edictRepo->deactivate($userId, $edictKey)) {
            return ServiceResponse::success('Edict revoked.');
        }
        return ServiceResponse::error('Edict was not active.');
    }

    private function calculateMaxSlots(int $level): int
    {
        if ($level <= 0) return 0;
        if ($level < 5) return 1;
        if ($level < 10) return 2;
        if ($level < 15) return 3;
        return 4;
    }
}
