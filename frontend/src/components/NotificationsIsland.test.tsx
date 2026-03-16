import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { NotificationsIsland } from './NotificationsIsland';
import {
    fetchNotificationPreferences,
    fetchNotifications,
    markAllNotificationsRead,
    markNotificationRead,
    updateNotificationPreferences,
    type NotificationsApiResponse,
} from '../lib/api';

vi.mock('../lib/api', () => ({
    fetchNotifications: vi.fn(),
    markNotificationRead: vi.fn(),
    markAllNotificationsRead: vi.fn(),
    fetchNotificationPreferences: vi.fn(),
    updateNotificationPreferences: vi.fn(),
}));

const mockedFetchNotifications = vi.mocked(fetchNotifications);
const mockedMarkNotificationRead = vi.mocked(markNotificationRead);
const mockedMarkAllNotificationsRead = vi.mocked(markAllNotificationsRead);
const mockedFetchNotificationPreferences = vi.mocked(fetchNotificationPreferences);
const mockedUpdateNotificationPreferences = vi.mocked(updateNotificationPreferences);

function makeResponse(overrides: Partial<NotificationsApiResponse> = {}): NotificationsApiResponse {
    return {
        notifications: [
            {
                id: 11,
                type: 'system',
                title: 'Status',
                message: 'All systems nominal',
                link: '/battle/report/12',
                is_read: false,
                created_at: '2026-03-08 12:00:00',
            },
        ],
        pagination: {
            current_page: 2,
            per_page: 20,
            total_items: 1,
            total_pages: 3,
            has_previous: true,
            has_next: true,
        },
        ...overrides,
    };
}

describe('NotificationsIsland', () => {
    let confirmSpy: ReturnType<typeof vi.spyOn>;
    let requestPermissionMock: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        vi.clearAllMocks();
        confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);
        requestPermissionMock = vi.fn().mockResolvedValue('granted');
        vi.stubGlobal('Notification', {
            permission: 'default',
            requestPermission: requestPermissionMock,
        });
        mockedFetchNotificationPreferences.mockResolvedValue({
            attack_enabled: true,
            spy_enabled: true,
            alliance_enabled: true,
            system_enabled: true,
            push_notifications_enabled: false,
        });
    });

    afterEach(() => {
        confirmSpy.mockRestore();
        vi.unstubAllGlobals();
        cleanup();
    });

    it('loads notifications on mount and renders list content', async () => {
        mockedFetchNotifications.mockResolvedValueOnce(makeResponse());

        render(<NotificationsIsland initialPage={2} />);

        expect(screen.getByText('Loading notifications...')).toBeTruthy();

        await waitFor(() => {
            expect(screen.getByText('Status')).toBeTruthy();
        });

        expect(mockedFetchNotifications).toHaveBeenCalledWith(2);
        expect(screen.getByText('Showing 1 items on page 2 of 3')).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Command Uplink' })).toBeTruthy();
    });

    it('renders API error message when initial load fails', async () => {
        mockedFetchNotifications.mockRejectedValueOnce(new Error('Unable to load feed'));

        render(<NotificationsIsland initialPage={1} />);

        await waitFor(() => {
            expect(screen.getByText('Unable to load feed')).toBeTruthy();
        });
    });

    it('marks one notification as read and reloads current page', async () => {
        mockedFetchNotifications.mockResolvedValue(makeResponse());
        mockedMarkNotificationRead.mockResolvedValueOnce();

        render(<NotificationsIsland initialPage={2} />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Mark Read' })).toBeTruthy();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mark Read' }));

        await waitFor(() => {
            expect(mockedMarkNotificationRead).toHaveBeenCalledWith(11);
        });

        await waitFor(() => {
            expect(mockedFetchNotifications).toHaveBeenCalledTimes(2);
            expect(mockedFetchNotifications).toHaveBeenLastCalledWith(2);
        });
    });

    it('marks all notifications as read and reloads current page', async () => {
        mockedFetchNotifications.mockResolvedValue(makeResponse());
        mockedMarkAllNotificationsRead.mockResolvedValueOnce();

        render(<NotificationsIsland initialPage={2} />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Mark All Read' })).toBeTruthy();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mark All Read' }));

        await waitFor(() => {
            expect(mockedMarkAllNotificationsRead).toHaveBeenCalledTimes(1);
        });

        await waitFor(() => {
            expect(mockedFetchNotifications).toHaveBeenCalledTimes(2);
            expect(mockedFetchNotifications).toHaveBeenLastCalledWith(2);
        });

        expect(confirmSpy).toHaveBeenCalledWith('Mark all notifications as read?');
    });

    it('does not call mark-all endpoint when user cancels confirmation', async () => {
        confirmSpy.mockReturnValueOnce(false);
        mockedFetchNotifications.mockResolvedValue(makeResponse());

        render(<NotificationsIsland initialPage={2} />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Mark All Read' })).toBeTruthy();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mark All Read' }));

        expect(mockedMarkAllNotificationsRead).not.toHaveBeenCalled();
    });

    it('renders empty state when no notifications are available', async () => {
        mockedFetchNotifications.mockResolvedValue(
            makeResponse({
                notifications: [],
                pagination: {
                    current_page: 1,
                    per_page: 20,
                    total_items: 0,
                    total_pages: 1,
                    has_previous: false,
                    has_next: false,
                },
            }),
        );

        render(<NotificationsIsland initialPage={1} />);

        await waitFor(() => {
            expect(screen.getByText('No communications in log.')).toBeTruthy();
        });
    });

    it('renders action link when notification has a destination', async () => {
        mockedFetchNotifications.mockResolvedValue(makeResponse());

        render(<NotificationsIsland initialPage={2} />);

        await waitFor(() => {
            expect(screen.getByRole('link', { name: 'View Report' })).toBeTruthy();
        });
    });

    it('loads and saves push preferences from the SPA panel', async () => {
        mockedFetchNotifications.mockResolvedValue(makeResponse());
        mockedUpdateNotificationPreferences.mockResolvedValue();

        render(<NotificationsIsland initialPage={2} />);

        const saveButton = await screen.findByRole('button', { name: 'Save Preferences' });
        fireEvent.click(screen.getByLabelText('Enable Browser Push Notifications'));
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(requestPermissionMock).toHaveBeenCalledTimes(1);
        });

        await waitFor(() => {
            expect(mockedUpdateNotificationPreferences).toHaveBeenCalledWith({
                attack_enabled: true,
                spy_enabled: true,
                alliance_enabled: true,
                system_enabled: true,
                push_notifications_enabled: true,
            });
        });
    });

    it('does not save push preferences when browser permission is denied', async () => {
        requestPermissionMock.mockResolvedValueOnce('denied');
        mockedFetchNotifications.mockResolvedValue(makeResponse());

        render(<NotificationsIsland initialPage={2} />);

        const saveButton = await screen.findByRole('button', { name: 'Save Preferences' });
        fireEvent.click(screen.getByLabelText('Enable Browser Push Notifications'));
        fireEvent.click(saveButton);

        await waitFor(() => {
            expect(requestPermissionMock).toHaveBeenCalledTimes(1);
        });

        expect(mockedUpdateNotificationPreferences).not.toHaveBeenCalled();

        await waitFor(() => {
            expect(screen.getByText('Push notifications were not enabled in this browser.')).toBeTruthy();
        });
    });
});
