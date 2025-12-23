<?php
// --- Partial View: Economics ---
?>
<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-money-bill-wave"></i> Income</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-plus-circle value-green"></i> Credit Income / Turn</span> <strong class="value-green">+ <?= number_format($incomeBreakdown['total_credit_income']) ?></strong></li>
            <li><span><i class="fas fa-piggy-bank value-green"></i> Bank Interest / Turn</span> <strong class="value-green">+ <?= number_format($incomeBreakdown['interest']) ?></span></strong>
            <li><span><i class="fas fa-users value-green"></i> Citizen Growth / Turn</span> <strong class="value-green">+ <?= number_format($incomeBreakdown['total_citizens']) ?></span></strong>
            <li><span><i class="fas fa-flask value-green"></i> Research / Turn</span> <strong class="value-green">+ <?= number_format($incomeBreakdown['research_data_income']) ?></span></strong>
            <li><span><i class="fas fa-atom value-green"></i> Dark Matter / Turn</span> <strong class="value-green">+ <?= number_format($incomeBreakdown['dark_matter_income']) ?></span></strong>
        </ul>
    </div>
</div>

<div class="mobile-card">
    <div class="mobile-card-header">
        <h3><i class="fas fa-wallet"></i> Resources on Hand</h3>
    </div>
    <div class="mobile-card-content" style="display: block;">
        <ul class="mobile-stats-list">
            <li><span><i class="fas fa-credit-card"></i> Credits</span> <strong><?= number_format($resources->credits) ?></strong></li>
            <li><span><i class="fas fa-university"></i> Banked Credits</span> <strong><?= number_format($resources->banked_credits) ?></strong></li>
            <li><span><i class="fas fa-database"></i> Research Data</span> <strong><?= number_format($resources->research_data) ?></strong></li>
            <li><span><i class="fas fa-adjust"></i> Dark Matter</span> <strong><?= number_format($resources->dark_matter) ?></strong></li>
        </ul>
         <a href="/bank" class="btn">Manage Bank</a>
    </div>
</div>
