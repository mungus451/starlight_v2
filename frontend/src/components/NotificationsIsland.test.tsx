import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { NotificationsIsland } from './NotificationsIsland';
import {
    fetchNotifications,
    markAllNotificationsRead,
    markNotificationRead,
    type NotificationsApiResponse,
} from '../lib/api';

vi.mock('../lib/api', () => ({
    fetchNotifications: vi.fn(),
    markNotificationRead: vi.fn(),
    markAllNotificationsRead: vi.fn(),
}));

const mockedFetchNotifications = vi.mocked(fetchNotifications);
const mockedMarkNotificationRead = vi.mocked(markNotificationRead);
const mockedMarkAllNotificationsRead = vi.mocked(markAllNotificationsRead);

function makeResponse(overrides: Partial<NotificationsApiResponse> = {}): NotificationsApiResponse {
    return {
        notifications: [
            {
                id: 11,
                type: 'system',
                title: 'Status',
                message: 'All systems nominal',
                link: null,
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
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
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
            expect(screen.getByRole('button', { name: 'Mark All Read (SPA)' })).toBeTruthy();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mark All Read (SPA)' }));

        await waitFor(() => {
            expect(mockedMarkAllNotificationsRead).toHaveBeenCalledTimes(1);
        });

        await waitFor(() => {
            expect(mockedFetchNotifications).toHaveBeenCalledTimes(2);
            expect(mockedFetchNotifications).toHaveBeenLastCalledWith(2);
        });
    });
});
