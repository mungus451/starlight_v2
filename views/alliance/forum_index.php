<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\AllianceForumTopic[] $topics */
/* @var array $pagination */
/* @var int $allianceId */
/* @var bool $canManageForum */
?>

<div class="container-full">
    <h1>Alliance Forum</h1>

    <div class="data-table-container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit btn-accent" style="margin: 0;">
                &laquo; Back to Alliance
            </a>
            <a href="/alliance/forum/topic/create" class="btn-submit" style="margin: 0;">
                Create New Topic
            </a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Topic</th>
                    <th style="width: 15%;">Replies</th>
                    <th style="text-align: right; width: 25%;">Last Post</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topics)): ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 2rem;">There are no topics in the forum... yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <tr class="<?= $topic->is_pinned ? 'topic-pinned' : '' ?>">
                            <td data-label="Topic" class="topic-title-cell">
                                <?php if ($topic->is_pinned): ?><span class="topic-icon" title="Pinned">📌</span><?php endif; ?>
                                <?php if ($topic->is_locked): ?><span class="topic-icon" title="Locked">🔒</span><?php endif; ?>
                                <a href="/alliance/forum/topic/<?= $topic->id ?>">
                                    <?= htmlspecialchars($topic->title) ?>
                                </a>
                                <span class="topic-author">by <?= htmlspecialchars($topic->author_name ?? 'N/A') ?></span>
                            </td>
                            <td data-label="Replies">
                                <?= number_format($topic->post_count - 1) ?>
                            </td>
                            <td data-label="Last Post" class="topic-last-reply">
                                <strong><?= htmlspecialchars($topic->last_reply_user_name ?? 'N/A') ?></strong>
                                <br>
                                <?= $topic->formatted_last_reply_at ?>
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