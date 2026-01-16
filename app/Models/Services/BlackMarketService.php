<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Repositories\StructureRepository; // --- NEW ---
use App\Models\Services\EffectService;
use App\Models\Services\LevelUpService;
use App\Models\Services\GeneralService;
use PDO;
use Throwable;

/**
* Handles purchase logic for the Black Market.
* Orchestrates transactions for premium features.
*/
class BlackMarketService
{
private PDO $db;
private Config $config;
private ResourceRepository $resourceRepo;
private StatsRepository $statsRepo;
private UserRepository $userRepo;
private BountyRepository $bountyRepo;
private AttackService $attackService;
private BlackMarketLogRepository $logRepo;
private EffectService $effectService;
private HouseFinanceRepository $houseFinanceRepo;
private LevelUpService $levelUpService;
private StructureRepository $structureRepo; // --- NEW ---
private GeneralService $generalService;

public function __construct(
PDO $db,
Config $config,
ResourceRepository $resourceRepo,
StatsRepository $statsRepo,
UserRepository $userRepo,
BountyRepository $bountyRepo,
AttackService $attackService,
BlackMarketLogRepository $logRepo,
EffectService $effectService,
HouseFinanceRepository $houseFinanceRepo,
LevelUpService $levelUpService,
StructureRepository $structureRepo, // --- NEW ---
GeneralService $generalService
) {
$this->db = $db;
$this->config = $config;
$this->resourceRepo = $resourceRepo;
$this->statsRepo = $statsRepo;
$this->userRepo = $userRepo;
$this->bountyRepo = $bountyRepo;
$this->attackService = $attackService;
$this->logRepo = $logRepo;
$this->effectService = $effectService;
$this->houseFinanceRepo = $houseFinanceRepo;
$this->levelUpService = $levelUpService;
$this->structureRepo = $structureRepo;
$this->generalService = $generalService;
}

    public function getUndermarketPageData(int $userId): array
    {
        return [
            'bounties' => $this->bountyRepo->getActiveBounties(10),
            'targets' => $this->userRepo->findAllNonNpcs(),
            'isHighRiskActive' => $this->effectService->hasActiveEffect($userId, 'high_risk_protocol'),
            'isSafehouseCooldown' => $this->effectService->hasActiveEffect($userId, 'safehouse_cooldown')
        ];
    }
public function purchaseStatRespec(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.stat_respec', 50);
return $this->processPurchase($userId, $cost, function() use ($userId, $cost) {
$stats = $this->statsRepo->findByUserId($userId);
$pointsToRefund = $stats->strength_points + $stats->constitution_points + $stats->wealth_points + $stats->dexterity_points + $stats->charisma_points;
$newPool = $stats->level_up_points + $pointsToRefund;
$this->statsRepo->updateBaseStats($userId, $newPool, 0, 0, 0, 0, 0);
$this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'stat_respec');
return "Neural pathways reset. You have {$newPool} points available.";
});
}

public function purchaseTurnRefill(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.turn_refill', 10);
$amount = $this->config->get('black_market.quantities.turn_refill_amount', 50);

return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $amount) {
$this->statsRepo->applyTurnAttackTurn($userId, $amount);
$this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'turn_refill', ['amount' => $amount]);
return "Stim-pack applied. {$amount} attack turns restored.";
});
}

public function purchaseCitizens(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.citizen_package', 25);
$amount = $this->config->get('black_market.quantities.citizen_package_amount', 500);

return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $amount) {
// FIX: Use dedicated incrementer instead of the multi-arg Turn loop method
$this->resourceRepo->incrementUntrainedCitizens($userId, $amount);

// Log Transaction
$this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'citizen_package', ['amount' => $amount]);

return "Smuggling successful. {$amount} citizens have joined your empire.";
});
}

    public function openVoidContainer(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.void_container', 100);
        $lootTable = $this->config->get('black_market.void_container_loot', []);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $lootTable) {
            $totalWeight = array_sum(array_column($lootTable, 'weight'));
            $roll = mt_rand(1, $totalWeight);
            $current = 0;
            $selected = null;
            $selectedKey = '';

            foreach ($lootTable as $key => $item) {
                $current += $item['weight'];
                if ($roll <= $current) {
                    $selected = $item;
                    $selectedKey = $key;
                    break;
                }
            }

            $qty = (isset($selected['min']) && isset($selected['max'])) ? mt_rand($selected['min'], $selected['max']) : 0;
            $msg = "";
            $outcomeType = 'success';
            $resources = $this->resourceRepo->findByUserId($userId);

            switch ($selected['type']) {
                case 'credits':
                    $this->resourceRepo->updateCredits($userId, $resources->credits + $qty);
                    $msg = "You found: " . number_format($qty) . " Credits!";
                    break;
                case 'unit':
                    $u = $selected['unit'];
                    $newS = $resources->soldiers + ($u === 'soldiers' ? $qty : 0);
                    $newG = $resources->guards + ($u === 'guards' ? $qty : 0);
                    $newSp = $resources->spies + ($u === 'spies' ? $qty : 0);
                    $newSe = $resources->sentries + ($u === 'sentries' ? $qty : 0);
                    $this->resourceRepo->updateTrainedUnits($userId, $resources->credits, $resources->untrained_citizens, $resources->workers, $newS, $newG, $newSp, $newSe);
                    $msg = "Reinforcements: " . number_format($qty) . " " . ucfirst($u);
                    break;
                case 'crystals':
                    $this->resourceRepo->updateResources($userId, 0, $qty);
                    $msg = "JACKPOT! " . number_format($qty) . " Naquadah Crystals!";
                    break;
                case 'dark_matter':
                    $this->resourceRepo->updateResources($userId, 0, 0, $qty);
                    $msg = "You found: " . number_format($qty) . " Dark Matter!";
                    break;
                case 'protoform':
                    $this->resourceRepo->updateResources($userId, 0, 0, 0, $qty);
                    $msg = "You found: " . number_format($qty) . " Protoform!";
                    break;
                case 'research_data':
                    $this->resourceRepo->updateResources($userId, 0, 0, 0, 0, $qty);
                    $msg = "You found: " . number_format($qty) . " Research Data!";
                    break;
                case 'xp':
                    $this->levelUpService->grantExperience($userId, $qty);
                    $msg = "Combat Data Decrypted: +" . number_format($qty) . " XP!";
                    break;
                case 'turns':
                    $this->statsRepo->applyTurnAttackTurn($userId, $qty);
                    $msg = "Neural Stim-Pack Used: +" . number_format($qty) . " Attack Turns!";
                    break;
                case 'cursed':
                    // Grant Resource
                    $resType = $selected['resource'];
                    if ($resType === 'crystals') {
                        $this->resourceRepo->updateResources($userId, 0, $qty);
                        $resLabel = number_format($qty) . " Naquadah Crystals";
                    } elseif ($resType === 'dark_matter') {
                        $this->resourceRepo->updateResources($userId, 0, 0, $qty);
                        $resLabel = number_format($qty) . " Dark Matter";
                    } else {
                        $resLabel = "Unknown Artifacts";
                    }

                    // Apply Debuff
                    $key = $selected['debuff_key'];
                    $dur = $selected['duration'];
                    $this->effectService->applyEffect($userId, $key, $dur);
                    
                    $msg = "{$resLabel} secured... but at a cost! " . ($selected['text'] ?? "Debuff applied.");
                    $outcomeType = 'warning'; // Special UI state? Or just success/negative. Let's stick to warning if handled by FE, or just success with text.
                    break;
                case 'buff':
                    $key = $selected['buff_key'];
                    
                    if ($key === 'action_clear_safehouse') {
                        if ($this->effectService->hasActiveEffect($userId, 'safehouse_cooldown')) {
                            $this->effectService->breakEffect($userId, 'safehouse_cooldown');
                            $msg = $selected['text'] ?? "Safehouse cooldown reset!";
                        } else {
                            $msg = "Safehouse systems checked. No cooldown was active.";
                        }
                    } else {
                        $dur = $selected['duration'];
                        $this->effectService->applyEffect($userId, $key, $dur);
                        $msg = $selected['text'] ?? "Buff applied!";
                    }
                    break;
                case 'debuff':
                    $key = $selected['buff_key'];
                    $dur = $selected['duration'];
                    $this->effectService->applyEffect($userId, $key, $dur);
                    $msg = $selected['text'] ?? "System Malfunction Detected!";
                    $outcomeType = 'negative';
                    break;
                case 'neutral':
                    $msg = $selected['text'] ?? "The container was empty.";
                    break;
                case 'credits_loss':
                    $newCredits = max(0, $resources->credits - $qty);
                    $this->resourceRepo->updateCredits($userId, $newCredits);
                    $msg = ($selected['text'] ?? "Trap triggered!") . " Lost Credits.";
                    $outcomeType = 'negative';
                    break;
                case 'unit_loss':
                    $u = $selected['unit'];
                    $newUnits = max(0, ($resources->{$u} ?? 0) - $qty);
                    $newS = ($u === 'soldiers') ? $newUnits : $resources->soldiers;
                    $newG = ($u === 'guards') ? $newUnits : $resources->guards;
                    $newSp = ($u === 'spies') ? $newUnits : $resources->spies;
                    $newSe = ($u === 'sentries') ? $newUnits : $resources->sentries;
                    $this->resourceRepo->updateTrainedUnits($userId, $resources->credits, $resources->untrained_citizens, $resources->workers, $newS, $newG, $newSp, $newSe);
                    $msg = ($selected['text'] ?? "Ambush!") . " Lost " . ucfirst($u) . ".";
                    $outcomeType = 'negative';
                    break;
            }

            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'void_container', ['outcome_key' => $selectedKey, 'outcome_type' => $selected['type'], 'qty' => $qty, 'result_text' => $msg]);
            return ['message' => $msg, 'status' => $outcomeType];
        });
    }

    public function purchaseRadarJamming(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.radar_jamming', 50000);
        return $this->processPurchase($userId, $cost, function() use ($userId, $cost) {
            $this->effectService->applyEffect($userId, 'jamming', 240);
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'radar_jamming');
            return "Jamming signal broadcasted. Spies will be blind for 4 hours.";
        });
    }

    public function purchaseSafehouse(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.safehouse', 100000);
        $duration = $this->config->get('black_market.durations.safehouse_active', 240); // 4 Hours
        $cooldown = $this->config->get('black_market.durations.safehouse_cooldown', 720); // 12 Hours
        
        // Prevent if High Risk Protocol is active
        if ($this->effectService->hasActiveEffect($userId, 'high_risk_protocol')) {
            return ServiceResponse::error("Cannot activate Safehouse while High Risk Protocol is active.");
        }

        // Prevent if Safehouse Block (Void Debuff) is active
        if ($this->effectService->hasActiveEffect($userId, 'safehouse_block')) {
            return ServiceResponse::error("Safehouse beacon active. Location compromised. Cannot activate.");
        }

        // Prevent if Cooldown is active
        if ($this->effectService->hasActiveEffect($userId, 'safehouse_cooldown')) {
            $cdEffect = $this->effectService->getEffectDetails($userId, 'safehouse_cooldown');
            $expiresAt = new \DateTime($cdEffect['expires_at']);
            $diff = $expiresAt->diff(new \DateTime());
            
            $hours = $diff->h + ($diff->d * 24);
            $timeStr = $hours > 0 ? "{$hours}h {$diff->i}m" : "{$diff->i}m";

            return ServiceResponse::error("Safehouse systems are rebooting. Cooldown active for {$timeStr}.");
        }

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $duration, $cooldown) {
            // 1. Apply Protection
            $this->effectService->applyEffect($userId, 'peace_shield', $duration);
            
            // 2. Apply Cooldown (Overlaps with protection)
            $this->effectService->applyEffect($userId, 'safehouse_cooldown', $cooldown);

            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'safehouse');
            
            $durationHours = round($duration / 60, 1);
            return "You have vanished from the grid. You are safe for {$durationHours} hours.";
        });
    }
    public function purchaseSafehouseCracker(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.safehouse_cracker', 75000000.0);
        $amount = $this->config->get('black_market.quantities.safehouse_cracker_amount', 1);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $amount) {
            
            // Check for existing breach permit
            $existing = $this->effectService->getEffectDetails($userId, 'safehouse_breach');
            
            if ($existing) {
                // Add to existing charges
                $meta = isset($existing['metadata']) ? json_decode($existing['metadata'], true) : [];
                $currentCharges = $meta['charges'] ?? 0;
                $newCharges = $currentCharges + $amount;
                
                // Update Metadata
                $this->effectService->updateMetadata($userId, 'safehouse_breach', ['charges' => $newCharges]);
                $msg = "Breach permit updated. You now have {$newCharges} safehouse bypasses authorized.";
            } else {
                // Create new effect (Duration: 2 days to use it)
                $this->effectService->applyEffect($userId, 'safehouse_breach', 2880, ['charges' => $amount]);
                $msg = "Breach permit acquired. You have {$amount} authorized safehouse bypass.";
            }

            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'safehouse_cracker', ['charges_gained' => $amount]);
            return $msg;
        });
    }

    public function purchaseHighRiskBuff(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.high_risk_buff', 50000000);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost) {
            // Remove Safehouse if active
            if ($this->effectService->hasActiveEffect($userId, 'peace_shield')) {
                $this->effectService->breakEffect($userId, 'peace_shield');
            }

            // Duration: 24h
            $this->effectService->applyEffect($userId, 'high_risk_protocol', 1440);
            
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'high_risk_buff');
            return "High Risk Protocol initiated. Income boosted, casualty protocols optimized. Safehouse disabled.";
        });
    }

    public function terminateHighRiskProtocol(int $userId): ServiceResponse
    {
        if (!$this->effectService->hasActiveEffect($userId, 'high_risk_protocol')) {
            return ServiceResponse::error("High Risk Protocol is not active.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Remove the Buff
            $this->effectService->breakEffect($userId, 'high_risk_protocol');
            
            // 2. Apply Safehouse Cooldown (1 Hour = 60 Minutes)
            $this->effectService->applyEffect($userId, 'safehouse_cooldown', 60);

            // 3. Log it (No cost, just an action)
            $this->logRepo->log($userId, 'terminate', 'none', 0, 'high_risk_protocol');

            $this->db->commit();
            return ServiceResponse::success("Protocol terminated. Safehouse systems require 1 hour to reboot.");
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ServiceResponse::error("System Failure: " . $e->getMessage());
        }
    }

    public function launderCredits(int $userId, int $amount): ServiceResponse{
if ($amount <= 0) return ServiceResponse::error("Invalid amount.");
$rate = $this->config->get('black_market.rates.laundering', 1.15);
$chipAmount = (int)floor($amount / $rate);
if ($chipAmount <= 0) return ServiceResponse::error("Amount too low to launder.");
$resources = $this->resourceRepo->findByUserId($userId);
if ($resources->credits < $amount) return ServiceResponse::error("Insufficient Credits.");

$this->db->beginTransaction();
try {
$this->resourceRepo->updateCredits($userId, $resources->credits - $amount);
$this->resourceRepo->updateChips($userId, $resources->untraceable_chips + $chipAmount);
$this->logRepo->log($userId, 'launder', 'credits', $amount, null, ['chips_gained' => $chipAmount]);
$this->db->commit();
return ServiceResponse::success("Laundered " . number_format($amount) . " Credits.");
} catch (Throwable $e) {
$this->db->rollBack();
return ServiceResponse::error($e->getMessage());
}
}

public function withdrawChips(int $userId, int $amount): ServiceResponse
{
if ($amount <= 0) return ServiceResponse::error("Invalid amount.");
$resources = $this->resourceRepo->findByUserId($userId);
if ($resources->untraceable_chips < $amount) return ServiceResponse::error("Insufficient Chips.");

$this->db->beginTransaction();
try {
$this->resourceRepo->updateChips($userId, $resources->untraceable_chips - $amount);
$this->resourceRepo->updateCredits($userId, $resources->credits + $amount);
$this->logRepo->log($userId, 'withdraw', 'chips', $amount, null, ['credits_gained' => $amount]);
$this->db->commit();
return ServiceResponse::success("Withdrew " . number_format($amount) . " Chips.");
} catch (Throwable $e) {
$this->db->rollBack();
return ServiceResponse::error($e->getMessage());
}
}

public function placeBounty(int $userId, string $targetName, float $amount): ServiceResponse
{
if ($amount < 10) return ServiceResponse::error("Minimum bounty is 10 Crystals.");
$target = $this->userRepo->findByCharacterName($targetName);
if (!$target) return ServiceResponse::error("Target not found.");
if ($target->id === $userId) return ServiceResponse::error("You cannot place a bounty on yourself.");

return $this->processPurchase($userId, $amount, function() use ($userId, $target, $amount) {
$this->bountyRepo->create($target->id, $userId, $amount);
$this->logRepo->log($userId, 'bounty', 'crystals', $amount, null, ['target_id' => $target->id, 'target_name' => $target->characterName]);
return "Bounty of " . number_format($amount) . " Crystals placed.";
});
}

public function purchaseShadowContract(int $userId, string $targetName): ServiceResponse
{
$cost = $this->config->get('black_market.costs.shadow_contract', 500);
$resources = $this->resourceRepo->findByUserId($userId);
if ($resources->naquadah_crystals < $cost) return ServiceResponse::error("Insufficient Crystals.");

$this->db->beginTransaction();
try {
$this->resourceRepo->updateResources($userId, 0, -$cost);
$attackResponse = $this->attackService->conductAttack($userId, $targetName, 'plunder', true);
if (!$attackResponse->isSuccess()) throw new \Exception($attackResponse->message);
$this->logRepo->log($userId, 'shadow_contract', 'crystals', $cost, null, ['target_name' => $targetName, 'attack_message' => $attackResponse->message]);
$this->db->commit();
return ServiceResponse::success("Shadow Contract Fulfilled.");
} catch (Throwable $e) {
$this->db->rollBack();
return ServiceResponse::error($e->getMessage());
}
}

    public function synthesizeDarkMatter(int $userId, string $sourceCurrency, float $quantity): ServiceResponse
    {
        if ($quantity <= 0) return ServiceResponse::error("Invalid quantity.");
        
        $resources = $this->resourceRepo->findByUserId($userId);
        $dmReceived = 0;

        if ($sourceCurrency === 'credits') {
            // Credits are Integers. Floor the input.
            $creditsToSpend = (int)floor($quantity);
            if ($creditsToSpend < 1) return ServiceResponse::error("Minimum 1 Credit required.");

            // Ratio: 10,000 Credits -> 1.0 Dark Matter (Base)
            // House takes 30% -> 0.7 Dark Matter per 10,000 Credits.
            // 1 Credit -> 0.00007 DM.
            if ($resources->credits < $creditsToSpend) return ServiceResponse::error("Insufficient Credits.");
            
            $baseDM = $creditsToSpend / 10000;
            $dmReceived = $baseDM * 0.7;
            $dmTaxed = $baseDM * 0.3;
            
            $this->db->beginTransaction();
            try {
                $this->resourceRepo->updateCredits($userId, $resources->credits - $creditsToSpend);
                $this->resourceRepo->updateResources($userId, 0, 0, $dmReceived, 0);
                
                // Track House Tax
                $this->houseFinanceRepo->updateFinances(1, 0, 0, $dmTaxed);
                
                $this->logRepo->log($userId, 'synthesis', 'credits', $creditsToSpend, null, ['dark_matter_gained' => $dmReceived, 'dark_matter_taxed' => $dmTaxed]);
                
                $this->db->commit();
                return ServiceResponse::success("Synthesis Complete. " . number_format($creditsToSpend) . " Credits converted to " . number_format($dmReceived, 9) . " Dark Matter.");
            } catch (Throwable $e) {
                $this->db->rollBack();
                return ServiceResponse::error($e->getMessage());
            }

        } elseif ($sourceCurrency === 'crystals') {
            // Crystals are Decimals. Use float directly.
            // Ratio: 10 Crystals -> 1.0 Dark Matter (Base)
            // House takes 30% -> 0.7 Dark Matter per 10 Crystals.
            if ($resources->naquadah_crystals < $quantity) return ServiceResponse::error("Insufficient Crystals.");
            
            $baseDM = $quantity / 10;
            $dmReceived = $baseDM * 0.7;
            $dmTaxed = $baseDM * 0.3;
            
            $this->db->beginTransaction();
            try {
                $this->resourceRepo->updateResources($userId, 0, -$quantity, $dmReceived, 0);
                
                // Track House Tax
                $this->houseFinanceRepo->updateFinances(1, 0, 0, $dmTaxed);
                
                $this->logRepo->log($userId, 'synthesis', 'crystals', $quantity, null, ['dark_matter_gained' => $dmReceived, 'dark_matter_taxed' => $dmTaxed]);
                
                $this->db->commit();
                return ServiceResponse::success("Synthesis Complete. " . number_format($quantity, 4) . " Crystals converted to " . number_format($dmReceived, 9) . " Dark Matter.");
            } catch (Throwable $e) {
                $this->db->rollBack();
                return ServiceResponse::error($e->getMessage());
            }
        }

        return ServiceResponse::error("Invalid currency source.");
    }

    public function draftMercenaries(int $userId, string $unitType, int $quantity): ServiceResponse
    {
        $structures = $this->structureRepo->findByUserId($userId);
        if (!$structures || $structures->mercenary_outpost_level <= 0) {
            return ServiceResponse::error("You must build a Mercenary Outpost to draft units.");
        }

        if ($quantity <= 0) {
            return ServiceResponse::error("Quantity must be a positive number.");
        }

        $config = $this->config->get('black_market.mercenary_outpost');
        $limitPerLevel = $config['limit_per_level'] ?? 500;
        $maxDraft = $structures->mercenary_outpost_level * $limitPerLevel;

        if ($quantity > $maxDraft) {
            return ServiceResponse::error("You can only draft a maximum of " . number_format($maxDraft) . " units at your current outpost level.");
        }

        $unitCost = $config['costs'][$unitType]['dark_matter'] ?? null;
        if ($unitCost === null) {
            return ServiceResponse::error("Invalid unit type for drafting.");
        }

        $totalCost = $unitCost * $quantity;
        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->dark_matter < $totalCost) {
            return ServiceResponse::error("Insufficient Dark Matter. Cost: " . number_format($totalCost, 2));
        }
        
        if ($unitType === 'soldiers') {
            $cap = $this->generalService->getArmyCapacity($userId);
            if (($resources->soldiers + $quantity) > $cap) {
                return ServiceResponse::error("Army Limit Reached ({$cap}). You cannot exceed your army capacity.");
            }
        }
        
        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateResources($userId, 0, 0, -$totalCost);
            
            $newUntrained = $resources->untrained_citizens;
            $newWorkers = $resources->workers;
            $newSoldiers = $resources->soldiers;
            $newGuards = $resources->guards;
            $newSpies = $resources->spies;
            $newSentries = $resources->sentries;

            match ($unitType) {
                'soldiers' => $newSoldiers += $quantity,
                'guards'   => $newGuards += $quantity,
                'spies'    => $newSpies += $quantity,
                'sentries' => $newSentries += $quantity,
                default    => null,
            };

            $this->resourceRepo->updateTrainedUnits(
                $userId,
                $resources->credits,
                $newUntrained,
                $newWorkers,
                $newSoldiers,
                $newGuards,
                $newSpies,
                $newSentries
            );

            $this->logRepo->log($userId, 'draft', 'dark_matter', $totalCost, $unitType, ['quantity' => $quantity]);

            $this->db->commit();
            return ServiceResponse::success("Successfully drafted " . number_format($quantity) . " " . ucfirst($unitType) . ".");

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ServiceResponse::error("An error occurred during drafting: " . $e->getMessage());
        }
    }

    private function processPurchase(int $userId, float $cost, callable $action): ServiceResponse{
        // --- Orbital Trade Port Discount ---
        $structures = $this->structureRepo->findByUserId($userId);
        $bmConfig = $this->config->get('game_balance.black_market');
        
        $level = $structures->orbital_trade_port_level ?? 0;
        if ($level > 0) {
            $discountPerLevel = $bmConfig['orbital_trade_port_discount_per_level'] ?? 0.005;
            $maxDiscount = $bmConfig['max_orbital_trade_port_discount'] ?? 0.25;
            
            $discountPct = min($level * $discountPerLevel, $maxDiscount);
            $cost = floor($cost * (1.0 - $discountPct));
        }

        $resources = $this->resourceRepo->findByUserId($userId);
        if ($resources->naquadah_crystals < $cost) return ServiceResponse::error("Insufficient Naquadah Crystals.");

        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateResources($userId, 0, -$cost);
            $result = $action();
            $message = is_array($result) ? ($result['message'] ?? 'Operation successful') : $result;
            $status = is_array($result) ? ($result['status'] ?? 'success') : 'success';
            $this->db->commit();
            return ServiceResponse::success($message, ['outcome_type' => $status]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ServiceResponse::error("Transaction failed: " . $e->getMessage());
        }
    }
}