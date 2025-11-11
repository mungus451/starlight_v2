<style>
    .spy-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .spy-container h1 {
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

<div class="spy-container">
    <h1>Espionage</h1>

    <div class="data-card">
        <h3>Spy Command</h3>
        <ul>
            <li><span>Current Spies:</span> <span><?= number_format($resources->spies) ?></span></li>
            <li><span>Attack Turns:</span> <span><?= number_format($stats->attack_turns) ?></span></li>
        </ul>
        <br>
        <a href="/spy/reports" class="btn-submit" style="text-align: center; display: block;">View Spy Reports</a>
    </div>

    <div class="data-card">
        <h3>Conduct Operation</h3>
        
        <?php
            $spies_to_send = $resources->spies;
            $credit_cost = $costs['cost_per_spy'] * $spies_to_send;
            $turn_cost = $costs['attack_turn_cost'];
        ?>

        <div class="op-summary">
            An operation will send <strong>all <?= number_format($spies_to_send) ?> spies</strong>, 
            cost <strong><?= number_format($credit_cost) ?> credits</strong>, 
            and use <strong><?= $turn_cost ?> attack turn(s)</strong>.
        </div>

        <form action="/spy/conduct" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="form-group">
                <label for="target_name">Target Character Name</label>
                <input type="text" name="target_name" id="target_name" placeholder="e.g., EmperorZurg" required>
            </div>
            
            <button type="submit" class="btn-submit" <?= $spies_to_send <= 0 ? 'disabled' : '' ?>>
                <?= $spies_to_send <= 0 ? 'You have no spies' : 'Launch Operation' ?>
            </button>
        </form>
    </div>
</div>