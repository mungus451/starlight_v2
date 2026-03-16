import { beforeEach, describe, expect, it, vi } from 'vitest';

const renderMock = vi.fn();
const createRootMock = vi.fn(() => ({ render: renderMock }));

vi.mock('react-dom/client', () => ({
    createRoot: createRootMock,
}));

vi.mock('./components/NotificationsIsland', () => ({
    NotificationsIsland: () => null,
}));

describe('notifications entrypoint', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.useFakeTimers();
        createRootMock.mockReset();
        renderMock.mockReset();
        document.body.innerHTML = '';
        delete (window as Window & { __notificationsSpaMounted?: boolean }).__notificationsSpaMounted;
    });

    it('mounts SPA and uses data-page when valid', async () => {
        document.body.innerHTML = `
            <div id="notifications-spa-root" data-page="3"></div>
            <div id="notifications-legacy-root" style="display:none"></div>
        `;

        await import('./notifications');

        expect(createRootMock).toHaveBeenCalledTimes(1);
        const firstCall = renderMock.mock.calls[0];
        if (!firstCall) {
            throw new Error('Expected render to be called');
        }
        const renderArg = firstCall[0] as { props?: { children?: { props?: { initialPage?: number } } } };
        expect(renderArg.props?.children?.props?.initialPage).toBe(3);
    });

    it('falls back to page 1 when data-page is invalid', async () => {
        document.body.innerHTML = `
            <div id="notifications-spa-root" data-page="invalid"></div>
            <div id="notifications-legacy-root" style="display:none"></div>
        `;

        await import('./notifications');

        const firstCall = renderMock.mock.calls[0];
        if (!firstCall) {
            throw new Error('Expected render to be called');
        }
        const renderArg = firstCall[0] as { props?: { children?: { props?: { initialPage?: number } } } };
        expect(renderArg.props?.children?.props?.initialPage).toBe(1);
    });

    it('reveals legacy root after timeout when SPA mount event is not fired', async () => {
        document.body.innerHTML = `
            <div id="notifications-spa-root" data-page="2"></div>
            <div id="notifications-legacy-root" style="display:none"></div>
        `;

        await import('./notifications');

        const legacyRoot = document.getElementById('notifications-legacy-root');
        expect(legacyRoot?.style.display).toBe('none');

        vi.advanceTimersByTime(1600);

        expect(legacyRoot?.style.display).toBe('');
    });

    it('marks SPA mounted and prevents timeout reveal when mount event fires', async () => {
        document.body.innerHTML = `
            <div id="notifications-spa-root" data-page="2"></div>
            <div id="notifications-legacy-root" style="display:none"></div>
        `;

        await import('./notifications');

        const legacyRoot = document.getElementById('notifications-legacy-root');

        window.dispatchEvent(new Event('notifications-spa-mounted'));
        vi.advanceTimersByTime(1600);

        expect((window as Window & { __notificationsSpaMounted?: boolean }).__notificationsSpaMounted).toBe(true);
        expect(legacyRoot?.style.display).toBe('none');
    });

    it('reveals legacy root if createRoot throws', async () => {
        const error = new Error('render failed');
        createRootMock.mockImplementationOnce(() => {
            throw error;
        });

        document.body.innerHTML = `
            <div id="notifications-spa-root" data-page="1"></div>
            <div id="notifications-legacy-root" style="display:none"></div>
        `;

        await expect(import('./notifications')).rejects.toThrow('render failed');

        const legacyRoot = document.getElementById('notifications-legacy-root');
        expect(legacyRoot?.style.display).toBe('');
    });

    it('does nothing when no mount node exists', async () => {
        document.body.innerHTML = '<div id="notifications-legacy-root"></div>';

        await import('./notifications');

        expect(createRootMock).not.toHaveBeenCalled();
        expect(renderMock).not.toHaveBeenCalled();
    });
});
