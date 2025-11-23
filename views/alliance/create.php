<?php
// --- Helper variables from the controller ---
/* @var int $cost */
?>

<div class="container-full">
    <h1>Found a New Alliance</h1>

    <!-- Centered layout for simple forms -->
    <div class="item-grid" style="grid-template-columns: 1fr; max-width: 800px;">
        <div class="item-card">
            <form action="/alliance/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <h4>Alliance Details</h4>

                <div class="info-box">
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
</div>