export interface NotificationItem {
    id: number;
    type: string;
    title: string;
    message: string;
    link: string | null;
    is_read: boolean;
    created_at: string;
}

export interface NotificationsApiResponse {
    notifications: NotificationItem[];
    pagination: {
        current_page: number;
        per_page: number;
        total_items: number;
        total_pages: number;
        has_previous: boolean;
        has_next: boolean;
    };
}

export interface NotificationPreferences {
    attack_enabled: boolean;
    spy_enabled: boolean;
    alliance_enabled: boolean;
    system_enabled: boolean;
    push_notifications_enabled: boolean;
}

function getCsrfToken(): string {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token ?? '';
}

async function parseJson<T>(response: Response): Promise<T> {
    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }
    return (await response.json()) as T;
}

export async function fetchNotifications(page = 1, perPage = 20): Promise<NotificationsApiResponse> {
    const response = await fetch(`/api/v1/notifications?page=${page}&per_page=${perPage}`, {
        credentials: 'same-origin',
    });

    return parseJson<NotificationsApiResponse>(response);
}

export async function markNotificationRead(id: number): Promise<void> {
    const response = await fetch(`/api/v1/notifications/${id}/read`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: new URLSearchParams({ csrf_token: getCsrfToken() }).toString(),
    });

    await parseJson<{ success: boolean }>(response);
}

export async function markAllNotificationsRead(): Promise<void> {
    const response = await fetch('/api/v1/notifications/read-all', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: new URLSearchParams({ csrf_token: getCsrfToken() }).toString(),
    });

    await parseJson<{ success: boolean }>(response);
}

export async function fetchNotificationPreferences(): Promise<NotificationPreferences> {
    const response = await fetch('/api/v1/notification-preferences', {
        credentials: 'same-origin',
    });

    return parseJson<NotificationPreferences>(response);
}
