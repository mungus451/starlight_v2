<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card bg-dark text-light border-secondary shadow-lg">
                <div class="card-header border-secondary text-center py-4">
                    <h1 class="display-4 text-neon-blue glitch-text" data-text="ESTABLISH IDENTITY">ESTABLISH IDENTITY</h1>
                    <p class="lead text-muted">A new era has begun. Choose your race and class to define your destiny in the Starlight Dominion.</p>
                </div>
                <div class="card-body p-5">
                    <form action="/choose-identity" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="mb-5">
                            <h3 class="text-neon-blue mb-4 border-bottom border-secondary pb-2"><i class="fas fa-dna mr-2"></i> Select Your Race</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="race" value="Humans" id="race-humans" class="d-none" required>
                                        <label for="race-humans" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Humans</h4>
                                            <p class="small text-success mb-0">+5% Strength Bonus (Offense)</p>
                                            <p class="small text-muted">Adaptive and resilient, humans excel in pure martial power.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="race" value="Cyborgs" id="race-cyborgs" class="d-none">
                                        <label for="race-cyborgs" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Cyborgs</h4>
                                            <p class="small text-success mb-0">+5% Defensive Bonus</p>
                                            <p class="small text-muted">Machine-enhanced warriors with impenetrable armor plating.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="race" value="Sythera" id="race-sythera" class="d-none">
                                        <label for="race-sythera" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Sythera</h4>
                                            <p class="small text-success mb-0">+5% Economic Bonus</p>
                                            <p class="small text-muted">Master manipulators of trade and resource efficiency.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="race" value="Juggalo" id="race-juggalo" class="d-none">
                                        <label for="race-juggalo" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Juggalo</h4>
                                            <p class="small text-success mb-0">+5% Spy Bonus</p>
                                            <p class="small text-muted">Elusive and unpredictable, masters of the unseen world.</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h3 class="text-neon-blue mb-4 border-bottom border-secondary pb-2"><i class="fas fa-user-tag mr-2"></i> Select Your Class</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="class" value="Thief" id="class-thief" class="d-none" required>
                                        <label for="class-thief" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Thief</h4>
                                            <p class="small text-success mb-0">+5% Economic Bonus</p>
                                            <p class="small text-muted">Specializes in redirecting credits to their own coffers.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="class" value="Cleric" id="class-cleric" class="d-none">
                                        <label for="class-cleric" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Cleric</h4>
                                            <p class="small text-success mb-0">+5% Spy Bonus</p>
                                            <p class="small text-muted">Masters of information and spiritual guidance.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="class" value="Guard" id="class-guard" class="d-none">
                                        <label for="class-guard" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Guard</h4>
                                            <p class="small text-success mb-0">+5% Defensive Bonus</p>
                                            <p class="small text-muted">Stalwart protectors of the empire's assets.</p>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="selection-card p-3 border border-secondary rounded hover-glow h-100">
                                        <input type="radio" name="class" value="Soldier" id="class-soldier" class="d-none">
                                        <label for="class-soldier" class="w-100 cursor-pointer">
                                            <h4 class="text-white">Soldier</h4>
                                            <p class="small text-success mb-0">+5% Offensive Bonus</p>
                                            <p class="small text-muted">Front-line combatants dedicated to conquest.</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-outline-info btn-lg px-5 border-neon shadow-lg">CONFIRM IDENTITY</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .selection-card {
        transition: all 0.3s ease;
        background: rgba(0,0,0,0.3);
        cursor: pointer;
    }
    .selection-card:hover {
        background: rgba(0, 243, 255, 0.1);
        border-color: #00f3ff !important;
        transform: translateY(-5px);
    }
    input[type="radio"]:checked + label .selection-card,
    .selection-card:has(input[type="radio"]:checked) {
        background: rgba(0, 243, 255, 0.2);
        border-color: #00f3ff !important;
        box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
    }
    .hover-glow:hover {
        box-shadow: 0 0 15px rgba(0, 243, 255, 0.2);
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>

<script>
    document.querySelectorAll('.selection-card').forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Visual feedback
            const name = radio.getAttribute('name');
            document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                r.closest('.selection-card').classList.remove('selected');
            });
            card.classList.add('selected');
        });
    });
</script>
