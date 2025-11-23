<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceForumTopic $topic */
/* @var \App\Models\Entities\AllianceForumPost[] $posts */
/* @var int $allianceId */
/* @var bool $canManageForum */
?>

<div class="container-full">
    <!-- Topic Header -->
    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
        <h1><?= htmlspecialchars($topic->title) ?></h1>
        <p style="text-align: center; color: var(--muted); margin-top: -1.5rem;">
            Started by <?= htmlspecialchars($topic->author_name ?? 'N/A') ?> 
            on <?= (new DateTime($topic->created_at))->format('M d, Y') ?>
        </p>
    </div>

    <!-- Controls -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <a href="/alliance/forum" class="btn-submit btn-accent" style="margin: 0;">
            &laquo; Back to Forum
        </a>
        
        <?php if ($canManageForum): ?>
            <div style="display: flex; gap: 0.75rem;">
                <form action="/alliance/forum/topic/<?= $topic->id ?>/pin" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit" style="margin: 0;">
                        <?= $topic->is_pinned ? 'Unpin' : 'Pin' ?>
                    </button>
                </form>
                <form action="/alliance/forum/topic/<?= $topic->id ?>/lock" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-reject" style="margin: 0;">
                        <?= $topic->is_locked ? 'Unlock' : 'Lock' ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Posts Loop -->
    <div class="post-list">
        <?php foreach ($posts as $post): ?>
            <div class="forum-post">
                <!-- Author Sidebar -->
                <div class="forum-author">
                    <?php if ($post->author_avatar): ?>
                        <img src="/serve/avatar/<?= htmlspecialchars($post->author_avatar) ?>" alt="Avatar" class="player-avatar">
                    <?php else: ?>
                        <svg class="player-avatar player-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                    
                    <div>
                        <div class="name"><?= htmlspecialchars($post->author_name ?? 'N/A') ?></div>
                        <div class="role">Member</div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="forum-content">
                    <div class="forum-meta">
                        <span>Posted on <?= (new DateTime($post->created_at))->format('M d, Y \a\t H:i') ?></span>
                        <span>#<?= $post->id ?></span>
                    </div>
                    <div class="forum-body"><?= htmlspecialchars($post->content) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Area -->
    <div class="reply-area">
        <?php if ($topic->is_locked): ?>
            <div style="text-align: center; color: var(--accent-red); font-weight: 600;">
                <i class="fas fa-lock"></i> This topic is locked. No further replies allowed.
            </div>
        <?php else: ?>
            <h4 style="color: #fff; margin-top: 0;">Post a Reply</h4>
            <form action="/alliance/forum/topic/<?= $topic->id ?>/reply" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <textarea name="content" placeholder="Type your reply here..." required style="min-height: 150px;"></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="submit" class="btn-submit">Post Reply</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>