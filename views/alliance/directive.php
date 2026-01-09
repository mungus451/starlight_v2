<?php
// views/alliance/directive.php
?>
<style>
    :root {
        --glass-bg: rgba(13, 17, 23, 0.85);
        --glass-border: 1px solid rgba(45, 209, 209, 0.2);
        --neon-blue: #00f3ff;
        --neon-glow: 0 0 10px rgba(0, 243, 255, 0.3);
        --tech-font: 'Orbitron', sans-serif;
        --data-font: 'Courier New', monospace;
    }

    .command-header {
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .command-title {
        font-family: var(--tech-font);
        font-size: 2.5rem;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: var(--neon-glow);
        margin-bottom: 0.5rem;
    }

    .command-subtitle {
        color: var(--neon-blue);
        font-family: var(--data-font);
        font-size: 0.9rem;
        letter-spacing: 1px;
        opacity: 0.8;
    }

    /* Grid Layout */
    .directive-grid-page {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding-bottom: 2rem;
    }

    /* Glass Card */
    .directive-card {
        background: var(--glass-bg);
        border: var(--glass-border);
        border-radius: 12px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .directive-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .directive-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), var(--neon-glow);
        border-color: var(--neon-blue);
    }

    .directive-card:hover::before {
        opacity: 1;
    }

    /* Icon Box */
    .card-icon-box {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(0, 243, 255, 0.05);
        border: 1px solid rgba(0, 243, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 2.5rem;
        color: var(--neon-blue);
        box-shadow: inset 0 0 20px rgba(0, 243, 255, 0.05);
        transition: all 0.3s;
    }

    .directive-card:hover .card-icon-box {
        background: rgba(0, 243, 255, 0.1);
        box-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
        transform: scale(1.1) rotate(5deg);
    }

    /* Typography */
    .card-title {
        font-family: var(--tech-font);
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        color: #fff;
    }

    .card-desc {
        color: #a8afd4;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
        min-height: 2.5em; /* Alignment fix */
    }

    /* Data Display */
    .data-terminal {
        width: 100%;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        font-family: var(--data-font);
    }

    .data-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
    }

    .data-row:last-child {
        margin-bottom: 0;
        padding-top: 0.5rem;
        border-top: 1px dashed rgba(255, 255, 255, 0.1);
    }

    .data-label { color: #6c757d; }
    .data-value { color: #fff; font-weight: bold; }
    .data-value.target { color: var(--neon-blue); text-shadow: 0 0 5px var(--neon-blue); }

    /* Button */
    .btn-issue-order {
        width: 100%;
        background: transparent;
        border: 1px solid var(--neon-blue);
        color: var(--neon-blue);
        padding: 0.8rem;
        font-family: var(--tech-font);
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .btn-issue-order:hover {
        background: var(--neon-blue);
        color: #000;
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
    }

    .btn-active-state {
        background: rgba(0, 243, 255, 0.1);
        border-color: var(--neon-blue);
        color: var(--neon-blue);
        opacity: 0.8;
    }

    .btn-issue-order:disabled {
        border-color: #555;
        color: #555;
        cursor: not-allowed;
        box-shadow: none;
    }

    /* Back Link */
    .back-link-container {
        text-align: center;
        margin-top: 2rem;
    }
    
    .back-link {
        color: var(--neon-blue);
        text-decoration: none;
        font-family: var(--tech-font);
        font-size: 0.9rem;
        transition: all 0.2s;
        border-bottom: 1px solid transparent;
    }
    
    .back-link:hover {
        text-shadow: var(--neon-glow);
        border-bottom-color: var(--neon-blue);
    }
</style>

<div class="container">
    <div class="command-header">
        <h1 class="command-title glitch-text" data-text="ALLIANCE COMMAND">ALLIANCE COMMAND</h1>
        <div class="command-subtitle">SELECT STRATEGIC PRIORITY // AUTHORIZATION LEVEL: LEADER</div>
    </div>

    <div class="directive-grid-page">
        <?php foreach ($options as $key => $opt): ?>
            <?php $isActive = ($key === ($activeType ?? null)); ?>
            <div class="directive-card <?= $isActive ? 'is-active' : '' ?>">
                <?php if ($isActive): ?>
                    <div class="active-badge">CURRENTLY ACTIVE</div>
                <?php endif; ?>

                <div class="card-icon-box">
                    <i class="fas <?= $opt['icon'] ?>"></i>
                </div>
                
                <h3 class="card-title"><?= htmlspecialchars($opt['name']) ?></h3>
                <p class="card-desc"><?= htmlspecialchars($opt['desc']) ?></p>
                
                <div class="data-terminal">
                    <div class="data-row">
                        <span class="data-label">CURRENT STATUS</span>
                        <span class="data-value"><?= number_format($opt['current']) ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">TARGET GOAL</span>
                        <span class="data-value target"><?= number_format($opt['target']) ?></span>
                    </div>
                </div>

                <button class="btn-issue-order <?= $isActive ? 'btn-active-state' : '' ?>" 
                        data-type="<?= $key ?>" 
                        <?= $isActive ? 'disabled' : '' ?>>
                    <?= $isActive ? 'STRATEGIC PRIORITY SET' : 'INITIALIZE DIRECTIVE' ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="back-link-container">
        <a href="/dashboard" class="back-link"><i class="fas fa-chevron-left"></i> RETURN TO DASHBOARD</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.btn-issue-order');
    
    buttons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const type = btn.dataset.type;
            const originalText = btn.innerText;
            
            // Visual Feedback
            btn.disabled = true;
            btn.innerText = 'TRANSMITTING...';
            btn.style.borderColor = '#fff';
            
            try {
                const res = await fetch('/alliance/directive/set', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: type })
                });
                const data = await res.json();
                
                if (data.message) {
                    // Success Animation
                    btn.style.backgroundColor = '#00f3ff';
                    btn.style.color = '#000';
                    btn.innerText = 'DIRECTIVE ACTIVE';
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                } else {
                    alert('Error: ' + (data.error || 'Unknown'));
                    resetBtn(btn, originalText);
                }
            } catch (e) {
                alert('System Error. Connection Failed.');
                resetBtn(btn, originalText);
            }
        });
    });

    function resetBtn(btn, text) {
        btn.disabled = false;
        btn.innerText = text;
        btn.style.borderColor = '';
        btn.style.backgroundColor = '';
        btn.style.color = '';
    }
});
</script>