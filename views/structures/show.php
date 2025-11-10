<style>
    .structures-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .structures-container h1 {
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
    
    /* Structure List Styles */
    .structure-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .structure-item {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: #1e1e3f; /* Darker than card */
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
    }
    @media (min-width: 768px) {
        .structure-item {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }
    .structure-item-info {
        font-size: 1.1rem;
        font-weight: bold;
    }
    .structure-item-info span {
        color: #c0c0e0;
        font-weight: normal;
    }
    .structure-item form {
        margin: 0; /* Override default form margin */
    }
    .structure-item .btn-submit {
        margin-top: 0; /* Override default button margin */
        width: 100%;
    }
    @media (min-width: 768px) {
        .structure-item .btn-submit {
            width: auto;
        }
    }
</style>

<div class="structures-container">
    <h1>Structures</h1>

    <div class="data-card">
        <h3>Finances</h3>
        <ul>
            <li style="font-size: 1.2rem;">
                <span>Credits on Hand:</span> 
                <span><?= number_format($resources->credits) ?></span>
            </li>
        </ul>
    </div>

    <div class="data-card">
        <h3>Upgrades</h3>
        <ul class="structure-list">
            <?php 
            // We loop over the $structureFormulas (from config) to control the display order
            foreach ($structureFormulas as $key => $details):
                
                // e.g., 'fortification_level'
                $columnName = $key . '_level'; 
                
                // Get the current level from the UserStructure entity
                $currentLevel = $structures->{$columnName} ?? 0;
                
                // Get the pre-calculated cost for the *next* level
                $upgradeCost = $costs[$key] ?? 0;
                
                // Get the display name from the config
                $displayName = $details['name'] ?? 'Unknown Structure';
                
            ?>
                <li class="structure-item">
                    <div class="structure-item-info">
                        <?= htmlspecialchars($displayName) ?> 
                        <span>(Level <?= $currentLevel ?>)</span>
                    </div>
                    
                    <form action="/structures/upgrade" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="structure_key" value="<?= htmlspecialchars($key) ?>">
                        
                        <button type="submit" class="btn-submit">
                            Upgrade for <?= number_format($upgradeCost) ?> C
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>