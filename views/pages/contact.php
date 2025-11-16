<style>
    :root {
        --card: radial-gradient(circle at 30% -10%, rgba(45, 209, 209, 0.07), rgba(13, 15, 27, 0.6));
        --border: rgba(255, 255, 255, 0.03);
        --accent: #2dd1d1;
        --accent-2: #f9c74f;
        --text: #eff1ff;
        --muted: #a8afd4;
        --radius: 18px;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    }

    /* --- Base Container --- */
    .contact-container-full {
        width: 100%;
        max-width: 1200px; /* CHANGED FROM 900px */
        margin-inline: auto;
        padding: 0;
        position: relative;
    }

    .contact-container-full h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: clamp(2.1rem, 3vw, 2.6rem);
        letter-spacing: -0.03em;
        color: #fff;
        padding-top: 1.5rem;
    }

    /* --- Grid for Cards --- */
    .item-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .item-grid {
            grid-template-columns: 1fr;
        }
    }

    /* --- Info Card --- */
    .item-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        text-align: left;
    }
    .item-card h3 {
        color: var(--accent-2);
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 0.75rem;
    }
    .item-card p {
        color: var(--muted);
        line-height: 1.6;
        font-size: 1rem;
        flex-grow: 1;
    }
    .item-card .btn-submit {
        width: 100%;
        margin-top: 1rem;
        text-decoration: none;
        text-align: center;
    }
</style>

<div class="contact-container-full">
    <h1>Contact</h1>

    <div class="item-grid">
        <div class="item-card">
            <h3>Support Inquiries</h3>
            <p>
                For all technical support, bug reports, or account issues, please reach out to our support team directly via email.
            </p>
            <a href="mailto:support@starlightdominion.com" class="btn-submit" style="background: var(--accent); color: #02030a;">
                support@starlightdominion.com
            </a>
        </div>

        <div class="item-card">
            <h3>Community</h3>
            <p>
                Join our official community Discord server to connect with other players, form alliances, and provide feedback to the development team.
            </p>
            <a href="https://discord.gg/sCKvuxHAqt" class="btn-submit" target="_blank" rel="noopener noreferrer">
                Join the Discord Server
            </a>
        </div>
    </div>
</div>