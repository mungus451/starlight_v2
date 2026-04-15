import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    fetchNotificationPreferences,
    fetchNotifications,
    fetchProfileData,
    markAllNotificationsRead,
    markNotificationRead,
    updateNotificationPreferences,
} from './api';

describe('notifications API client', () => {
    const fetchMock = vi.fn<typeof fetch>();

    beforeEach(() => {
        vi.stubGlobal('fetch', fetchMock);
        document.head.innerHTML = '<meta name="csrf-token" content="csrf-123">';
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        document.head.innerHTML = '';
        fetchMock.mockReset();
    });

    it('fetchNotifications sends expected query and parses payload', async () => {
        fetchMock.mockResolvedValueOnce(
            new Response(
                JSON.stringify({
                    notifications: [],
                    pagination: {
                        current_page: 2,
                        per_page: 10,
                        total_items: 0,
                        total_pages: 0,
                        has_previous: true,
                        has_next: false,
                    },
                }),
                { status: 200 },
            ),
        );

        const payload = await fetchNotifications(2, 10);

        expect(fetchMock).toHaveBeenCalledWith('/api/v1/notifications?page=2&per_page=10', {
            credentials: 'same-origin',
        });
        expect(payload.pagination.current_page).toBe(2);
        expect(payload.pagination.per_page).toBe(10);
    });

    it('propagates json error messages on non-OK responses', async () => {
        fetchMock.mockResolvedValueOnce(
            new Response(JSON.stringify({ error: 'Bad things happened' }), {
                status: 400,
                statusText: 'Bad Request',
            }),
        );

        await expect(fetchNotifications()).rejects.toThrow('Bad things happened');
    });

    it('falls back to text error messages on non-JSON responses', async () => {
        fetchMock.mockResolvedValueOnce(
            new Response('Upstream timeout', {
                status: 504,
                statusText: 'Gateway Timeout',
            }),
        );

        await expect(fetchNotifications()).rejects.toThrow('Upstream timeout');
    });

    it('markNotificationRead includes CSRF header/body', async () => {
        fetchMock.mockResolvedValueOnce(new Response(JSON.stringify({ success: true }), { status: 200 }));

        await markNotificationRead(45);

        expect(fetchMock).toHaveBeenCalledTimes(1);
        const [url, options] = fetchMock.mock.calls[0] as [string, RequestInit];

        expect(url).toBe('/api/v1/notifications/45/read');
        expect(options.method).toBe('POST');
        expect(options.credentials).toBe('same-origin');
        expect(options.headers).toEqual({
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': 'csrf-123',
        });
        expect(options.body).toBe('csrf_token=csrf-123');
    });

    it('fetchNotificationPreferences reads preferences payload', async () => {
        fetchMock.mockResolvedValueOnce(
            new Response(
                JSON.stringify({
                    attack_enabled: true,
                    spy_enabled: false,
                    alliance_enabled: true,
                    system_enabled: true,
                    push_notifications_enabled: false,
                }),
                { status: 200 },
            ),
        );

        const payload = await fetchNotificationPreferences();

        expect(payload.attack_enabled).toBe(true);
        expect(payload.spy_enabled).toBe(false);
    });

    it('markAllNotificationsRead calls read-all endpoint', async () => {
        fetchMock.mockResolvedValueOnce(new Response(JSON.stringify({ success: true }), { status: 200 }));

        await markAllNotificationsRead();

        expect(fetchMock).toHaveBeenCalledWith('/api/v1/notifications/read-all', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': 'csrf-123',
            },
            body: 'csrf_token=csrf-123',
        });
    });

    it('updateNotificationPreferences posts toggles and CSRF token', async () => {
        fetchMock.mockResolvedValueOnce(new Response(JSON.stringify({ success: true }), { status: 200 }));

        await updateNotificationPreferences({
            attack_enabled: true,
            spy_enabled: false,
            alliance_enabled: true,
            system_enabled: false,
            push_notifications_enabled: true,
        });

        expect(fetchMock).toHaveBeenCalledWith('/api/v1/notification-preferences', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': 'csrf-123',
            },
            body: 'csrf_token=csrf-123&attack_enabled=1&spy_enabled=0&alliance_enabled=1&system_enabled=0&push_notifications_enabled=1',
        });
    });
});

describe('profile API client', () => {
    const fetchMock = vi.fn<typeof fetch>();

    beforeEach(() => {
        vi.stubGlobal('fetch', fetchMock);
        document.head.innerHTML = '<meta name="csrf-token" content="csrf-123">';
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        document.head.innerHTML = '';
        fetchMock.mockReset();
    });

    it('fetchProfileData calls /api/v1/profile/{id} with credentials', async () => {
        const payload = {
            profile: {
                id: 7,
                character_name: 'Commander Rex',
                bio: 'Test bio',
                profile_picture_url: null,
                formatted_created_at: '2025-01-01',
            },
            stats: { level: 10, net_worth: 50000, war_prestige: 200 },
            alliance: null,
            viewer: { can_invite: false },
        };
        fetchMock.mockResolvedValueOnce(new Response(JSON.stringify(payload), { status: 200 }));

        const result = await fetchProfileData(7);

        expect(fetchMock).toHaveBeenCalledWith('/api/v1/profile/7', {
            credentials: 'same-origin',
        });
        expect(result.profile.character_name).toBe('Commander Rex');
        expect(result.stats.level).toBe(10);
    });

    it('fetchProfileData surfaces error via parseJson on non-OK response', async () => {
        fetchMock.mockResolvedValueOnce(
            new Response(JSON.stringify({ error: 'Not found' }), {
                status: 404,
                statusText: 'Not Found',
            }),
        );

        await expect(fetchProfileData(99)).rejects.toThrow('Not found');
    });
});
