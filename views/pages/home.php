<style>
    /* Page shell */
    .home-container {
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3rem;
        padding: 4rem 1.5rem 5rem;
        position: relative;
        overflow: hidden;
        background: radial-gradient(circle at top, #121a3a 0%, #040814 55%, #02040a 100%);
        isolation: isolate;
    }

    /* floating nebula + stars */
    .home-container::before,
    .home-container::after {
        content: "";
        position: absolute;
        inset: -20% -5%;
        background: radial-gradient(circle, rgba(0, 243, 255, 0.12), transparent 55%);
        filter: blur(120px);
        pointer-events: none;
        z-index: -2;
        animation: drift 18s ease-in-out infinite alternate;
    }
    .home-container::after {
        background: radial-gradient(circle, rgba(249, 199, 79, 0.2), transparent 60%);
        animation-duration: 26s;
        animation-direction: alternate-reverse;
    }
    @keyframes drift {
        0% { transform: translateY(-50px) translateX(-40px) scale(1); }
        100% { transform: translateY(40px) translateX(30px) scale(1.15); }
    }

    /* HERO */
    .hero-section {
        width: min(1080px, 100%);
        background: radial-gradient(circle at 10% 10%, rgba(255,255,255,0.02), rgba(6,10,21,0.85));
        border: 1px solid rgba(255, 255, 255, 0.03);
        border-radius: 1.75rem;
        padding: 3rem 3.5rem 3.25rem;
        text-align: center;
        box-shadow:
            0 30px 60px rgba(0,0,0,0.35),
            inset 0 0 80px rgba(255,255,255,0.015);
        backdrop-filter: blur(14px);
        position: relative;
    }

    .hero-section::before {
        content: "BROWSER MMO";
        position: absolute;
        top: 1.25rem;
        left: 50%;
        transform: translateX(-50%);
        letter-spacing: 0.4rem;
        font-size: 0.55rem;
        color: rgba(255,255,255,0.28);
        text-transform: uppercase;
        font-family: "Orbitron", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .hero-section h1 {
        font-family: "Orbitron", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: clamp(3rem, 4vw, 3.4rem);
        color: #ffffff;
        letter-spacing: -0.01em;
        margin-bottom: 0.75rem;
        text-shadow: 0 12px 30px rgba(0,0,0,0.4);
    }

    .hero-section .tagline {
        font-size: 1.1rem;
        color: rgba(223, 228, 255, 0.85);
        margin-bottom: 2.15rem;
        max-width: 650px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.5;
    }

    /* CTA ROW */
    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        align-items: center;
        justify-content: center;
        margin-bottom: 2.3rem;
    }

    .cta-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.95rem 2.4rem;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.01em;
        transition: transform 0.12s ease-out, box-shadow 0.12s ease-out, background 0.12s ease-out;
        border: 1px solid transparent;
    }

    /* primary: glowing plasma */
    .btn-primary {
        background: linear-gradient(120deg, #00e5ff 0%, #008cff 48%, #2e1aff 100%);
        color: #03101a;
        box-shadow: 0 16px 35px rgba(0, 140, 255, 0.37);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 40px rgba(0, 140, 255, 0.45);
    }

    /* secondary: stealth chip */
    .btn-secondary {
        background: rgba(4, 6, 12, 0.35);
        border-color: rgba(255,255,255,0.03);
        color: #edf1ff;
    }
    .btn-secondary:hover {
        background: rgba(9, 12, 20, 0.7);
        transform: translateY(-1px);
    }

    /* tiny label under hero – supports your current class name */
    .hero-section .hero-footnote,
    .hero-section .subnote {
        display: block;
        margin-top: 1.2rem;
        font-size: 0.6rem;
        letter-spacing: 0.35rem;
        text-transform: uppercase;
        color: rgba(228, 233, 255, 0.25);
        font-family: "Orbitron", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    /* FEATURES */
    .features-section {
        width: min(1120px, 100%);
        padding-top: 0.25rem;
    }
    .features-section h2 {
        color: #ffffff;
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 1.3rem;
        letter-spacing: 0.02em;
        font-family: "Orbitron", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .features-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.4rem;
    }

    .feature-item {
        background: radial-gradient(circle at top, rgba(0, 229, 255, 0.065), rgba(15, 15, 32, 0.2));
        border: 1px solid rgba(255, 255, 255, 0.015);
        border-radius: 1.25rem;
        padding: 1.35rem 1.35rem 1.2rem;
        box-shadow: 0 18px 28px rgba(0,0,0,0.25);
        position: relative;
        overflow: hidden;
    }

    .feature-item::before {
        content: "";
        position: absolute;
        top: -45px;
        right: -45px;
        width: 130px;
        height: 130px;
        background: radial-gradient(circle, rgba(0, 229, 255, 0.35), transparent 70%);
        filter: blur(12px);
        opacity: 0.4;
        pointer-events: none;
    }

    .feature-item h3 {
        margin-top: 0;
        color: #ffffff;
        font-size: 1rem;
        margin-bottom: 0.45rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .feature-item h3::before {
        content: "";
        width: 4px;
        height: 20px;
        border-radius: 999px;
        background: linear-gradient(180deg, #00e5ff 0%, #008cff 100%);
        display: inline-block;
    }

    .feature-item p {
        color: rgba(219, 227, 255, 0.8);
        line-height: 1.55;
        font-size: 0.9rem;
    }

    /* wider layouts */
    @media (min-width: 768px) {
        .cta-buttons {
            flex-direction: row;
        }
        .features-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .hero-section {
            padding: 2.6rem 1.4rem 2.7rem;
        }
        .hero-section h1 {
            font-size: 2.5rem;
        }
    }
</style>


<div class="home-container">
    <div class="hero-section">
        <h1>Starlight Dominion</h1>
        <p class="tagline">Conquer the galaxy, one click at a time. An idle empire awaits.</p>
        
        <div class="cta-buttons">
            <a href="/register" class="cta-button btn-primary">Join the Fight</a>
            <a href="/login" class="cta-button btn-secondary">Login to Your Command</a>
        </div>
        <p class="hero-footnote">Browser-based. Persistent. Player vs Player.</p>
    </div>
    
    <div class="features-section">
        <h2>Features</h2>
        <div class="features-grid">
            <div class="feature-item">
                <h3>Build Your Empire</h3>
                <p>Develop your home world. Construct economic and military structures to generate resources and project power across the stars.</p>
            </div>
            <div class="feature-item">
                <h3>Form Alliances</h3>
                <p>No emperor rules alone. Join a powerful alliance, create your own, and coordinate with allies to dominate the universe.</p>
            </div>
            <div class="feature-item">
                <h3>Wage War</h3>
                <p>Train spies to gather intel, build vast fleets to plunder your rivals, and battle for galactic supremacy on the leaderboards.</p>
            </div>
        </div>
    </div>
</div>
