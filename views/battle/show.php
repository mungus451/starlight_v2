<style>
    .battle-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .battle-container h1 {
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
    .data-card ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    .data-card li {
        font-size: 1.1rem;
        color: #e0e0e0;
        padding: 0.25rem 0;
        display: flex;
        justify-content: space-between;
    }
    .data-card li span:first-child {
        font-weight: bold;
        color: #c0c0e0;
    }
    
    /* Target List Table */
    .target-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .target-table th, .target-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #3a3a5a;
    }
    .target-table th {
        color: #f9c74f;
    }
    .target-table tr:nth-child(even) {
        background: #2a2a4a;
    }
    .btn-select {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        background: #7683f5;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
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
    
    /* NEW "All-In" Summary */
    .op-summary {
        font-size: 1rem;
        color: #c0c0e0;
        background: #1e1e3f;
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        margin-bottom: 1rem;
    }
    .op-summary strong {
        color: #f9c74f;
    }
</style>

<div class="battle-container">
    <h1>Battle</h1>

    <div class="data-card">
        <h3>Launch Attack</h3>
        <ul>
            <li><span>Soldiers:</span> <span><?= number_format($attackerResources->soldiers) ?></span></li>
            <li><span>Attack Turns:</span> <span><?= number_format($attackerStats->attack_turns) ?></span></li>
        </ul>
        <br>
        <a href="/battle/reports" class="btn-submit" style="text-align: center; display: block; margin-bottom: 1rem;">View Battle Reports</a>

        <div class="op-summary">
            An operation will send <strong>all <?= number_format($attackerResources->soldiers) ?> soldiers</strong> 
            and cost <strong><?= $costs['attack_turn_cost'] ?> attack turn(s)</strong>.
        </div>

        <form action="/battle/attack" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="form-group">
                <label for="target_name">Target Character Name</label>
                <input type="text" name="target_name" id="target_name" placeholder="Select from list below" required>
            </div>
            
            <div class="form-group">
                <label for="attack_type">Attack Type</label>
                <select name="attack_type" id="attack_type">
                    <option value="plunder">Plunder (Steal Credits & Net Worth)</option>
                    </select>
            </div>

            <button type="submit" class="btn-submit" <?= $attackerResources->soldiers <= 0 ? 'disabled' : '' ?>>
                <?= $attackerResources->soldiers <= 0 ? 'You have no soldiers' : 'Launch Attack' ?>
            </button>
        </form>
    </div>

    <div class="data-card">
        <h3>Target List (Leaderboard)</h3>
        <table class="target-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Net Worth</th>
                    <th>Level</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($players)): ?>
                    <tr><td colspan="5" style="text-align: center;">No other players found.</td></tr>
                <?php else: ?>
                    <?php foreach ($players as $index => $player): ?>
                        <tr>
                            <td><?= ($pagination['currentPage'] - 1) * $perPage + $index + 1 ?></td>
                            <td><?= htmlspecialchars($player['character_name']) ?></td>
                            <td><?= number_format($player['net_worth']) ?></td>
                            <td><?= $player['level'] ?></td>
                            <td>
                                <button class="btn-select" data-target-name="<?= htmlspecialchars($player['character_name']) ?>">
                                    Select
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($pagination['totalPages'] > 1): ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="/battle/page/<?= $pagination['currentPage'] - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <?php if ($i == $pagination['currentPage']): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="/battle/page/<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="/battle/page/<?= $pagination['currentPage'] + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const targetNameInput = document.getElementById('target_name');
        if (targetNameInput) {
            document.querySelectorAll('.btn-select').forEach(button => {
                button.addEventListener('click', function() {
                    const targetName = this.getAttribute('data-target-name');
                    targetNameInput.value = targetName;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    targetNameInput.focus();
                });
            });
        }
    });
</script>