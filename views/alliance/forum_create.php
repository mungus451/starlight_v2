<?php
// --- Helper variables from the controller ---
/* @var int $allianceId */
?>

<div class="container-full">
    <h1>Create New Topic</h1>

    <!-- Centered Card -->
    <div class="item-grid" style="grid-template-columns: 1fr; max-width: 800px; margin: 0 auto;">
        <div class="item-card">
            <form action="/alliance/forum/topic/create" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                
                <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                    New Topic Details
                </h4>
                
                <div class="form-group">
                    <label for="title">Topic Title</label>
                    <input type="text" name="title" id="title" maxlength="255" placeholder="Enter a descriptive title..." required>
                </div>
                
                <div class="form-group">
                    <label for="content">Post Content</label>
                    <textarea name="content" id="content" maxlength="10000" placeholder="Write your message..." style="min-height: 200px;" required></textarea>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <a href="/alliance/forum" class="btn-submit btn-reject" style="text-align: center; flex-grow: 0;">Cancel</a>
                    <button type="submit" class="btn-submit" style="flex-grow: 1;">Post Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>