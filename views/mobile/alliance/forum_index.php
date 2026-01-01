<?php
// --- Mobile Forum Index View ---
/* @var array $topics */
/* @var array $pagination */
/* @var object $permissions */
/* @var string $csrf_token */

$page = $pagination['currentPage'];
$totalPages = $pagination['totalPages'];
$prevPage = $page > 1 ? $page - 1 : null;
$nextPage = $page < $totalPages ? $page + 1 : null;
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Forum</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Alliance communications.</p>
    </div>

    <?php if ($permissions->can_create_topic): ?>
        <a href="/alliance/forum/topic/create" class="btn btn-accent" style="margin-bottom: 1.5rem; text-align: center;">
            <i class="fas fa-plus-circle"></i> New Topic
        </a>
    <?php endif; ?>

    <?php if (empty($topics)): ?>
        <p class="text-center text-muted" style="padding: 2rem;">No topics have been created yet.</p>
    <?php else: ?>
        <?php foreach ($topics as $topic): ?>
        <a href="/alliance/forum/topic/<?= $topic->id ?>" class="mobile-card" style="display: block; text-decoration: none; border-left: 4px solid <?= $topic->is_pinned ? 'var(--mobile-accent-yellow)' : 'transparent' ?>; margin-bottom: 0.5rem;">
            <div class="mobile-card-content" style="display: block; padding: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #fff; font-size: 1.1rem;">
                    <?php if ($topic->is_locked): ?><i class="fas fa-lock" style="font-size: 0.8em; color: var(--muted);"></i><?php endif; ?>
                    <?php if ($topic->is_pinned): ?><i class="fas fa-thumbtack" style="font-size: 0.8em; color: var(--mobile-accent-yellow);"></i><?php endif; ?>
                    <?= htmlspecialchars($topic->title) ?>
                </h4>
                <div style="font-size: 0.8rem; color: var(--muted); display: flex; justify-content: space-between;">
                    <span>By: <?= htmlspecialchars($topic->author_name) ?></span>
                    <span><i class="fas fa-comment"></i> <?= number_format($topic->post_count - 1) ?></span>
                </div>
                <div style="font-size: 0.8rem; color: var(--mobile-text-secondary); text-align: right; margin-top: 0.5rem;">
                    Last post by <?= htmlspecialchars($topic->last_reply_user_name) ?> at <?= $topic->formatted_last_reply_at ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mobile-tabs" style="justify-content: space-between; margin-top: 1rem;">
        <?php if ($prevPage): ?>
            <a href="/alliance/forum?page=<?= $prevPage ?>" class="btn">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;"><i class="fas fa-chevron-left"></i> Prev</span>
        <?php endif; ?>

        <span style="align-self: center; font-family: 'Orbitron', sans-serif; color: var(--mobile-text-secondary);">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <?php if ($nextPage): ?>
            <a href="/alliance/forum?page=<?= $nextPage ?>" class="btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="btn disabled" style="opacity: 0.5; cursor: default;">Next <i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
