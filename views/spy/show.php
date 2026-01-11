<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\UserResource $resources */
/* @var \App\Models\Entities\UserStats $stats */
/* @var array $costs */
/* @var array $targets */
/* @var array $pagination */
/* @var int $perPage */
/* @var array $operation  -- PRE-CALCULATED DATA -- */
?>

<div class="container-full">
    <h1>Espionage</h1>

    <div class="resource-header-card">
        <div class="header-stat">
            <span>Your Spies</span>
            <strong class="accent-blue"><?= number_format($resources->spies) ?> Spies</strong>
        </div>
        <div class="header-stat">
            <span>Your Attack Turns</span>
            <strong class="accent"><?= number_format($stats->attack_turns) ?> Turns</strong>
        </div>
        <div class="header-stat">
            <span>Operation Cost</span>
            <strong>
                <?= $operation['turn_cost'] ?> Turn
            </strong>
        </div>
        <div class="header-stat">
            <span>
                <a href="/spy/reports" class="btn-submit btn-accent" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Reports
                </a>
            </span>
        </div>
    </div>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Level</th>
                    <th>Credits</th>
                    <th>Army Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($targets)): ?>
                    <tr><td colspan="5" style="text-align: center;">No targets found.</td></tr>
                <?php else: ?>
                    <?php foreach ($targets as $target): ?>
                        <tr class="player-row">
                            <td data-label="Player" style="display: flex; align-items: center; gap: 1rem;">
                                <?php if ($target['profile_picture_url']): ?>
                                    <img src="/serve/avatar/<?= htmlspecialchars($target['profile_picture_url']) ?>" alt="Avatar" class="player-avatar btn-spy-modal" data-target-name="<?= htmlspecialchars($target['character_name']) ?>" style="width: 40px; height: 40px;">
                                <?php else: ?>
                                    <svg class="player-avatar player-avatar-svg btn-spy-modal" data-target-name="<?= htmlspecialchars($target['character_name']) ?>" style="width: 40px; height: 40px; padding: 0.5rem;" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                <?php endif; ?>
                                <a href="/profile/<?= $target['id'] ?>" style="font-weight: 600; font-size: 1.1rem;">
                                    <?= htmlspecialchars($target['character_name']) ?>
                                </a>
                            </td>
                            
                            <td data-label="Level"><strong><?= $target['level'] ?></strong></td>
                            <td data-label="Credits"><?= number_format($target['credits']) ?></td>
                            <td data-label="Army Size"><?= number_format($target['army_size']) ?></td>
                            
                            <td data-label="Actions">
                                <button class="btn-submit btn-accent btn-spy-modal" 
                                        data-target-name="<?= htmlspecialchars($target['character_name']) ?>"
                                        style="margin: 0; padding: 0.5rem 1rem;">
                                    Spy
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="/spy/page/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="/spy/page/<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="/spy/page/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fixed Modal Overlay -->
<div class="modal-overlay" id="spy-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Espionage</h3>
            <button class="modal-close-btn" id="modal-close-btn">&times;</button>
        </div>
        
        <form action="/spy/conduct" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <input type="hidden" name="target_name" id="modal-target-name" value="">
            
            <div class="modal-summary">
                Launch operation against:
                <br>
                <strong id="modal-target-name-display" style="font-size: 1.2rem; color: var(--accent-2); display: block; margin: 0.5rem 0;"></strong>
                
                Sends <strong><?= number_format($operation['spies_to_send']) ?> spies</strong>.
                <br>
                Cost: <strong><?= $operation['turn_cost'] ?> Attack Turn</strong>.
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="button" class="btn-submit" id="modal-cancel-btn" style="background: transparent; border: 1px solid var(--border);">Cancel</button>
                <button type="submit" class="btn-submit btn-accent" style="flex-grow: 1;" <?= $operation['spies_to_send'] <= 0 ? 'disabled' : '' ?>>
                    <?= $operation['spies_to_send'] <= 0 ? 'No Spies Available' : 'Launch' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/spy.js"></script>