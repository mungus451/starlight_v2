# Implementation Plan: Dynamic & Strategic NPC Overhaul

## 1. Objective
Transform the current weight-based NPC system into a "Persona-Driven Strategic AI" that mimics experienced player behavior. NPCs will aggressively scale their economies, adapt their military composition based on opponents, manage resources intelligently (saving vs. spending), and execute coordinated strategies.

## 2. Core Architecture: `NpcDirector`
We will introduce a new `NpcDirector` class (or refactor `NpcService`) to manage high-level decision making.

### 2.1. The "Persona" Engine (State Machine)
Instead of static weights, NPCs will have dynamic states managed by a Finite State Machine (FSM).
*   **States:** `GROWTH`, `PREPARATION`, `AGGRESSION`, `RECOVERY`, `TURTLE`.
*   **Transition Logic:**
    *   *Low Income?* -> `GROWTH` (Focus on Mines/Workers)
    *   *High Income + Low Army?* -> `PREPARATION` (Training/Armory)
    *   *High Power + Vulnerable Targets?* -> `AGGRESSION` (Scout/Attack)
    *   *Recent Loss?* -> `RECOVERY` (Rebuild essential defense/workers)

### 2.2. Enhanced Personalities
Expand the 3 existing profiles into distinct archetypes with specific "Grand Strategies":
1.  **The Industrialist (Eco-Boomer):**
    *   **Strategy:** Aggressive geometric scaling of Workers/Mines first. Ignores army until income hits a threshold ($10M/turn).
    *   **Tech:** Prioritizes Mining Tech & Bank Interest.
    *   **Defense:** Minimum viable sentries to prevent spying.
2.  **The Reaver (Rush Aggro):**
    *   **Strategy:** Early aggression. Trains cheap units (Soldiers) en masse to farm inactive players/weak neighbors for resources.
    *   **Tech:** Weaponry & Spy tech.
    *   **Targeting:** Hunts high-resource/low-defense players.
3.  **The Technocrat (Quality > Quantity):**
    *   **Strategy:** Rushes high-tier tech (Armory/Nanite) before building a massive army.
    *   **Composition:** Smaller, elite armies with maxed upgrades.
    *   **Economy:** Balanced approach, heavy reliance on crystal conversion.
4.  **The Vault Keeper (Turtle/Banker):**
    *   **Strategy:** Max Defense (Guards/Sentries/Shields). Hoards gold in the bank to generate interest.
    *   **Behavior:** Rarely attacks unless provoked. Hard counter to Reavers.

## 3. Detailed Logic Implementation

### 3.1. Economic Intelligence (`manageEconomy`)
*   **"Golden Ratio" Scaling:** NPCs will maintain a strict ratio of Worker Cost vs. Income Return. They will prioritize workers until the ROI drops below a certain curve (e.g., 20 turns to ROI).
*   **Resource Balancing:** If an NPC needs Crystals for an upgrade but has excess Gold, it will use the `CurrencyConverterService` intelligently, checking market rates (if dynamic) or standard conversion.
*   **Saving Logic:** If the current goal (e.g., Level 10 Command Nexus) is unaffordable, the NPC will *hold* turns/resources instead of spending small amounts on useless low-tier upgrades.

### 3.2. Military Intelligence (`manageMilitary`)
*   **Unit Composition:**
    *   *Offensive:* Ratio of Spies (to blind) vs. Soldiers (to kill).
    *   *Defensive:* Ratio of Sentries (counter-intel) vs. Guards.
*   **Armory Logic:** Instead of random buys, buy items that complement the current stats. (e.g., if Attack is high but Defense is low, buy Armor).
*   **Tech Tree:** Prioritize `Laser Tech` (Attack) for Reavers, `Shield Tech` (Defense) for Turtles.

### 3.3. Aggression & Targeting (`manageAggression`)
*   **The "Kill List":** NPCs will maintain a short-term memory of targets.
    *   *Scouted Vulnerable:* High loot, low defense.
    *   *Revenge Target:* Player who attacked them recently.
*   **Risk Assessment:**
    *   Before attacking, run a `PowerCalculator` simulation.
    *   **Smart Spy:** Send a spy mission first. If successful, check `loot_potential` vs `projected_losses`.
    *   **Safety Margin:** Only attack if Win Probability > 80% AND Profit > Cost of Units Lost.
*   **Safe House Awareness:** Check if target has `peace_shield` (using the new feature). If yes, remove from list and find new target.

### 3.4. Dynamic Difficulty Scaling (DDS)
*   NPCs will scale their aggression based on the *average server power*.
*   If the top 10 players are miles ahead, NPCs will cheat slightly (bonus income/XP) to remain relevant threats, or purely optimize their build order to catch up. (We will stick to optimization first, no "cheating" unless requested).

## 4. Execution Steps

1.  **Refactor `NpcService.php`:**
    *   Create `App\Models\AI\Strategies\` namespace.
    *   Implement `IndustrialistStrategy`, `ReaverStrategy`, etc., implementing a common `NpcStrategyInterface`.
2.  **Implement `NpcBrain` Class:**
    *   Handles the state machine (Growth vs. War).
    *   Injects the correct Strategy based on NPC ID/Class.
3.  **Update Database:**
    *   Add `npc_strategy` column to `users` table (or map via ID in code to save DB migrations).
    *   Add `npc_memory` (JSON) to store "Target List" and "Current Goal".
4.  **Rewrite Cron Logic:**
    *   Ensure `cron/process_npcs.php` calls the new `NpcBrain->think()` method.

## 5. Success Metrics
*   NPCs successfully upgrade purely on their own income.
*   NPCs effectively "farm" weak players.
*   NPCs defend themselves against average player attacks.
*   Leaderboard presence: At least 1 NPC in the top 50 without arbitrary stat padding.
