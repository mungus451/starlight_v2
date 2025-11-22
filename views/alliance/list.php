<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Alliance[] $alliances */
/* @var array $pagination */
/* @var int $perPage */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
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

    /* --- List Table --- */
    .alliance-table-container {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        max-width: 1000px;
        margin: 0 auto 1.5rem auto;
    }
    
    .alliance-table-container .btn-submit {
        display: block;
        width: 100%;
        max-width: 300px;
        margin: 0 auto 1.5rem auto;
        text-align: center;
        text-decoration: none;
        padding: 0.8rem;
    }

    .alliance-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 100%;
    }
    .alliance-table th, .alliance-table td {
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .alliance-table th {
        color: var(--accent-2);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .alliance-table tr:last-child td {
        border-bottom: none;
    }

    .alliance-name {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .alliance-name a {
        color: var(--text);
        text-decoration: none;
    }
    .alliance-name a:hover {
        text-decoration: underline;
        color: var(--accent);
    }
    .alliance-tag {
        font-weight: 700;
        color: var(--accent-2);
        font-size: 1.1rem;
    }

    /* --- MOBILE CARD TRANSFORMATION --- */
    @media (max-width: 768px) {
        /* Force table elements to display as block/flex */
        .alliance-table, .alliance-table thead, .alliance-table tbody, .alliance-table th, .alliance-table td, .alliance-table tr { 
            display: block; 
        }
        
        /* Hide headers */
        .alliance-table thead tr { 
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        
        /* Card Style for Rows */
        .alliance-table tr { 
            margin-bottom: 1rem; 
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(13, 15, 27, 0.4);
            padding: 1rem;
        }
        
        /* Flex Cells */
        .alliance-table td { 
            border: none;
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            position: relative;
            padding-left: 0;
            padding-right: 0;
            text-align: right;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
        }
        
        .alliance-table td:last-child {
            border-bottom: 0;
        }

        /* Labels via pseudo-element */
        .alliance-table td::before { 
            content: attr(data-label);
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            font-size: 0.75rem;
            text-align: left;
        }
    }

    /* --- Pagination --- */
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
    <h1>Alliances</h1>

    <div class="alliance-table-container">
        
        <a href="/alliance/create" class="btn-submit" style="background: var(--accent); color: #02030a;">
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
                    <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 2rem;">There are no alliances... yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($alliances as $alliance): ?>
                        <tr>
                            <td data-label="Tag">
                                <span class="alliance-tag">[<?= htmlspecialchars($alliance->tag) ?>]</span>
                            </td>
                            <td data-label="Name">
                                <span class="alliance-name">
                                    <a href="/alliance/profile/<?= $alliance->id ?>">
                                        <?= htmlspecialchars($alliance->name) ?>
                                    </a>
                                </span>
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