import { useEffect, useMemo, useState } from 'react';
import {
    fetchNotifications,
    markAllNotificationsRead,
    markNotificationRead,
    type NotificationsApiResponse,
} from '../lib/api';

interface Props {
    initialPage: number;
}

export function NotificationsIsland({ initialPage }: Props) {
    const [data, setData] = useState<NotificationsApiResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

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
        try {
            await markAllNotificationsRead();
            if (pagination) {
                await load(pagination.current_page);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to mark all notifications as read.');
        }
    };

    return (
        <section aria-live="polite">
            <div className="notifications-spa-toolbar">
                <button className="btn-submit btn-accent" onClick={() => void onMarkAllRead()} type="button">
                    Mark All Read (SPA)
                </button>
            </div>

            {loading ? <p className="notifications-spa-status">Loading notifications...</p> : null}
            {error ? <p className="notifications-spa-status">{error}</p> : null}

            {!loading && !error && data && (
                <div>
                    <p className="notifications-spa-status">
                        Showing {data.notifications.length} items on page {data.pagination.current_page} of {data.pagination.total_pages}
                    </p>
                    <ul>
                        {data.notifications.map((item) => (
                            <li key={item.id} style={{ marginBottom: '0.75rem' }}>
                                <strong>{item.title}</strong> — {item.message}
                                {!item.is_read ? (
                                    <button
                                        type="button"
                                        className="btn-submit"
                                        style={{ marginLeft: '0.5rem' }}
                                        onClick={() => void onMarkRead(item.id)}
                                    >
                                        Mark Read
                                    </button>
                                ) : null}
                            </li>
                        ))}
                    </ul>

                    <div className="notifications-spa-toolbar" style={{ justifyContent: 'space-between' }}>
                        <button
                            type="button"
                            className="btn-submit"
                            onClick={() => pagination?.has_previous && void load(pagination.current_page - 1)}
                            disabled={!pagination?.has_previous}
                        >
                            Previous
                        </button>
                        <button
                            type="button"
                            className="btn-submit"
                            onClick={() => pagination?.has_next && void load(pagination.current_page + 1)}
                            disabled={!pagination?.has_next}
                        >
                            Next
                        </button>
                    </div>
                </div>
            )}
        </section>
    );
}
