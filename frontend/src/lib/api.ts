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
    const text = await response.text();

    if (!response.ok) {
        let message = `Request failed: ${response.status}`;

        if (text.trim() !== '') {
            try {
                const data = JSON.parse(text);
                if (data && typeof data === 'object') {
                    const anyData = data as { error?: unknown; message?: unknown };
                    if (typeof anyData.error === 'string' && anyData.error.trim() !== '') {
                        message = anyData.error;
                    } else if (typeof anyData.message === 'string' && anyData.message.trim() !== '') {
                        message = anyData.message;
                    } else {
                        message = text.trim();
                    }
                } else {
                    message = text.trim();
                }
            } catch {
                message = text.trim();
            }
        }

        throw new Error(message);
    }

    if (text.trim() === '') {
        return {} as T;
    }

    return JSON.parse(text) as T;
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

export interface GlossaryStructure {
    name: string;
    category?: string;
    description: string;
    base_cost: number;
    multiplier: number;
}

export interface GlossaryUnit {
    name: string;
    role: string;
    description: string;
    icon: string;
    credits: number;
    citizens: number;
}

export interface GlossaryArmoryItem {
    name: string;
    offense?: number;
    defense?: number;
    cost_credits: number;
    description: string;
    requires?: string;
    armory_level_req?: number;
}

export interface GlossaryArmoryCategory {
    title: string;
    slots: number;
    items: Record<string, GlossaryArmoryItem>;
}

export interface GlossaryArmoryLoadout {
    title: string;
    unit: string;
    categories: Record<string, GlossaryArmoryCategory>;
}

export interface GlossaryDirective {
    name: string;
    description: string;
    goal: string;
    icon: string;
    badge: string;
}

export interface GlossaryAllianceOp {
    name: string;
    description: string;
    requirement: string;
    reward: string;
    icon: string;
}

export interface GlossaryResource {
    name: string;
    description: string;
    source: string;
    icon: string;
    color: string;
}

export interface GlossaryData {
    structures: Record<string, GlossaryStructure>;
    units: Record<string, GlossaryUnit>;
    armory: Record<string, GlossaryArmoryLoadout>;
    directives: Record<string, GlossaryDirective>;
    allianceOps: Record<string, GlossaryAllianceOp>;
    resources: Record<string, GlossaryResource>;
}

export async function fetchGlossaryData(): Promise<GlossaryData> {
    const response = await fetch('/api/v1/glossary', {
        credentials: 'same-origin',
    });

    return parseJson<GlossaryData>(response);
}

export async function fetchNotificationPreferences(): Promise<NotificationPreferences> {
    const response = await fetch('/api/v1/notification-preferences', {
        credentials: 'same-origin',
    });

    return parseJson<NotificationPreferences>(response);
}

export async function updateNotificationPreferences(preferences: NotificationPreferences): Promise<void> {
    const response = await fetch('/api/v1/notification-preferences', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: new URLSearchParams({
            csrf_token: getCsrfToken(),
            attack_enabled: preferences.attack_enabled ? '1' : '0',
            spy_enabled: preferences.spy_enabled ? '1' : '0',
            alliance_enabled: preferences.alliance_enabled ? '1' : '0',
            system_enabled: preferences.system_enabled ? '1' : '0',
            push_notifications_enabled: preferences.push_notifications_enabled ? '1' : '0',
        }).toString(),
    });

    await parseJson<{ success: boolean }>(response);
}
