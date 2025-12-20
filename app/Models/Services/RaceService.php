<?php

namespace App\Models\Services;

use App\Models\Repositories\RaceRepository;
use App\Models\Repositories\UserRepository;
use PDO;

/**
 * Business logic for race selection.
 */
class RaceService
{
    public function __construct(
        private PDO $db,
        private RaceRepository $raceRepository,
        private UserRepository $userRepository
    ) {
    }

    /**
     * Gets all available races.
     * 
     * @return \App\Models\Entities\Race[]
     */
    public function getAllRaces(): array
    {
        return $this->raceRepository->findAll();
    }

    /**
     * Selects a race for a user (one-time only).
     * 
     * @param int $userId
     * @param int $raceId
     * @return bool
     * @throws \Exception If user already has a race or race doesn't exist
     */
    public function selectRace(int $userId, int $raceId): bool
    {
        // 1. Check if user exists
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new \Exception('User not found.');
        }

        // 2. PROTECTION: Check if user already has a race
        if ($user->race_id !== null) {
            throw new \Exception('You have already selected a race and cannot change it.');
        }

        // 3. Validate that the race exists
        $race = $this->raceRepository->findById($raceId);
        if (!$race) {
            throw new \Exception('Invalid race selected.');
        }

        // 4. Update the user's race
        return $this->userRepository->updateRace($userId, $raceId);
    }

    /**
     * Checks if a user needs to select a race.
     * 
     * @param int $userId
     * @return bool True if user needs to select a race
     */
    public function needsRaceSelection(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        return $user && $user->race_id === null;
    }
}
