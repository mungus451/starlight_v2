import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { NotificationsIsland } from './components/NotificationsIsland';
import './styles/notifications-spa.css';

const mountNode = document.getElementById('notifications-spa-root');

if (mountNode) {
    const pageValue = Number(mountNode.getAttribute('data-page') ?? '1');
    const initialPage = Number.isFinite(pageValue) && pageValue > 0 ? pageValue : 1;

    const legacyRoot = document.getElementById('notifications-legacy-root');
    if (legacyRoot) {
        legacyRoot.style.display = 'none';
    }

    createRoot(mountNode).render(
        <StrictMode>
            <NotificationsIsland initialPage={initialPage} />
        </StrictMode>,
    );
}
