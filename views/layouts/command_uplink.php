<?php
/**
 * Starlight Dominion - Command Uplink
 *
 * EXPECTS from the main layout:
 * - $uplink_data (array)
 */
?>

<aside class="command-uplink-panel">
    <div class="command-uplink-header">
        <h3>Command Uplink</h3>
    </div>
    <div class="command-uplink-feed">
        <?php if (empty($uplink_data['SYSTEM_ALERTS']) && empty($uplink_data['THREAT_ANALYSIS']) && empty($uplink_data['OPPORTUNITIES'])): ?>
            <div class="uplink-entry uplink-type-system">
                <span class="uplink-text">Systems nominal. All clear.</span>
            </div>
        <?php endif; ?>

        <?php foreach ($uplink_data['SYSTEM_ALERTS'] as $alert): ?>
            <div class="uplink-entry uplink-type-system">
                <span class="uplink-text"><?= htmlspecialchars($alert['text']) ?></span>
                <?php if (isset($alert['link'])): ?>
                    <a href="<?= htmlspecialchars($alert['link']) ?>" class="uplink-action">[Engage]</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php foreach ($uplink_data['THREAT_ANALYSIS'] as $alert): ?>
            <div class="uplink-entry uplink-type-threat">
                <span class="uplink-text"><?= htmlspecialchars($alert['text']) ?></span>
                <?php if (isset($alert['link'])): ?>
                    <a href="<?= htmlspecialchars($alert['link']) ?>" class="uplink-action">[Analyze]</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php foreach ($uplink_data['OPPORTUNITIES'] as $alert): ?>
            <div class="uplink-entry uplink-type-opportunity">
                <span class="uplink-text"><?= htmlspecialchars($alert['text']) ?></span>
                <?php if (isset($alert['link'])): ?>
                    <a href="<?= htmlspecialchars($alert['link']) ?>" class="uplink-action">[Execute]</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</aside>
