<style>
    .home-container {
        text-align: center;
    }
    .hero-section {
        padding: 2rem 0;
    }
    .hero-section h1 {
        font-size: 3rem; /* Larger than default */
        color: #f9c74f; /* Gold */
        margin-bottom: 0.5rem;
    }
    .hero-section .tagline {
        font-size: 1.25rem;
        color: #c0c0e0;
        margin-bottom: 2rem;
    }
    
    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 3rem;
    }
    
    .cta-button {
        display: block;
        padding: 1rem 2rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        font-size: 1.1rem;
        transition: all 0.2s ease;
    }
    
    .btn-primary {
        background: #5a67d8; /* Indigo */
        color: white;
    }
    .btn-primary:hover {
        background: #7683f5;
    }
    
    .btn-secondary {
        background: #2a2a4a;
        color: #c0c0e0;
        border: 1px solid #3a3a5a;
    }
    .btn-secondary:hover {
        background: #3a3a5a;
        color: white;
    }

    .features-section {
        border-top: 1px solid #3a3a5a;
        padding-top: 2rem;
    }
    .features-section h2 {
        color: #f9c74f;
        text-align: center;
        margin-bottom: 2rem;
    }
    .features-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        text-align: left;
    }
    .feature-item {
        background: #2a2a4a;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #3a3a5a;
    }
    .feature-item h3 {
        margin-top: 0;
        color: #f9c74f;
    }
    .feature-item p {
        color: #c0c0e0;
        line-height: 1.6;
    }
    
    @media (min-width: 768px) {
        .cta-buttons {
            flex-direction: row;
            justify-content: center;
        }
        .features-grid {
            grid-template-columns: 1fr 1fr 1fr;
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