<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Services\EffectService; // --- NEW ---
use PDO;
use Throwable;

/**
 * Handles purchase logic for the Black Market.
 * Orchestrates transactions for premium features.
 * Updated to include transaction logging.
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
    private EffectService $effectService; // --- NEW ---

    public function __construct(
        PDO $db,
        Config $config,
        ResourceRepository $resourceRepo,
        StatsRepository $statsRepo,
        UserRepository $userRepo,
        BountyRepository $bountyRepo,
        AttackService $attackService,
        BlackMarketLogRepository $logRepo,
        EffectService $effectService // --- NEW ---
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
    }

    /**
     * Retrieves data required for the Undermarket (Actions) tab.
     * Encapsulates repository access for the controller.
     * 
     * @param int $userId
     * @return array
     */
    public function getUndermarketPageData(int $userId): array
    {
        return [
            'bounties' => $this->bountyRepo->getActiveBounties(10),
            'targets' => $this->userRepo->findAllNonNpcs()
        ];
    }

    // --- Option 1: Stat Respec ---
    public function purchaseStatRespec(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.stat_respec', 50);
        return $this->processPurchase($userId, $cost, function() use ($userId, $cost) {
            $stats = $this->statsRepo->findByUserId($userId);

            // Refund all allocated points
            $pointsToRefund = $stats->strength_points +
                $stats->constitution_points +
                $stats->wealth_points +
                $stats->dexterity_points +
                $stats->charisma_points;

            $newPool = $stats->level_up_points + $pointsToRefund;

            // Set stats to 0, pool to total
            $this->statsRepo->updateBaseStats($userId, $newPool, 0, 0, 0, 0, 0);
            
            // Log Transaction
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'stat_respec');
            
            return "Neural pathways reset. You have {$newPool} points available.";
        });
    }

    // --- Option 2: Turn Refill ---
    public function purchaseTurnRefill(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.turn_refill', 10);
        $amount = $this->config->get('black_market.quantities.turn_refill_amount', 50);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $amount) {
            $this->statsRepo->applyTurnAttackTurn($userId, $amount);
            
            // Log Transaction
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'turn_refill', ['amount' => $amount]);
            
            return "Stim-pack applied. {$amount} attack turns restored.";
        });
    }

    // --- Option 3: Citizen Package ---
    public function purchaseCitizens(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.citizen_package', 25);
        $amount = $this->config->get('black_market.quantities.citizen_package_amount', 500);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $amount) {
            // Use existing repo method to add citizens (0 credits, 0 interest)
            $this->resourceRepo->applyTurnIncome($userId, 0, 0, $amount);
            
            // Log Transaction
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'citizen_package', ['amount' => $amount]);
            
            return "Smuggling successful. {$amount} citizens have joined your empire.";
        });
    }

    // --- Option 8: The Void Container (Loot Box) ---
    public function openVoidContainer(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.void_container', 100);
        $lootTable = $this->config->get('black_market.void_container_loot', []);

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $lootTable) {
            // RNG Logic
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

            // Determine Quantity (if applicable)
            $qty = 0;
            if (isset($selected['min']) && isset($selected['max'])) {
                $qty = mt_rand($selected['min'], $selected['max']);
            }

            $msg = "";
            $outcomeType = 'success'; // Default to success (Green)
            $resources = $this->resourceRepo->findByUserId($userId);

            // --- Outcome Handling ---
            switch ($selected['type']) {
                case 'credits':
                    $this->resourceRepo->updateCredits($userId, $resources->credits + $qty);
                    $msg = "You found: " . number_format($qty) . " Credits!";
                    break;

                case 'unit':
                    $u = $selected['unit']; // 'soldiers' or 'spies'
                    $this->resourceRepo->updateTrainedUnits(
                        $userId,
                        $resources->credits, // No credit change
                        $resources->untrained_citizens, // Do not consume citizens
                        $resources->workers,
                        $u === 'soldiers' ? $resources->soldiers + $qty : $resources->soldiers,
                        $resources->guards,
                        $u === 'spies' ? $resources->spies + $qty : $resources->spies,
                        $resources->sentries
                    );
                    $msg = "Reinforcements: " . number_format($qty) . " " . ucfirst($u);
                    break;

                case 'crystals':
                    // Refund cost + jackpot amount
                    $this->resourceRepo->updateResources($userId, 0, $qty);
                    $msg = "JACKPOT! " . number_format($qty) . " Naquadah Crystals!";
                    break;

                case 'neutral':
                    $msg = $selected['text'] ?? "The container was empty.";
                    break;

                case 'credits_loss':
                    $newCredits = max(0, $resources->credits - $qty);
                    $this->resourceRepo->updateCredits($userId, $newCredits);
                    $loss = number_format($resources->credits - $newCredits);
                    $msg = ($selected['text'] ?? "Trap triggered!") . " Lost {$loss} Credits.";
                    $outcomeType = 'negative';
                    break;

                case 'unit_loss':
                    $u = $selected['unit'];
                    $currentUnits = $resources->{$u};
                    $newUnits = max(0, $currentUnits - $qty);
                    $loss = $currentUnits - $newUnits;

                    $this->resourceRepo->updateTrainedUnits(
                        $userId,
                        $resources->credits,
                        $resources->untrained_citizens,
                        $resources->workers,
                        $u === 'soldiers' ? $newUnits : $resources->soldiers,
                        $resources->guards,
                        $u === 'spies' ? $newUnits : $resources->spies,
                        $resources->sentries
                    );
                    $msg = ($selected['text'] ?? "Ambush!") . " Lost {$loss} " . ucfirst($u) . ".";
                    $outcomeType = 'negative';
                    break;

                default:
                    $msg = "The container dissolves into nothingness.";
                    break;
            }
            
            // Log Transaction with specific outcome
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'void_container', [
                'outcome_key' => $selectedKey,
                'outcome_type' => $selected['type'],
                'qty' => $qty,
                'result_text' => $msg
            ]);

            // Return complex result for processPurchase to handle
            return [
                'message' => $msg,
                'status' => $outcomeType
            ];
        });
    }

    // --- Option: Radar Jamming ---
    public function purchaseRadarJamming(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.radar_jamming', 50000);
        $duration = 240; // 4 Hours

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $duration) {
            $this->effectService->applyEffect($userId, 'jamming', $duration);
            
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'radar_jamming');
            
            return "Jamming signal broadcasted. Spies will be blind for 4 hours.";
        });
    }

    // --- Option: Safehouse (Peace Shield) ---
    public function purchaseSafehouse(int $userId): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.safehouse', 100000);
        $duration = 360; // 6 Hours

        return $this->processPurchase($userId, $cost, function() use ($userId, $cost, $duration) {
            $this->effectService->applyEffect($userId, 'peace_shield', $duration);
            
            $this->logRepo->log($userId, 'purchase', 'crystals', $cost, 'safehouse');
            
            return "You have vanished from the grid. You are safe for 6 hours.";
        });
    }

    // --- Option: Resource Laundering ---
    public function launderCredits(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) return ServiceResponse::error("Invalid amount.");
        
        $rate = $this->config->get('black_market.rates.laundering', 1.15); // 1.15 Credits per 1 Chip
        $chipAmount = (int)floor($amount / $rate);
        
        if ($chipAmount <= 0) return ServiceResponse::error("Amount too low to launder.");

        // Custom process because we deduct credits, not crystals
        $resources = $this->resourceRepo->findByUserId($userId);
        if ($resources->credits < $amount) {
            return ServiceResponse::error("Insufficient Credits.");
        }

        $this->db->beginTransaction();
        try {
            // Deduct Credits
            $this->resourceRepo->updateCredits($userId, $resources->credits - $amount);
            
            // Add Chips
            $this->resourceRepo->updateChips($userId, $resources->untraceable_chips + $chipAmount);
            
            // Log
            $this->logRepo->log($userId, 'launder', 'credits', $amount, null, ['chips_gained' => $chipAmount]);
            
            $this->db->commit();
            return ServiceResponse::success("Laundered " . number_format($amount) . " Credits into " . number_format($chipAmount) . " Untraceable Chips.");
            
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    // --- Withdraw Untraceable Chips ---
    public function withdrawChips(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) return ServiceResponse::error("Invalid amount.");

        $resources = $this->resourceRepo->findByUserId($userId);
        if ($resources->untraceable_chips < $amount) {
            return ServiceResponse::error("Insufficient Untraceable Chips.");
        }

        $this->db->beginTransaction();
        try {
            // Deduct Chips
            $this->resourceRepo->updateChips($userId, $resources->untraceable_chips - $amount);
            
            // Add Credits (no fee on withdrawal, fee was paid on deposit)
            $this->resourceRepo->updateCredits($userId, $resources->credits + $amount);
            
            // Log
            $this->logRepo->log($userId, 'withdraw', 'chips', $amount, null, ['credits_gained' => $amount]);
            
            $this->db->commit();
            return ServiceResponse::success("Withdrew " . number_format($amount) . " Untraceable Chips for " . number_format($amount) . " Credits.");
            
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    // --- Option 10: Place Bounty ---
    public function placeBounty(int $userId, string $targetName, float $amount): ServiceResponse
    {
        if ($amount < 10) return ServiceResponse::error("Minimum bounty is 10 Crystals.");

        $target = $this->userRepo->findByCharacterName($targetName);
        if (!$target) return ServiceResponse::error("Target not found.");
        if ($target->id === $userId) return ServiceResponse::error("You cannot place a bounty on yourself.");

        return $this->processPurchase($userId, $amount, function() use ($userId, $target, $amount) {
            $this->bountyRepo->create($target->id, $userId, $amount);
            
            // Log Transaction
            $this->logRepo->log($userId, 'bounty', 'crystals', $amount, null, [
                'target_id' => $target->id,
                'target_name' => $target->characterName
            ]);
            
            return "Bounty of " . number_format($amount) . " Crystals placed on {$target->characterName}.";
        });
    }

    // --- Option 4: Shadow Contract ---
    public function purchaseShadowContract(int $userId, string $targetName): ServiceResponse
    {
        $cost = $this->config->get('black_market.costs.shadow_contract', 500);

        // 1. Validation & Resource Check
        $resources = $this->resourceRepo->findByUserId($userId);
        if ($resources->naquadah_crystals < $cost) {
            return ServiceResponse::error("Insufficient Naquadah Crystals. Required: {$cost}");
        }

        if (empty(trim($targetName))) {
            return ServiceResponse::error('You must enter a target.');
        }

        // 2. Transaction
        $this->db->beginTransaction();
        try {
            // Deduct Cost
            $this->resourceRepo->updateResources($userId, 0, -$cost);

            // Execute Attack via Service with isHidden=true
            // We do NOT call processPurchase because AttackService manages its own complex state.
            $attackResponse = $this->attackService->conductAttack($userId, $targetName, 'plunder', true);

            if (!$attackResponse->isSuccess()) {
                throw new \Exception($attackResponse->message);
            }
            
            // Log Transaction
            $this->logRepo->log($userId, 'shadow_contract', 'crystals', $cost, null, [
                'target_name' => $targetName,
                'attack_message' => $attackResponse->message
            ]);

            $this->db->commit();
            return ServiceResponse::success("Shadow Contract Fulfilled. " . $attackResponse->message);

        } catch (Throwable $e) {
            $this->db->rollBack();
            return ServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Helper to handle the "Check funds -> Deduct -> Execute -> Commit" flow.
     * Updated to handle array return types from closures for metadata passing.
     */
    private function processPurchase(int $userId, float $cost, callable $action): ServiceResponse
    {
        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->naquadah_crystals < $cost) {
            return ServiceResponse::error("Insufficient Naquadah Crystals. Required: {$cost}");
        }

        $this->db->beginTransaction();
        try {
            // Deduct Cost
            $this->resourceRepo->updateResources($userId, 0, -$cost);

            // Execute Logic (Logging happens inside this closure)
            $result = $action();

            // Handle both simple string returns and complex array returns
            $message = is_array($result) ? ($result['message'] ?? 'Operation successful') : $result;
            $status = is_array($result) ? ($result['status'] ?? 'success') : 'success';

            $this->db->commit();

            // Pass the outcome type in data payload
            return ServiceResponse::success($message, ['outcome_type' => $status]);

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("Black Market Error: " . $e->getMessage());
            return ServiceResponse::error("Transaction failed: " . $e->getMessage());
        }
    }
}