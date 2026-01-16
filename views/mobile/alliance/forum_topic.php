<?php
// --- Mobile Forum Topic View ---
/* @var object $topic */
/* @var array $posts */
/* @var object $permissions */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">
            <?php if ($topic->is_locked): ?><i class="fas fa-lock"></i><?php endif; ?>
            <?php if ($topic->is_pinned): ?><i class="fas fa-thumbtack"></i><?php endif; ?>
            <?= htmlspecialchars($topic->title) ?>
        </h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">
            By: <?= htmlspecialchars($topic->author_name) ?> on <?= $topic->formatted_created_at ?>
        </p>
    </div>

<div class="topic-view-container p-3">
    <!-- Moderation Actions -->
    <?php if (!empty($perms) && $perms['can_manage_forum']): ?>
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
        <form action="/alliance/forum/topic/<?= $topic->id ?>/pin" method="POST" style="flex: 1;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-outline" style="width: 100%;">
                <?= $topic->is_pinned ? 'Unpin' : 'Pin' ?>
            </button>
        </form>
        <form action="/alliance/forum/topic/<?= $topic->id ?>/lock" method="POST" style="flex: 1;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-outline" style="width: 100%;">
                <?= $topic->is_locked ? 'Unlock' : 'Lock' ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Posts -->
    <?php foreach ($posts as $post): ?>
    <div class="mobile-card" style="margin-bottom: 0.5rem;">
        <div class="mobile-card-header" style="display: flex; align-items: center; gap: 1rem;">
            <img src="<?= htmlspecialchars($post->author_profile_picture ?? '/img/default_avatar.png') ?>" style="width: 40px; height: 40px; border-radius: 50%;">
            <div>
                <strong style="color: #fff;"><?= htmlspecialchars($post->author_name) ?></strong>
                <div style="font-size: 0.8rem; color: var(--muted);"><?= $post->formatted_created_at ?></div>
            </div>
        </div>
        <div class="mobile-card-content" style="display: block; line-height: 1.6; color: #e0e0e0; white-space: pre-wrap; padding-top: 0;">
            <?= htmlspecialchars($post->content) ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Reply Form -->
    <?php if (!$topic->is_locked): ?>
    <div class="mobile-card" style="margin-top: 2rem;">
        <div class="mobile-card-header"><h3>Post a Reply</h3></div>
        <div class="mobile-card-content" style="display: block;">
            <form action="/alliance/forum/topic/<?= $topic->id ?>/reply" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <textarea name="content" class="mobile-input" style="width: 100%; height: 120px;" placeholder="Your message..."></textarea>
                </div>
                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">Post Reply</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <a href="/alliance/forum" class="btn btn-outline" style="margin-top: 2rem; text-align: center;">
        <i class="fas fa-arrow-left"></i> Back to Forum
    </a>
</div>
