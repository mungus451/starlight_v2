<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceForumTopic $topic */
/* @var \App\Models\Entities\AllianceForumPost[] $posts */
/* @var int $allianceId */
/* @var bool $canManageForum */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-red: #e53e3e;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .alliance-container-full {
        width: 100%;
        max-width: 1200px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .alliance-container-full h1 {
        text-align: left;
        margin-bottom: 0.5rem;
        font-size: clamp(1.8rem, 3vw, 2.2rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }
    
    .topic-subtitle {
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: 0;
        margin-bottom: 1.5rem;
    }

    /* --- Controls --- */
    .topic-controls {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    .topic-controls .btn-submit {
        display: block;
        width: auto;
        text-align: center;
        margin: 0;
    }
    .mod-controls {
        display: flex;
        gap: 1rem;
    }

    /* --- Post List --- */
    .post-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .post-item {
        display: grid;
        grid-template-columns: 150px 1fr; /* Author sidebar | Post content */
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    
    .post-author {
        padding: 1rem;
        background: rgba(13, 15, 27, 0.7);
        border-right: 1px solid var(--border);
        text-align: center;
    }
    
    .post-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--accent);
        margin: 0 auto 0.75rem auto;
    }
    .post-avatar-svg {
        padding: 1.25rem;
        background: #1e1e3f;
        color: var(--muted);
    }
    .post-author-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text);
    }
    
    .post-content {
        padding: 1rem 1.5rem;
        display: flex;
        flex-direction: column;
    }
    .post-date {
        font-size: 0.85rem;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }
    .post-body {
        font-size: 1rem;
        line-height: 1.6;
        color: var(--text);
        white-space: pre-wrap; /* Respects newlines */
        flex-grow: 1;
    }
    
    /* --- Reply Card --- */
    .reply-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        margin-top: 1.5rem;
    }
    .reply-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
    }
    .reply-card textarea {
        padding: 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        background: #2a2a4a;
        color: #e0e0e0;
        font-size: 1rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        min-height: 150px;
        width: 100%;
        box-sizing: border-box; /* Important */
    }
    .reply-card .btn-submit {
        width: 100%;
        margin-top: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .post-item {
            grid-template-columns: 1fr; /* Stack author on top of content */
        }
        .post-author {
            border-right: none;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
        }
        .post-avatar, .post-avatar-svg {
            width: 50px;
            height: 50px;
            margin: 0;
            padding: 0.5rem;
        }
    }

</style>

<div class="alliance-container-full">
    <h1><?= htmlspecialchars($topic->title) ?></h1>
    <p class="topic-subtitle">
        Topic started by <?= htmlspecialchars($topic->author_name ?? 'N/A') ?> on <?= (new DateTime($topic->created_at))->format('M d, Y') ?>
    </p>

    <div class="topic-controls">
        <a href="/alliance/forum" class="btn-submit" style="background: var(--accent-blue);">
            &laquo; Back to Forum Index
        </a>
        
        <?php if ($canManageForum): ?>
            <div class="mod-controls">
                <form action="/alliance/forum/topic/<?= $topic->id ?>/pin" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit">
                        <?= $topic->is_pinned ? 'Unpin Topic' : 'Pin Topic' ?>
                    </button>
                </form>
                <form action="/alliance/forum/topic/<?= $topic->id ?>/lock" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit" style="background: var(--accent-red);">
                        <?= $topic->is_locked ? 'Unlock Topic' : 'Lock Topic' ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="post-list">
        <?php foreach ($posts as $post): ?>
            <div class="post-item">
                <div class="post-author">
                    <?php if ($post->author_avatar): ?>
                        <img src="/serve/avatar/<?= htmlspecialchars($post->author_avatar) ?>" alt="Avatar" class="post-avatar">
                    <?php else: ?>
                        <svg class="post-avatar post-avatar-svg" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                    
                    <div>
                        <span class="post-author-name"><?= htmlspecialchars($post->author_name ?? 'N/A') ?></span>
                        </div>
                </div>
                <div class="post-content">
                    <div class="post-date">
                        Posted on <?= (new DateTime($post->created_at))->format('M d, Y \a\t H:i') ?>
                    </div>
                    <div class="post-body">
                        <?= htmlspecialchars($post->content) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="reply-card">
        <?php if ($topic->is_locked): ?>
            <h4>Topic Locked</h4>
            <p style="color: var(--muted); text-align: center; margin: 1rem 0;">
                This topic has been locked by a moderator. No new replies can be posted.
            </p>
        <?php else: ?>
            <h4>Post a Reply</h4>
            <form action="/alliance/forum/topic/<?= $topic->id ?>/reply" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <textarea name="content" placeholder="Type your reply here..."></textarea>
                </div>
                <button type="submit" class="btn-submit">Post Reply</button>
            </form>
        <?php endif; ?>
    </div>
</div>