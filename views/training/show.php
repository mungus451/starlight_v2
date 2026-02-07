


        <div class="training-page-wrapper">



            <main class="training-main-content">

                <?php if(isset($_SESSION['training_message'])): ?>
                    <div class="flash-message-success">
                        <?php echo htmlspecialchars($_SESSION['training_message']); unset($_SESSION['training_message']); ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($_SESSION['training_error'])): ?>
                    <div class="flash-message-error">
                        <?php echo htmlspecialchars($_SESSION['training_error']); unset($_SESSION['training_error']); ?>
                    </div>
                <?php endif; ?>

                <div class="content-box training-header">
                    <div class="training-header-grid">
                        <div>
                            <p class="training-header-label">Citizens</p>
                            <p id="available-citizens" data-amount="<?php echo $user_stats['untrained_citizens']; ?>" class="training-header-value">
                                <?php echo number_format($user_stats['untrained_citizens']); ?>
                            </p>
                        </div>
                        <div>
                            <p class="training-header-label">Credits</p>
                            <p id="available-credits" data-amount="<?php echo $user_stats['credits']; ?>" class="training-header-value">
                                <?php echo number_format($user_stats['credits']); ?>
                            </p>
                        </div>
                        <div>
                            <p class="training-header-label">Total Cost</p>
                            <p id="total-build-cost" class="text-lg font-bold text-yellow-400">0</p>
                        </div>
                        <div>
                            <p class="training-header-label">Total Refund</p>
                            <p id="total-refund-value" class="text-lg font-bold text-green-400">0</p>
                        </div>
                    </div>
                </div>
                
                <div class="tabs-nav">
                    <a class="tab-link <?php if ($current_tab === 'train') echo 'active'; ?>" data-tab="train-tab-content">Train Units</a>
                </div>

                <!-- TRAIN TAB -->
                <div id="train-tab-content" class="tab-content <?php if ($current_tab === 'train') echo 'active'; ?>">
                    <form id="train-form" action="/training/train" method="POST" class="training-form" data-charisma-discount="<?php echo $charisma_discount; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="train">
                        <div class="training-unit-grid">
                            <?php foreach($units as $key => $unit): 
                                $discounted_cost = floor($unit['credits'] * $charisma_discount);
                                $ownedCount = $user_stats[$key] ?? 0;
                            ?>
                            <div class="content-box training-unit-card">
                                <img src="/img/<?php echo strtolower($unit['name']); ?>.avif" alt="<?php echo $unit['name']; ?> Icon" class="icon">
                                <div class="details">
                                    <p class="training-unit-name"><?php echo $unit['name']; ?></p>
                                    <p class="training-unit-desc"><?php echo $unit['desc']; ?></p>
                                    <p class="training-unit-stat">Cost: <?php echo number_format($discounted_cost); ?> Credits</p>
                                    <p class="training-unit-stat">Owned: <?php echo number_format($ownedCount); ?></p>
                                </div>
                                <div class="actions">
                                    <input type="number" name="units[<?php echo $key; ?>]" min="0" placeholder="0" data-cost="<?php echo $unit['credits']; ?>" class="training-input-field">
                                    <button type="button" class="training-max-btn train">Max</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="content-box training-submit-container">
                            <button type="submit" class="training-submit-btn train">Train All Selected Units</button>
                        </div>
                    </form>
                </div>
                <!-- DISBAND TAB -->

                <!-- RECOVERY TAB -->
            </main>
        </div>