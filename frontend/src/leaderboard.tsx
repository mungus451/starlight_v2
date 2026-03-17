import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { LeaderboardIsland, type LeaderboardInitialState } from './components/LeaderboardIsland';
import './styles/leaderboard-spa.css';

type LeaderboardWindow = Window & {
    __leaderboardSpaMounted?: boolean;
};

const mountNode = document.getElementById('leaderboard-spa-root');

if (mountNode) {
    const leaderboardWindow = window as LeaderboardWindow;
    const legacyRoot = document.getElementById('leaderboard-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!leaderboardWindow.__leaderboardSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'leaderboard-spa-mounted',
        () => {
            leaderboardWindow.__leaderboardSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    const initialState: LeaderboardInitialState = {
        type: mountNode.getAttribute('data-type') === 'alliances' ? 'alliances' : 'players',
        page: Number(mountNode.getAttribute('data-page') ?? '1') || 1,
        sort: mountNode.getAttribute('data-sort') ?? 'net_worth',
    };

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <LeaderboardIsland initialState={initialState} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}