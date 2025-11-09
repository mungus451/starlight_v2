<style>
    .training-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .training-container h1 {
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
        grid-template-columns: 1fr 1fr; /* 2-column layout */
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
    .form-group select {
        padding: 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        background: #2a2a4a;
        color: #e0e0e0;
        font-size: 1rem;
    }
</style>

<div class="training-container">
    <h1>Training</h1>

    <div class="data-card">
        <h3>Current Forces</h3>
        <ul>
            <li><span>Credits:</span> <span><?= number_format($resources->credits) ?></span></li>
            <li><span>Citizens:</span> <span><?= number_format($resources->untrained_citizens) ?></span></li>
            <li><span>Workers:</span> <span><?= number_format($resources->workers) ?></span></li>
            <li><span>Soldiers:</span> <span><?= number_format($resources->soldiers) ?></span></li>
            <li><span>Guards:</span> <span><?= number_format($resources->guards) ?></span></li>
            <li><span>Spies:</span> <span><?= number_format($resources->spies) ?></span></li>
            <li><span>Sentries:</span> <span><?= number_format($resources->sentries) ?></span></li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Train Units</h3>
        <form action="/training/train" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="form-group">
                <label for="unit_type">Unit to Train</label>
                <select name="unit_type" id="unit_type">
                    <?php foreach ($costs as $unit => $cost): ?>
                        <option value="<?= htmlspecialchars($unit) ?>">
                            <?= htmlspecialchars(ucfirst($unit)) ?> 
                            (Cost: <?= number_format($cost['credits']) ?> C, <?= $cost['citizens'] ?> Cit)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount to Train</label>
                <input type="number" name="amount" id="amount" min="1" placeholder="e.g., 100" required>
            </div>
            
            <button type="submit" class="btn-submit">Train</button>
        </form>
    </div>
</div>