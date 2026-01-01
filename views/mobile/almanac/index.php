<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 class="display-4 text-center text-neon-blue glitch-text" data-text="The Almanac">The Almanac</h1>
        <p class="text-center text-muted">Historical Dossiers & Records</p>
    </div>

    <!-- Tab Navigation -->
    <div id="almanac-tabs" class="mobile-tabs">
        <a href="#" class="tab-link active" data-tab="players">
            <i class="fas fa-user-astronaut me-2"></i>Players
        </a>
        <a href="#" class="tab-link" data-tab="alliances">
            <i class="fas fa-users me-2"></i>Alliances
        </a>
    </div>

    <div id="tab-content">
        <?php require __DIR__ . '/partials/_players.php'; ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/js/almanac.js?v=6"></script>
