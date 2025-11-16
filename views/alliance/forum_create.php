<?php
// --- Helper variables from the controller ---
/* @var int $allianceId */
?>

<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --text: #eff1ff;
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

    /* --- Form Card (from alliance/create) --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        max-width: 900px; /* Constrain form width */
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
    
    /* Need text area styles from settings */
    .form-group textarea {
        padding: 0.75rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        background: #2a2a4a;
        color: #e0e0e0;
        font-size: 1rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        min-height: 200px; /* Taller for a forum post */
    }
    
    .form-controls {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    .form-controls .btn-submit {
        margin-top: 0;
        width: auto;
        flex-grow: 1;
    }
</style>

<div class="alliance-container-full">
    <h1>Create New Topic</h1>

    <div class="item-card">
        <form action="/alliance/forum/topic/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <h4>New Topic Details</h4>
            
            <div class="form-group">
                <label for="title">Topic Title (max 255 characters)</label>
                <input type="text" name="title" id="title" maxlength="255" required>
            </div>
            
            <div class="form-group">
                <label for="content">Post Content (max 10,000 characters)</label>
                <textarea name="content" id="content" maxlength="10000" required></textarea>
            </div>

            <div class="form-controls">
                <a href="/alliance/forum" class="btn-submit" style="background: var(--accent-red); text-align: center;">Cancel</a>
                <button type="submit" class="btn-submit">Post Topic</button>
            </div>
        </form>
    </div>
</div>