<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        
        <div class="structure-card border-danger">
            <div class="card-header-main" style="border-bottom: 1px solid rgba(229, 62, 62, 0.3);">
                <div class="card-icon">
                    <i class="fas fa-satellite-dish text-danger fa-2x"></i>
                </div>
                <div class="card-title-group text-center">
                    <h2 class="card-title text-danger" style="font-family: 'Orbitron', sans-serif;">DISTRESS SIGNAL CONTROL</h2>
                    <span class="card-category text-muted">Emergency Broadcast System</span>
                </div>
            </div>

            <div class="card-body-main p-4">
                
                <?php if ($cooldownRemaining > 0): ?>
                    <div class="alert alert-dark border-danger text-center">
                        <h4 class="text-danger"><i class="fas fa-lock"></i> RELAYS OFFLINE</h4>
                        <p class="mb-0">Cooling down... ready in <?= gmdate("H:i:s", $cooldownRemaining) ?></p>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/dashboard" class="btn btn-outline-secondary">Return to Dashboard</a>
                    </div>
                <?php else: ?>

                    <form action="/alliance/sos/broadcast" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <div class="mb-4">
                            <label class="text-uppercase text-muted small fw-bold mb-2">Select Alert Priority</label>
                            
                            <div class="list-group">
                                <label class="list-group-item bg-dark border-secondary text-light d-flex gap-3 align-items-center cursor-pointer">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="type" value="invasion" checked>
                                    <div>
                                        <strong class="text-danger">INVASION DEFENSE</strong>
                                        <div class="small text-muted">Use when under direct attack.</div>
                                    </div>
                                </label>
                                <label class="list-group-item bg-dark border-secondary text-light d-flex gap-3 align-items-center cursor-pointer">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="type" value="resource">
                                    <div>
                                        <strong class="text-warning">RESOURCE EMERGENCY</strong>
                                        <div class="small text-muted">Request urgent supplies.</div>
                                    </div>
                                </label>
                                <label class="list-group-item bg-dark border-secondary text-light d-flex gap-3 align-items-center cursor-pointer">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="type" value="strike">
                                    <div>
                                        <strong class="text-info">COORDINATED STRIKE</strong>
                                        <div class="small text-muted">Rally for a joint attack.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="text-uppercase text-muted small fw-bold mb-2">Additional Intel (Optional)</label>
                            <textarea name="message" class="form-control bg-black border-secondary text-light" rows="3" placeholder="E.g., Enemy fleet incoming at [123,456]. Need 50k reinforcements."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg fw-bold" style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
                                <i class="fas fa-broadcast-tower me-2"></i> BROADCAST SIGNAL
                            </button>
                            <a href="/dashboard" class="btn btn-link text-muted text-decoration-none text-center">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
                
            </div>
        </div>

    </div>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.form-check-input:checked {
    background-color: #e53e3e;
    border-color: #e53e3e;
}
</style>
