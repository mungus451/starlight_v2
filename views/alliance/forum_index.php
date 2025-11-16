<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceForumTopic[] $topics */
/* @var array $pagination */
/* @var int $allianceId */
/* @var bool $canManageForum */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --accent-blue: #7683f5;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .alliance-container-full {
        width: 100%;
        max-width: 1400px;
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .alliance-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- List Table (from alliance/list) --- */
    .forum-table-container {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        max-width: 1200px;
        margin: 0 auto 1.5rem auto;
    }
    
    .forum-controls {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .forum-controls .btn-submit {
        display: block;
        width: auto;
        text-align: center;
        margin: 0;
    }

    .forum-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    .forum-table th, .forum-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .forum-table th {
        color: var(--accent-2);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .forum-table tr:last-child td {
        border-bottom: none;
    }

    .topic-title-cell {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .topic-title-cell a {
        color: var(--text);
        text-decoration: none;
    }
    .topic-title-cell a:hover {
        text-decoration: underline;
        color: var(--accent);
    }
    .topic-author {
        font-size: 0.9rem;
        color: var(--muted);
        font-weight: 400;
    }
    .topic-icon {
        color: var(--accent-2);
        font-size: 1rem;
        margin-right: 0.5rem;
    }
    .topic-last-reply {
        font-size: 0.9rem;
        color: var(--muted);
        text-align: right;
    }
    .topic-last-reply strong {
        color: var(--text);
    }
    
    /* --- Pagination (from alliance/list) --- */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .pagination a, .pagination span {
        color: var(--muted);
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        border: 1px solid var(--border);
        background: var(--card);
    }
    .pagination a:hover {
        background: rgba(255,255,255, 0.03);
        color: #fff;
        border-color: var(--accent);
    }
    .pagination span {
        background: var(--accent);
        color: #02030a;
        border-color: var(--accent);
        font-weight: bold;
    }
</style>

<div class="alliance-container-full">
    <h1>Alliance Forum</h1>

    <div class="forum-table-container">
        
        <div class="forum-controls">
            <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit" style="background: var(--accent-blue);">
                &laquo; Back to Alliance Profile
            </a>
            <a href="/alliance/forum/topic/create" class="btn-submit" style="background: var(--accent); color: #02030a;">
                Create New Topic
            </a>
        </div>

        <table class="forum-table">
            <thead>
                <tr>
                    <th>Topic</th>
                    <th>Replies</th>
                    <th style="text-align: right;">Last Post</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topics)): ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 2rem;">There are no topics in the forum... yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <tr style="<?= $topic->is_pinned ? 'background: rgba(249, 199, 79, 0.05);' : '' ?>">
                            <td class="topic-title-cell">
                                <?php if ($topic->is_pinned): ?><span class="topic-icon" title="Pinned">📌</span><?php endif; ?>
                                <?php if ($topic->is_locked): ?><span class="topic-icon" title="Locked">🔒</span><?php endif; ?>
                                <a href="/alliance/forum/topic/<?= $topic->id ?>">
                                    <?= htmlspecialchars($topic->title) ?>
                                </a>
                                <br>
                                <span class="topic-author">by <?= htmlspecialchars($topic->author_name ?? 'N/A') ?></span>
                            </td>
                            <td><?= number_format($topic->post_count - 1) // Subtract 1 for the original post ?></td>
                            <td class="topic-last-reply">
                                <strong><?= htmlspecialchars($topic->last_reply_user_name ?? 'N/A') ?></strong>
                                <br>
                                <?= (new DateTime($topic->last_reply_at))->format('M d, Y H:i') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="/alliance/forum/page/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="/alliance/forum/page/<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="/alliance/forum/page/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>