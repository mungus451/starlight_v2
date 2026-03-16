import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { NotificationsIsland } from './components/NotificationsIsland';
import './styles/notifications-spa.css';

type NotificationsWindow = Window & {
    __notificationsSpaMounted?: boolean;
};

const mountNode = document.getElementById('notifications-spa-root');

if (mountNode) {
    const pageValue = Number(mountNode.getAttribute('data-page') ?? '1');
    const initialPage = Number.isFinite(pageValue) && pageValue > 0 ? pageValue : 1;

    const notificationsWindow = window as NotificationsWindow;
    const legacyRoot = document.getElementById('notifications-legacy-root');

    const revealLegacyRoot = (): void => {
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }
    };

    const fallbackTimeout = window.setTimeout(() => {
        if (!notificationsWindow.__notificationsSpaMounted) {
            revealLegacyRoot();
        }
    }, 1600);

    window.addEventListener(
        'notifications-spa-mounted',
        () => {
            notificationsWindow.__notificationsSpaMounted = true;
            window.clearTimeout(fallbackTimeout);
        },
        { once: true },
    );

    try {
        createRoot(mountNode).render(
            <StrictMode>
                <NotificationsIsland initialPage={initialPage} />
            </StrictMode>,
        );
    } catch (error) {
        window.clearTimeout(fallbackTimeout);
        revealLegacyRoot();
        throw error;
    }
}
