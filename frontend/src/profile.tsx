import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { ProfileIsland, type ProfileInitialState } from './components/ProfileIsland';

type ProfileWindow = Window & {
    __profileSpaMounted?: boolean;
};

const mountNode = document.getElementById('profile-spa-root');

function toInt(value: string | null, fallback = 0): number {
    const parsed = Number(value ?? '');
    return Number.isFinite(parsed) ? Math.trunc(parsed) : fallback;
}

if (mountNode) {
    const profileWindow = window as ProfileWindow;
    const legacyRoot = document.getElementById('profile-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!profileWindow.__profileSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'profile-spa-mounted',
        () => {
            profileWindow.__profileSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    let initialState: ProfileInitialState = {
        profile: {
            id: toInt(mountNode.getAttribute('data-profile-id')),
            character_name: '',
            bio: '',
            profile_picture_url: null,
            formatted_created_at: '',
        },
        stats: {
            level: 0,
            net_worth: 0,
            war_prestige: 0,
        },
        alliance: null,
        viewer: {
            can_invite: false,
        },
    };

    try {
        const raw = mountNode.getAttribute('data-profile');
        initialState = raw ? (JSON.parse(raw) as ProfileInitialState) : initialState;
    } catch {
        // Keep server-provided fallback state when payload parsing fails.
    }

    const csrfToken = mountNode.getAttribute('data-csrf-token') ?? '';
    const targetId = toInt(mountNode.getAttribute('data-profile-id'));

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <ProfileIsland initialState={initialState} csrfToken={csrfToken} targetId={targetId} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}
