<?php
// --- Helper variables from the controller ---
/* @var int $cost */
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

    /* --- Form Card (from Bank) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        max-width: 800px; /* Constrain form width */
        margin: 0 auto; /* Center the card */
    }
    .item-card h4 {
        color: #fff;
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    
    .item-card .btn-submit {
        width: 100%;
        margin-top: 0.5rem;
    }
    
    .cost-summary {
        font-size: 1.1rem;
        color: var(--muted);
        background: rgba(13, 15, 27, 0.7);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        margin-bottom: 1.5rem;
        text-align: center;
    }
    .cost-summary strong {
        color: var(--accent-2);
        font-size: 1.2rem;
    }
</style>

<div class="alliance-container-full">
    <h1>Found a New Alliance</h1>

    <div class="item-card">
        <form action="/alliance/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <h4>Alliance Details</h4>

            <div class="cost-summary">
                Cost to found a new alliance: 
                <strong><?= number_format($cost) ?> Credits</strong>
            </div>
            
            <div class="form-group">
                <label for="alliance_name">Alliance Name (3-100 characters)</label>
                <input type="text" name="alliance_name" id="alliance_name" maxlength="100" minlength="3" required>
            </div>
            
            <div class="form-group">
                <label for="alliance_tag">Alliance Tag (3-5 characters)</label>
                <input type="text" name="alliance_tag" id="alliance_tag" maxlength="5" minlength="3" required>
            </div>

            <button type="submit" class="btn-submit">Found Alliance</button>
        </form>
    </div>
</div>