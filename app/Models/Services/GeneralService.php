<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ResourceRepository;
use PDO;

class GeneralService
{
    private Config $config;
    private GeneralRepository $generalRepo;
    private ResourceRepository $resourceRepo;
    private PDO $db;
    
    // Config will be loaded in constructor
    private array $generalConfig;

    public function __construct(
        Config $config,
        GeneralRepository $generalRepo,
        ResourceRepository $resourceRepo,
        PDO $db
    ) {
        $this->config = $config;
        $this->generalRepo = $generalRepo;
        $this->resourceRepo = $resourceRepo;
        $this->db = $db;
        $this->generalConfig = $this->config->get('game_balance.generals', []);
    }
    
    public function getArmyCapacity(int $userId): int
    {
        $baseCapacity = $this->generalConfig['base_capacity'] ?? 500;
        $capacityPerGeneral = $this->generalConfig['capacity_per_general'] ?? 10000;
        
        $count = $this->generalRepo->countByUserId($userId);
        return $baseCapacity + ($count * $capacityPerGeneral);
    }
    
    public function getRecruitmentCost(int $currentCount): array
    {
        // Scaling Cost: +1M Credits per General
        $mult = $currentCount + 1;
        return [
            'credits' => 1000000 * $mult
        ];
    }
    
    public function recruitGeneral(int $userId, string $name): ServiceResponse
    {
        $count = $this->generalRepo->countByUserId($userId);
        $cost = $this->getRecruitmentCost($count);
        
        $resources = $this->resourceRepo->findByUserId($userId);
        
        if ($resources->credits < $cost['credits']) {
            return ServiceResponse::error("Insufficient Credits. Need " . number_format($cost['credits']));
        }
        
        if (empty($name)) {
            $name = 'General ' . ($count + 1);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Deduct Resources
            $this->resourceRepo->updateResources(
                $userId, 
                -$cost['credits']
            );
            
            $this->generalRepo->create($userId, $name);
            
            $this->db->commit();
            return ServiceResponse::success("General $name commissioned successfully!");
            
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error("Recruitment failed: " . $e->getMessage());
        }
    }
    
    public function equipWeapon(int $userId, int $generalId, string $weaponKey): ServiceResponse
    {
        $general = $this->generalRepo->findById($generalId);
        if (!$general || $general['user_id'] != $userId) {
            return ServiceResponse::error("General not found.");
        }
        
        $weapons = $this->config->get('elite_weapons', []);
        if (!isset($weapons[$weaponKey])) {
            return ServiceResponse::error("Invalid weapon.");
        }
        
        $weapon = $weapons[$weaponKey];
        $cost = $weapon['cost'];
        
        $resources = $this->resourceRepo->findByUserId($userId);
        
        // Validate Cost
        if ($resources->credits < ($cost['credits'] ?? 0)) return ServiceResponse::error("Not enough Credits.");
        if ($resources->naquadah_crystals < ($cost['naquadah_crystals'] ?? 0)) return ServiceResponse::error("Not enough Naquadah.");
        if ($resources->dark_matter < ($cost['dark_matter'] ?? 0)) return ServiceResponse::error("Not enough Dark Matter.");
        
        try {
            $this->db->beginTransaction();
            
            // Deduct Resources
            $this->resourceRepo->updateResources(
                $userId,
                -($cost['credits'] ?? 0)
            );
            
            $this->generalRepo->updateWeaponSlot($generalId, $weaponKey);
            
            $this->db->commit();
            return ServiceResponse::success("Equipped {$weapon['name']}!");
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log($e->getMessage());
            return ServiceResponse::error("Equip failed.");
        }
    }

    public function decommissionGeneral(int $userId, int $generalId): ServiceResponse
    {
        $general = $this->generalRepo->findById($generalId);
        if (!$general || $general['user_id'] != $userId) {
            return ServiceResponse::error("General not found.");
        }
        
        try {
            $this->db->beginTransaction();
            $this->generalRepo->delete($generalId);
            $this->db->commit();
            return ServiceResponse::success("General {$general['name']} has been decommissioned.");
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error("Decommission failed: " . $e->getMessage());
        }
    }

    public function getGenerals(int $userId): array
    {
        return $this->generalRepo->findByUserId($userId);
    }

    public function getGeneral(int $generalId): ?array
    {
        return $this->generalRepo->findById($generalId);
    }

    public function getResources(int $userId)
    {
        return $this->resourceRepo->findByUserId($userId);
    }
}