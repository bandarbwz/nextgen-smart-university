import { useCallback, useEffect, useState } from 'react';
import { Archive, Bell, CheckCheck, Megaphone, Settings, Trash2 } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Badge } from '../components/Badge';
import { Alert } from '../components/Alert';
import { Button } from '../components/Button';
import { notificationService } from '../services/notificationService';
import { readApiError } from '../services/apiClient';
import { useToast } from '../hooks/useToast';

const typeVariants = {
    info: 'neutral',
    success: 'success',
    warning: 'warning',
    error: 'danger',
};

function relativeTime(value) {
    const then = new Date(`${value}Z`).getTime();
    const minutes = Math.round((Date.now() - then) / 60000);

    if (minutes < 1) {
        return 'just now';
    }

    if (minutes < 60) {
        return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
    }

    const hours = Math.round(minutes / 60);

    if (hours < 24) {
        return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    }

    return new Date(`${value}Z`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
    });
}

export function NotificationsPage() {
    const { notify } = useToast();

    const [notifications, setNotifications] = useState([]);
    const [announcements, setAnnouncements] = useState([]);
    const [preferences, setPreferences] = useState(null);
    const [unread, setUnread] = useState(0);
    const [tab, setTab] = useState('inbox');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const load = useCallback(
        (archived = false) =>
            Promise.all([
                notificationService.list(archived ? { archived: 'true' } : {}),
                notificationService.announcements(),
                notificationService.preferences(),
            ]).then(([inbox, announcementData, preferenceData]) => {
                setNotifications(inbox.notifications);
                setUnread(inbox.unread_count);
                setAnnouncements(announcementData);
                setPreferences(preferenceData);
            }),
        [],
    );

    useEffect(() => {
        load()
            .catch((error) => setNotice(readApiError(error, 'Unable to load notifications.').message))
            .finally(() => setIsLoading(false));
    }, [load]);

    const act = async (action, message) => {
        try {
            await action();
            await load(tab === 'archived');
            notify(message, 'success');
        } catch (error) {
            notify(readApiError(error, 'That did not work.').message, 'error');
        }
    };

    const switchTab = async (next) => {
        setTab(next);

        if (next === 'inbox' || next === 'archived') {
            await load(next === 'archived');
        }
    };

    const savePreference = async (key, value) => {
        try {
            setPreferences(
                await notificationService.updatePreferences({ ...preferences, [key]: value }),
            );
            notify('Preferences updated.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to save preferences.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Notifications" subtitle="Everything the platform wants to tell you." />
                <SkeletonRows rows={4} height={72} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Notifications"
                subtitle="Everything the platform wants to tell you."
                actions={
                    unread > 0 ? (
                        <Button
                            variant="secondary"
                            icon={CheckCheck}
                            onClick={() =>
                                act(() => notificationService.markAllRead(), 'All marked as read.')
                            }
                        >
                            Mark all read
                        </Button>
                    ) : null
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist">
                {[
                    ['inbox', `Inbox${unread > 0 ? ` (${unread})` : ''}`],
                    ['archived', 'Archived'],
                    ['announcements', `Announcements (${announcements.length})`],
                    ['settings', 'Settings'],
                ].map(([key, label]) => (
                    <button
                        type="button"
                        role="tab"
                        key={key}
                        aria-selected={tab === key}
                        className={`nsu-tab${tab === key ? ' nsu-tab--active' : ''}`}
                        onClick={() => switchTab(key)}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {(tab === 'inbox' || tab === 'archived') &&
                (notifications.length === 0 ? (
                    <EmptyState
                        icon={Bell}
                        title={tab === 'inbox' ? 'Nothing new' : 'Nothing archived'}
                        description={
                            tab === 'inbox'
                                ? 'Notifications about your courses, fees, examinations and events appear here.'
                                : 'Notifications you archive are kept here.'
                        }
                    />
                ) : (
                    <ul className="nsu-notification-list">
                        {notifications.map((item) => (
                            <li
                                className={`nsu-notification${Number(item.is_read) === 0 ? ' nsu-notification--unread' : ''}`}
                                key={item.id}
                            >
                                <div className="nsu-notification__body">
                                    <p className="nsu-notification__title">
                                        {item.title}
                                        <Badge variant={typeVariants[item.notification_type]}>
                                            {item.module}
                                        </Badge>
                                        {item.priority === 'Critical' && (
                                            <Badge variant="danger">Critical</Badge>
                                        )}
                                    </p>
                                    <p className="nsu-notification__message">{item.message}</p>
                                    <p className="nsu-notification__time">
                                        {relativeTime(item.created_at)}
                                    </p>
                                </div>

                                <div className="nsu-notification__actions">
                                    {Number(item.is_read) === 0 && (
                                        <Button
                                            variant="ghost"
                                            onClick={() =>
                                                act(
                                                    () => notificationService.markRead(item.id),
                                                    'Marked as read.',
                                                )
                                            }
                                        >
                                            Read
                                        </Button>
                                    )}

                                    {tab === 'inbox' && (
                                        <Button
                                            variant="ghost"
                                            icon={Archive}
                                            onClick={() =>
                                                act(
                                                    () => notificationService.archive(item.id),
                                                    'Archived.',
                                                )
                                            }
                                        >
                                            Archive
                                        </Button>
                                    )}

                                    <Button
                                        variant="ghost"
                                        icon={Trash2}
                                        onClick={() =>
                                            act(
                                                () => notificationService.remove(item.id),
                                                'Deleted.',
                                            )
                                        }
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                ))}

            {tab === 'announcements' &&
                (announcements.length === 0 ? (
                    <EmptyState
                        icon={Megaphone}
                        title="No announcements"
                        description="University wide announcements appear here."
                    />
                ) : (
                    <div className="nsu-event-list">
                        {announcements.map((announcement) => (
                            <article className="nsu-card" key={announcement.id}>
                                <p className="nsu-notification__title">
                                    {announcement.title}
                                    <Badge variant="neutral">{announcement.audience}</Badge>
                                    {announcement.priority === 'Critical' && (
                                        <Badge variant="danger">Critical</Badge>
                                    )}
                                </p>
                                <p className="nsu-notification__message">{announcement.content}</p>
                                <p className="nsu-notification__time">
                                    {announcement.published_by_name}
                                    {announcement.published_at
                                        ? ` · ${relativeTime(announcement.published_at)}`
                                        : ' · draft'}
                                </p>
                            </article>
                        ))}
                    </div>
                ))}

            {tab === 'settings' && preferences && (
                <div className="nsu-card">
                    <h2 className="nsu-section-title">
                        <Settings size={16} aria-hidden="true" /> Delivery
                    </h2>

                    <ul className="nsu-preference-list">
                        <li className="nsu-preference">
                            <span>
                                <strong>In app</strong>
                                <span className="nsu-table__hint">
                                    Notifications in this centre
                                </span>
                            </span>
                            <label className="nsu-switch">
                                <input
                                    type="checkbox"
                                    checked={Number(preferences.in_app_enabled) === 1}
                                    onChange={(event) =>
                                        savePreference('in_app_enabled', event.target.checked)
                                    }
                                />
                                <span>{Number(preferences.in_app_enabled) === 1 ? 'On' : 'Off'}</span>
                            </label>
                        </li>

                        <li className="nsu-preference">
                            <span>
                                <strong>Email</strong>
                                <span className="nsu-table__hint">
                                    Sent for high priority and critical notifications only
                                </span>
                            </span>
                            <label className="nsu-switch">
                                <input
                                    type="checkbox"
                                    checked={Number(preferences.email_enabled) === 1}
                                    onChange={(event) =>
                                        savePreference('email_enabled', event.target.checked)
                                    }
                                />
                                <span>{Number(preferences.email_enabled) === 1 ? 'On' : 'Off'}</span>
                            </label>
                        </li>

                        <li className="nsu-preference nsu-preference--disabled">
                            <span>
                                <strong>Push</strong>
                                <span className="nsu-table__hint">
                                    Not available. No push provider is configured.
                                </span>
                            </span>
                            <span className="nsu-table__hint">Unavailable</span>
                        </li>
                    </ul>

                    <Alert variant="info">
                        Critical notifications, such as a terminated examination or a financial hold,
                        are always delivered regardless of these settings.
                    </Alert>
                </div>
            )}
        </>
    );
}
