import { beforeEach, describe, expect, it, vi } from 'vitest';

const renderMock = vi.fn();
const createRootMock = vi.fn(() => ({ render: renderMock }));

vi.mock('react-dom/client', () => ({
    createRoot: createRootMock,
}));

vi.mock('./components/ProfileIsland', () => ({
    ProfileIsland: () => null,
}));

describe('profile entrypoint', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.useFakeTimers();
        createRootMock.mockReset();
        renderMock.mockReset();
        document.body.innerHTML = '';
        delete (window as Window & { __profileSpaMounted?: boolean }).__profileSpaMounted;
    });

    it('mounts SPA when mount node is present', async () => {
        document.body.innerHTML = `
            <div id="profile-spa-root" data-profile-id="5" data-csrf-token="tok" data-profile="{}"></div>
            <div id="profile-legacy-root" style="display:none"></div>
        `;

        await import('./profile');

        expect(createRootMock).toHaveBeenCalledTimes(1);
        expect(renderMock).toHaveBeenCalledTimes(1);
    });

    it('does nothing when no mount node exists', async () => {
        document.body.innerHTML = '<div id="profile-legacy-root"></div>';

        await import('./profile');

        expect(createRootMock).not.toHaveBeenCalled();
        expect(renderMock).not.toHaveBeenCalled();
    });

    it('reveals legacy root after timeout when SPA mount event is not fired', async () => {
        document.body.innerHTML = `
            <div id="profile-spa-root" data-profile-id="5" data-csrf-token="tok"></div>
            <div id="profile-legacy-root" style="display:none"></div>
        `;

        await import('./profile');

        const legacyRoot = document.getElementById('profile-legacy-root');
        expect(legacyRoot?.style.display).toBe('none');

        vi.advanceTimersByTime(1600);

        expect(legacyRoot?.style.display).toBe('');
    });

    it('hides legacy root and cancels timeout when mount event fires', async () => {
        document.body.innerHTML = `
            <div id="profile-spa-root" data-profile-id="5" data-csrf-token="tok"></div>
            <div id="profile-legacy-root" style="display:none"></div>
        `;

        await import('./profile');

        const legacyRoot = document.getElementById('profile-legacy-root');

        window.dispatchEvent(new Event('profile-spa-mounted'));
        vi.advanceTimersByTime(1600);

        expect((window as Window & { __profileSpaMounted?: boolean }).__profileSpaMounted).toBe(true);
        expect(legacyRoot?.style.display).toBe('none');
    });

    it('hides legacy root again if it was revealed before mount event fires', async () => {
        document.body.innerHTML = `
            <div id="profile-spa-root" data-profile-id="5" data-csrf-token="tok"></div>
            <div id="profile-legacy-root" style="display:none"></div>
        `;

        await import('./profile');

        const legacyRoot = document.getElementById('profile-legacy-root');

        // Simulate fallback revealing the legacy root before SPA mounts
        if (legacyRoot) {
            legacyRoot.style.display = '';
        }

        window.dispatchEvent(new Event('profile-spa-mounted'));

        expect(legacyRoot?.style.display).toBe('none');
    });

    it('reveals legacy root if createRoot throws', async () => {
        const error = new Error('render failed');
        createRootMock.mockImplementationOnce(() => {
            throw error;
        });

        document.body.innerHTML = `
            <div id="profile-spa-root" data-profile-id="5" data-csrf-token="tok"></div>
            <div id="profile-legacy-root" style="display:none"></div>
        `;

        await expect(import('./profile')).rejects.toThrow('render failed');

        const legacyRoot = document.getElementById('profile-legacy-root');
        expect(legacyRoot?.style.display).toBe('');
    });
});
