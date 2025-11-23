<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Alliance[] $alliances */
/* @var array $pagination */
/* @var int $perPage */
?>

<div class="container-full">
    <h1>Alliances</h1>

    <div class="data-table-container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
            <a href="/alliance/create" class="btn-submit btn-accent">
                Found a New Alliance
            </a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Name</th>
                    <th>Net Worth</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alliances)): ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 2rem;">There are no alliances... yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($alliances as $alliance): ?>
                        <tr>
                            <td data-label="Tag">
                                <span style="font-weight: 700; color: var(--accent-2);">
                                    [<?= htmlspecialchars($alliance->tag) ?>]
                                </span>
                            </td>
                            <td data-label="Name">
                                <a href="/alliance/profile/<?= $alliance->id ?>">
                                    <?= htmlspecialchars($alliance->name) ?>
                                </a>
                            </td>
                            <td data-label="Net Worth"><?= number_format($alliance->net_worth) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="/alliance/list/page/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="/alliance/list/page/<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="/alliance/list/page/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>