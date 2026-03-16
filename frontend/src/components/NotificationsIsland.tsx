import { useEffect, useMemo, useState } from 'react';
import {
    fetchNotificationPreferences,
    fetchNotifications,
    markAllNotificationsRead,
    markNotificationRead,
    updateNotificationPreferences,
    type NotificationPreferences,
    type NotificationsApiResponse,
    type NotificationItem,
} from '../lib/api';

interface Props {
    initialPage: number;
}

export function NotificationsIsland({ initialPage }: Props) {
    const [data, setData] = useState<NotificationsApiResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [status, setStatus] = useState<string | null>(null);
    const [preferences, setPreferences] = useState<NotificationPreferences | null>(null);
    const [prefsLoading, setPrefsLoading] = useState(true);
    const [prefsSaving, setPrefsSaving] = useState(false);

    const pagination = useMemo(() => data?.pagination, [data]);

    const load = async (page: number): Promise<void> => {
        setLoading(true);
        setError(null);
        try {
            const next = await fetchNotifications(page);
            setData(next);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to load notifications.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void load(initialPage);
    }, [initialPage]);

    useEffect(() => {
        window.dispatchEvent(new Event('notifications-spa-mounted'));
    }, []);

    useEffect(() => {
        const loadPreferences = async (): Promise<void> => {
            setPrefsLoading(true);
            try {
                const next = await fetchNotificationPreferences();
                setPreferences(next);
            } catch {
                setError('Failed to load notification preferences.');
            } finally {
                setPrefsLoading(false);
            }
        };

        void loadPreferences();
    }, []);

    const onMarkRead = async (id: number): Promise<void> => {
        try {
            await markNotificationRead(id);
            if (pagination) {
                await load(pagination.current_page);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to mark notification as read.');
        }
    };

    const onMarkAllRead = async (): Promise<void> => {
        if (!window.confirm('Mark all notifications as read?')) {
            return;
        }

        try {
            await markAllNotificationsRead();
            setStatus('All notifications marked as read.');
            if (pagination) {
                await load(pagination.current_page);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to mark all notifications as read.');
        }
    };

    const onEnablePush = async (): Promise<void> => {
        if (!('Notification' in window)) {
            setError('Push notifications are not supported in this browser.');
            return;
        }

        setError(null);
        const permission = await window.Notification.requestPermission();

        if (permission === 'granted') {
            setStatus('Push notifications enabled for this browser.');
            setPreferences((current) => {
                if (!current) {
                    return current;
                }

                return {
                    ...current,
                    push_notifications_enabled: true,
                };
            });
            return;
        }

        if (permission === 'denied') {
            setError('Push notifications were denied in browser settings.');
            return;
        }

        setStatus('Push notification permission was dismissed.');
    };

    const formatTimestamp = (item: NotificationItem): string => {
        const parsed = new Date(item.created_at.replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) {
            return item.created_at;
        }

        return parsed.toLocaleString(undefined, {
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getTypeMeta = (type: string): { iconClass: string; typeClass: string; label: string } => {
        switch (type) {
            case 'attack':
                return { iconClass: 'fa-crosshairs', typeClass: 'type-attack', label: 'Attack' };
            case 'spy':
                return { iconClass: 'fa-user-secret', typeClass: 'type-spy', label: 'Espionage' };
            case 'alliance':
                return { iconClass: 'fa-users', typeClass: 'type-alliance', label: 'Alliance' };
            default:
                return { iconClass: 'fa-info', typeClass: 'type-system', label: 'System' };
        }
    };

    const onPreferenceToggle = (key: keyof NotificationPreferences): void => {
        setPreferences((current) => {
            if (!current) {
                return current;
            }

            return {
                ...current,
                [key]: !current[key],
            };
        });
    };

    const onSavePreferences = async (): Promise<void> => {
        if (!preferences) {
            return;
        }

        setPrefsSaving(true);
        setError(null);

        if (preferences.push_notifications_enabled) {
            if (!('Notification' in window)) {
                setError('Push notifications are not supported in this browser.');
                setPrefsSaving(false);
                return;
            }

            if (window.Notification.permission === 'denied') {
                setError('Push notifications were denied in browser settings.');
                setPrefsSaving(false);
                return;
            }

            if (window.Notification.permission !== 'granted') {
                const permission = await window.Notification.requestPermission();

                if (permission !== 'granted') {
                    setError('Push notifications were not enabled in this browser.');
                    setPrefsSaving(false);
                    return;
                }
            }
        }

        try {
            await updateNotificationPreferences(preferences);
            setStatus('Notification preferences saved.');
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to save notification preferences.');
        } finally {
            setPrefsSaving(false);
        }
    };

    return (
        <section aria-live="polite" aria-labelledby="notifications-spa-heading">
            <div className="flex-between mb-1">
                <h1 id="notifications-spa-heading" className="notifications-spa-heading">
                    Command Uplink
                </h1>
            </div>

            <div className="notifications-spa-toolbar">
                <button className="btn-submit" onClick={() => void onEnablePush()} type="button">
                    Push
                </button>
                <button className="btn-submit btn-accent" onClick={() => void onMarkAllRead()} type="button">
                    Mark All Read
                </button>
            </div>

            <section className="notifications-spa-preferences" aria-labelledby="notifications-prefs-heading">
                <h2 id="notifications-prefs-heading" className="notifications-spa-subheading">
                    Push Notification Preferences
                </h2>

                {prefsLoading ? <p className="notifications-spa-status">Loading preferences...</p> : null}

                {!prefsLoading && preferences ? (
                    <fieldset className="notifications-spa-preferences-grid">
                        <legend className="notifications-spa-sr-only">Choose push notification types</legend>

                        <label className="notifications-spa-checkbox-label">
                            <input
                                type="checkbox"
                                checked={preferences.attack_enabled}
                                onChange={() => onPreferenceToggle('attack_enabled')}
                            />
                            Attack
                        </label>
                        <label className="notifications-spa-checkbox-label">
                            <input
                                type="checkbox"
                                checked={preferences.spy_enabled}
                                onChange={() => onPreferenceToggle('spy_enabled')}
                            />
                            Espionage
                        </label>
                        <label className="notifications-spa-checkbox-label">
                            <input
                                type="checkbox"
                                checked={preferences.alliance_enabled}
                                onChange={() => onPreferenceToggle('alliance_enabled')}
                            />
                            Alliance
                        </label>
                        <label className="notifications-spa-checkbox-label">
                            <input
                                type="checkbox"
                                checked={preferences.system_enabled}
                                onChange={() => onPreferenceToggle('system_enabled')}
                            />
                            System
                        </label>
                        <label className="notifications-spa-checkbox-label notifications-spa-master-toggle">
                            <input
                                type="checkbox"
                                checked={preferences.push_notifications_enabled}
                                onChange={() => onPreferenceToggle('push_notifications_enabled')}
                            />
                            Enable Browser Push Notifications
                        </label>

                        <button
                            className="btn-submit"
                            type="button"
                            onClick={() => void onSavePreferences()}
                            disabled={prefsSaving}
                        >
                            {prefsSaving ? 'Saving...' : 'Save Preferences'}
                        </button>
                    </fieldset>
                ) : null}
            </section>

            {loading ? <p className="notifications-spa-status">Loading notifications...</p> : null}
            {error ? <p className="notifications-spa-status" role="alert">{error}</p> : null}
            {status ? <p className="notifications-spa-status" role="status">{status}</p> : null}

            {!loading && !error && data && (
                <div>
                    <p className="notifications-spa-status">
                        Showing {data.notifications.length} items on page {data.pagination.current_page} of {data.pagination.total_pages}
                    </p>
                    {data.notifications.length === 0 ? (
                        <div className="notifications-spa-empty" role="status">
                            <i className="fas fa-inbox notifications-spa-empty-icon" aria-hidden="true"></i>
                            <p>No communications in log.</p>
                        </div>
                    ) : null}

                    <ul className="notifications-spa-list">
                        {data.notifications.map((item) => {
                            const typeMeta = getTypeMeta(item.type);

                            return (
                                <li
                                    key={item.id}
                                    className={`notification-item notifications-spa-item ${item.is_read ? 'read' : 'unread'}`}
                                >
                                    <div className={`notif-icon ${typeMeta.typeClass}`} title={typeMeta.label}>
                                        <i className={`fas ${typeMeta.iconClass}`} aria-hidden="true"></i>
                                        <span className="notifications-spa-sr-only">{typeMeta.label} notification</span>
                                    </div>

                                    <div className="notifications-spa-item-content">
                                        <div className="notifications-spa-item-head">
                                            <strong>{item.title}</strong>
                                            <small className="notifications-spa-time">{formatTimestamp(item)}</small>
                                        </div>

                                        <p className="notifications-spa-message">{item.message}</p>

                                        <div className="item-actions notifications-spa-actions">
                                            {item.link ? (
                                                <a href={item.link} className="btn-submit btn-accent">
                                                    View Report
                                                </a>
                                            ) : null}

                                            {!item.is_read ? (
                                                <button
                                                    type="button"
                                                    className="btn-submit notifications-spa-mark-read"
                                                    onClick={() => void onMarkRead(item.id)}
                                                >
                                                    Mark Read
                                                </button>
                                            ) : null}
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>

                    <div className="notifications-spa-toolbar" style={{ justifyContent: 'space-between' }}>
                        <button
                            type="button"
                            className="btn-submit"
                            onClick={() => pagination?.has_previous && void load(pagination.current_page - 1)}
                            disabled={!pagination?.has_previous}
                        >
                            <i className="fas fa-chevron-left" aria-hidden="true"></i> Previous
                        </button>
                        <button
                            type="button"
                            className="btn-submit"
                            onClick={() => pagination?.has_next && void load(pagination.current_page + 1)}
                            disabled={!pagination?.has_next}
                        >
                            Next <i className="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            )}
        </section>
    );
}
