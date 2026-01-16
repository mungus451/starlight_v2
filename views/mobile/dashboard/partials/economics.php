<?php
/**
 * @var \App\Models\Entities\UserResource $resources
 * @var array $incomeBreakdown
 */
?>
<div id="economics" class="tab-content">
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-coins"></i> Resource Overview</h3></div>
        <div class="mobile-card-content">
            <ul class="mobile-stats-list">
                <li><span>Credits</span> <strong><?= number_format($resources->credits) ?></strong></li>
                <li><span>Naquadah Crystals</span> <strong><?= number_format($resources->naquadah_crystals) ?></strong></li>
                <li><span>Dark Matter</span> <strong><?= number_format($resources->dark_matter) ?></strong></li>
            </ul>
        </div>
    </div>
    <div class="mobile-card">
        <div class="mobile-card-header"><h3><i class="fas fa-chart-line"></i> Income Breakdown</h3></div>
        <div class="mobile-card-content">
            <ul class="mobile-stats-list">
                <li>
                    <span>Credit Income</span> 
                    <strong class="<?= $incomeBreakdown['total_credit_income'] >= 0 ? 'value-green' : 'value-red' ?>">
                        <?= number_format($incomeBreakdown['total_credit_income']) ?>
                    </strong>
                </li>
                <li><span>Research Income</span> <strong><?= number_format($incomeBreakdown['research_data_income']) ?></strong></li>
                <li><span>Dark Matter Income</span> <strong><?= number_format($incomeBreakdown['dark_matter_income']) ?></strong></li>
            </ul>
        </div>
    </div>
</div>
