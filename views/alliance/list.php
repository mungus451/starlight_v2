<style>
    .alliance-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .alliance-container h1 {
        text-align: center;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .data-card h3 {
        color: #f9c74f;
        margin-top: 0;
        border-bottom: 1px solid #3a3a5a;
        padding-bottom: 0.5rem;
    }
    
    /* Alliance List Table */
    .alliance-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .alliance-table th, .alliance-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #3a3a5a;
    }
    .alliance-table th {
        color: #f9c74f;
    }
    .alliance-table tr:nth-child(even) {
        background: #2a2a4a;
    }
    .alliance-table a {
        color: #7683f5;
        font-weight: bold;
        text-decoration: none;
    }
    .alliance-table a:hover {
        text-decoration: underline;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .pagination a, .pagination span {
        color: #e0e0e0;
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
    }
    .pagination a:hover {
        background: #3a3a5a;
    }
    .pagination span {
        background: #5a67d8;
        color: white;
        border-color: #5a67d8;
        font-weight: bold;
    }
</style>

<div class="alliance-container">
    <h1>Alliances</h1>

    <div class="data-card">
        <h3>Find an Alliance</h3>
        <a href="/alliance/create" class="btn-submit" style="text-align: center; display: block; margin-bottom: 1.5rem;">
            Found a New Alliance
        </a>

        <table class="alliance-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Name</th>
                    <th>Net Worth</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alliances)): ?>
                    <tr><td colspan="3" style="text-align: center;">There are no alliances... yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($alliances as $alliance): ?>
                        <tr>
                            <td>[<?= htmlspecialchars($alliance->tag) ?>]</td>
                            <td>
                                <a href="/alliance/profile/<?= $alliance->id ?>">
                                    <?= htmlspecialchars($alliance->name) ?>
                                </a>
                            </td>
                            <td><?= number_format($alliance->net_worth) ?></td>
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