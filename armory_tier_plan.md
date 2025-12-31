# Plan: Armory Tiers 6-10 Expansion

## 1. Overview
We will expand the Armory system from Tier 5 to Tier 10. These high-tier "Elite" items will introduce **Naquadah Crystal** and **Dark Matter** costs, shifting the late-game economy from pure credit scaling to resource management.

## 2. Technical Updates (`ArmoryService.php`)
Currently, `manufactureItem` only checks and deducts `credits`. We must update it to support `naquadah_crystals` and `dark_matter` if defined in the item config.

1.  **Read Config:** Check item for `cost_crystals` and `cost_dark_matter`.
2.  **Validate:** Check user's resource balance.
3.  **Deduct:** Update `ResourceRepo` call to subtract these resources.
4.  **Batch Processing:** Ensure `processBatchManufacture` aggregates these costs correctly.

## 3. Stat & Cost Progression Model

### Soldier: Main Weapon (Attack)
*Curve: Doubling (Base 40 -> 640 @ T5 -> 20480 @ T10)*
*   **Tier 6:** `photon_lance` | +1280 Attack | Cost: 480k Cr + 10 Nq
*   **Tier 7:** `singularity_cannon` | +2560 Attack | Cost: 560k Cr + 50 Nq
*   **Tier 8:** `void_disintegrator` | +5120 Attack | Cost: 640k Cr + 250 Nq + 1 DM
*   **Tier 9:** `temporal_blaster` | +10240 Attack | Cost: 720k Cr + 1k Nq + 10 DM
*   **Tier 10:** `reality_shatter_gun` | +20480 Attack | Cost: 1M Cr + 5k Nq + 100 DM

### Soldier: Sidearm (Attack)
*   **Tier 6:** `plasma_sidearm` | +640 Attack | Cost: 300k Cr + 5 Nq
*   **Tier 7:** `nano_stinger` | +1280 Attack | Cost: 350k Cr + 25 Nq
*   **Tier 8:** `phase_pistol` | +2560 Attack | Cost: 400k Cr + 100 Nq + 1 DM
*   **Tier 9:** `neutron_blaster` | +5120 Attack | Cost: 450k Cr + 500 Nq + 5 DM
*   **Tier 10:** `entropy_hand_cannon` | +10240 Attack | Cost: 600k Cr + 2500 Nq + 50 DM

### Guard: Armor (Defense)
*Curve: Doubling (Base 40 -> 640 @ T5 -> 20480 @ T10)*
*   **Tier 6:** `neutronium_plate` | +1280 Def | Cost: 480k Cr + 10 Nq
*   **Tier 7:** `quantum_barrier_suit` | +2560 Def | Cost: 560k Cr + 50 Nq
*   **Tier 8:** `dark_matter_weave` | +5120 Def | Cost: 640k Cr + 250 Nq + 1 DM
*   **Tier 9:** `temporal_shield_armor` | +10240 Def | Cost: 720k Cr + 1k Nq + 10 DM
*   **Tier 10:** `event_horizon_vest` | +20480 Def | Cost: 1M Cr + 5k Nq + 100 DM

### Worker: Tool (Credit Bonus)
*Curve: Linear Step (Base 80 -> 800 @ T5 -> 2000 @ T10)*
*   **Tier 6:** `plasma_cutter` | +1000 Bonus | Cost: 480k Cr + 20 Nq
*   **Tier 7:** `matter_condenser` | +1200 Bonus | Cost: 560k Cr + 100 Nq
*   **Tier 8:** `gravity_well_extractor` | +1500 Bonus | Cost: 640k Cr + 500 Nq + 5 DM
*   **Tier 9:** `quantum_synthesizer` | +1800 Bonus | Cost: 720k Cr + 2k Nq + 20 DM
*   **Tier 10:** `reality_fabricator` | +2500 Bonus | Cost: 1M Cr + 10k Nq + 200 DM

## 4. Implementation Steps

1.  **Update Service:** Modify `ArmoryService.php` to handle `cost_crystals` and `cost_dark_matter`.
2.  **Update Config:** Append the new items to `config/armory_items.php`.
3.  **Update UI:** Ensure the `ArmoryPresenter` or View displays the new resource costs (if UI exists).
4.  **Verify:** Run tests to ensure manufacturing correctly deducts the new resources.
