<style>
    .create-alliance-container {
        width: 100%;
        max-width: 800px;
        text-align: left;
    }
    .create-alliance-container h1 {
        text-align: center;
    }
    .data-card {
        background: #2a2a4a;
        border: 1px solid #3a3a5a;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .cost-summary {
        font-size: 1.1rem;
        color: #c0c0e0;
        background: #1e1e3f;
        padding: 1rem;
        border-radius: 5px;
        border: 1px solid #3a3a5a;
        margin-bottom: 1rem;
        text-align: center;
    }
    .cost-summary strong {
        color: #f9c74f;
        font-size: 1.2rem;
    }
</style>

<div class="create-alliance-container">
    <h1>Found a New Alliance</h1>

    <div class="data-card">
        <div class="cost-summary">
            Cost to found a new alliance: 
            <strong><?= number_format($cost) ?> Credits</strong>
        </div>

        <form action="/alliance/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
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