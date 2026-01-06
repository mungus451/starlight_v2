<?php

namespace App\Models\Services;

use App\Models\Repositories\EffectRepository;
use App\Models\Repositories\UserRepository;
use Carbon\Carbon; // Assuming Carbon isn't available, we use DateTime
use DateTime;
use DateInterval;

class EffectService
{
    private EffectRepository $effectRepo;
    private UserRepository $userRepo;

    public function __construct(
        EffectRepository $effectRepo,
        UserRepository $userRepo
    ) {
        $this->effectRepo = $effectRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Applies an effect to a user.
     * 
     * @param int $userId
     * @param string $type ('jamming', 'peace_shield', 'wounded')
     * @param int $durationMinutes
     * @param array|null $metadata
     */
    public function applyEffect(int $userId, string $type, int $durationMinutes, ?array $metadata = null): void
    {
        $now = new DateTime();
        $now->modify("+{$durationMinutes} minutes");
        $expiresAt = $now->format('Y-m-d H:i:s');

        $this->effectRepo->addEffect($userId, $type, $expiresAt, $metadata);
    }

    /**
     * Checks if a user has a specific effect active.
     */
    public function hasActiveEffect(int $userId, string $type): bool
    {
        return $this->effectRepo->getActiveEffect($userId, $type) !== null;
    }

    /**
     * Gets details of an active effect.
     */
    public function getEffectDetails(int $userId, string $type): ?array
    {
        return $this->effectRepo->getActiveEffect($userId, $type);
    }

    /**
     * Removes an active effect (e.g., breaking a shield).
     */
    public function breakEffect(int $userId, string $type): void
    {
        $this->effectRepo->removeEffect($userId, $type);
    }

    /**
     * Updates the metadata of an active effect.
     */
    public function updateMetadata(int $userId, string $type, array $metadata): void
    {
        $this->effectRepo->updateMetadata($userId, $type, $metadata);
    }
}
