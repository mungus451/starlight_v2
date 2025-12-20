<div class="container">
    <div style="max-width: 800px; margin: 2rem auto;">
        <h1 style="text-align: center; margin-bottom: 1rem;">Select Your Race</h1>
        <p style="text-align: center; color: #aaa; margin-bottom: 2rem;">
            Choose your character's race. This choice is permanent and will affect your journey through the galaxy.
        </p>

        <form action="/race/select" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

            <div class="race-grid">
                <?php foreach ($races as $raceKey => $raceData): ?>
                    <label class="race-card">
                        <input type="radio" name="race" value="<?= htmlspecialchars($raceKey) ?>" required>
                        <div class="race-card-content">
                            <h3><?= htmlspecialchars($raceData['name']) ?></h3>
                            <p><?= htmlspecialchars($raceData['description']) ?></p>
                            <?php if (!empty($raceData['bonuses'])): ?>
                                <div class="race-bonuses">
                                    <strong>Bonuses:</strong>
                                    <ul>
                                        <?php foreach ($raceData['bonuses'] as $bonus): ?>
                                            <li><?= htmlspecialchars($bonus) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn-submit">Confirm Selection</button>
            </div>
        </form>
    </div>
</div>

<style>
.race-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.race-card {
    cursor: pointer;
    display: block;
    position: relative;
}

.race-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.race-card-content {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border: 2px solid #2a2a3e;
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    height: 100%;
}

.race-card:hover .race-card-content {
    border-color: var(--accent-color, #00d4ff);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
    transform: translateY(-4px);
}

.race-card input[type="radio"]:checked + .race-card-content {
    border-color: var(--accent-color, #00d4ff);
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
    background: linear-gradient(135deg, #1a2a3e 0%, #16284e 100%);
}

.race-card h3 {
    color: var(--accent-color, #00d4ff);
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
}

.race-card p {
    color: #ccc;
    line-height: 1.6;
    margin: 0;
}

.race-bonuses {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #2a2a3e;
}

.race-bonuses strong {
    color: var(--accent-color, #00d4ff);
}

.race-bonuses ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

.race-bonuses li {
    color: #aaa;
    margin: 0.25rem 0;
}

.btn-submit {
    background: linear-gradient(135deg, var(--accent-color, #00d4ff) 0%, #0095ff 100%);
    color: white;
    border: none;
    padding: 1rem 3rem;
    font-size: 1.1rem;
    font-weight: bold;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.4);
}
</style>
