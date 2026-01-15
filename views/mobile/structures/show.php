<?php
// --- Mobile Structures View (Tab Container) ---
/* @var array $groupedStructures */
/* @var \App\Models\Entities\UserResource $resources */
/* @var string $csrf_token */

if (!function_exists('slugify')) {
    function slugify($text) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }
}

$categories = array_keys($groupedStructures);
$firstCategorySlug = slugify($categories[0] ?? 'economy');
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem 1rem 1.5rem 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">Structure Network</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;">Upgrade and expand your empire's core facilities.</p>
    </div>

    <!-- Resource Overview Card -->
    <div class="mobile-card resource-overview-card">
        <div class="mobile-card-header">
            <h3><i class="fas fa-gem"></i> Current Resources</h3>
        </div>
        <div class="mobile-card-content" style="display: block;">
            <ul class="mobile-stats-list">
                <li><span><i class="fas fa-coins"></i> Credits</span> <strong><?= number_format($resources->credits) ?></strong></li>
                <li><span><i class="fas fa-gem"></i> Naquadah Crystals</span> <strong><?= number_format($resources->naquadah_crystals) ?></strong></li>
                <li><span><i class="fas fa-atom"></i> Dark Matter</span> <strong><?= number_format($resources->dark_matter) ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-nav mb-3">
        <?php foreach ($categories as $category): ?>
            <a class="tab-link" data-tab="<?= slugify($category) ?>"><?= htmlspecialchars($category) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Dynamic Content Area -->
    <div class="tab-content-container">
        <?php foreach ($categories as $category):
            $slug = slugify($category);
            $partialPath = __DIR__ . '/partials/' . $slug . '.php';
            if (file_exists($partialPath)) {
                require $partialPath;
            }
        endforeach; ?>
    </div>
</div>

<script src="/js/utils.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        StarlightUtils.initTabs({
            defaultTab: '<?= $firstCategorySlug ?>'
        });
    });
</script>
