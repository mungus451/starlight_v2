<?php
/**
 * @var \App\Models\Entities\UserStats $stats
 */
?>
<div id="military" class="tab-content">
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-crosshairs"></i> Military Overview</h3></div>
        <div class="mobile-card-content">
            <ul class="mobile-stats-list">
                <li><span>Battles Won</span> <strong><?= number_format($stats->battles_won) ?></strong></li>
                <li><span>Battles Lost</span> <strong><?= number_format($stats->battles_lost) ?></strong></li>
                <li><span>Spy Successes</span> <strong><?= number_format($stats->spy_successes) ?></strong></li>
                <li><span>Spy Failures</span> <strong><?= number_format($stats->spy_failures) ?></strong></li>
            </ul>
        </div>
    </div>
</div>
