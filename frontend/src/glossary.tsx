import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { GlossaryIsland } from './components/GlossaryIsland';
import './styles/glossary-spa.css';

type GlossaryWindow = Window & {
    __glossarySpaMounted?: boolean;
};

const mountNode = document.getElementById('glossary-spa-root');

if (mountNode) {
    const glossaryWindow = window as GlossaryWindow;
    const legacyRoot = document.getElementById('glossary-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!glossaryWindow.__glossarySpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'glossary-spa-mounted',
        () => {
            glossaryWindow.__glossarySpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <GlossaryIsland />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}
