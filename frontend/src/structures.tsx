import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { StructuresIsland, type StructuresInitialState } from './components/StructuresIsland';
import './styles/structures-spa.css';

type StructuresWindow = Window & {
    __structuresSpaMounted?: boolean;
};

const mountNode = document.getElementById('structures-spa-root');

function toInt(value: string | null, fallback = 0): number {
    const parsed = Number(value ?? '');
    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

if (mountNode) {
    const structuresWindow = window as StructuresWindow;
    const legacyRoot = document.getElementById('structures-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!structuresWindow.__structuresSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'structures-spa-mounted',
        () => {
            structuresWindow.__structuresSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    let categories: StructuresInitialState['categories'] = {};
    try {
        const raw = mountNode.getAttribute('data-grouped-structures');
        categories = raw ? (JSON.parse(raw) as StructuresInitialState['categories']) : {};
    } catch {
        categories = {};
    }

    const initialState: StructuresInitialState = {
        resources: {
            credits: toInt(mountNode.getAttribute('data-credits'), 0),
        },
        categories,
    };

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <StructuresIsland initialState={initialState} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}