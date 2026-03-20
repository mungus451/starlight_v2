import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { TrainingIsland, type TrainingInitialState } from './components/TrainingIsland';
import './styles/training-spa.css';

type TrainingWindow = Window & {
    __trainingSpaMounted?: boolean;
};

const mountNode = document.getElementById('training-spa-root');

function toInt(value: string | null, fallback = 0): number {
    const parsed = Number(value ?? '');
    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

if (mountNode) {
    const trainingWindow = window as TrainingWindow;
    const legacyRoot = document.getElementById('training-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!trainingWindow.__trainingSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'training-spa-mounted',
        () => {
            trainingWindow.__trainingSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    let units: TrainingInitialState['units'] = [];
    try {
        const raw = mountNode.getAttribute('data-units');
        units = raw ? (JSON.parse(raw) as TrainingInitialState['units']) : [];
    } catch {
        units = [];
    }

    const initialState: TrainingInitialState = {
        resources: {
            credits: toInt(mountNode.getAttribute('data-credits')),
            untrained_citizens: toInt(mountNode.getAttribute('data-untrained-citizens')),
        },
        units,
    };

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <TrainingIsland initialState={initialState} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}
