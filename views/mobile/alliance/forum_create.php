<?php
// --- Mobile Forum Create Topic View ---
/* @var string $csrf_token */
/* @var int $allianceId */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">New Topic</h1>
    </div>

    <div class="mobile-card">
        <div class="mobile-card-content" style="display: block;">
            <form action="/alliance/forum/topic/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="mobile-input" style="width: 100%;" required>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" class="mobile-input" style="width: 100%; height: 200px;" required></textarea>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Create Topic</button>
            </form>
        </div>
    </div>

    <a href="/alliance/forum" class="btn btn-outline" style="margin-top: 2rem; text-align: center;">
        <i class="fas fa-times"></i> Cancel
    </a>
</div>
