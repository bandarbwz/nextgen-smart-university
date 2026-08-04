import { useCallback, useEffect, useMemo, useState } from 'react';
import { Award, CalendarDays, MapPin, PartyPopper, Users } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { activityService } from '../../services/activityService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const registrationVariants = {
    Pending: 'warning',
    Approved: 'success',
    Rejected: 'danger',
    Cancelled: 'neutral',
};

function formatDate(value) {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function formatTime(value) {
    return (value ?? '').slice(0, 5);
}

export function ActivitiesPage() {
    const { notify } = useToast();

    const [events, setEvents] = useState([]);
    const [registrations, setRegistrations] = useState([]);
    const [points, setPoints] = useState({ total_points: 0, points: [] });
    const [tab, setTab] = useState('events');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const load = useCallback(
        () =>
            Promise.all([
                activityService.events(),
                activityService.myRegistrations(),
                activityService.myPoints(),
            ]).then(([eventData, registrationData, pointData]) => {
                setEvents(eventData);
                setRegistrations(registrationData);
                setPoints(pointData);
            }),
        [],
    );

    useEffect(() => {
        load()
            .catch((error) => setNotice(readApiError(error, 'Unable to load activities.').message))
            .finally(() => setIsLoading(false));
    }, [load]);

    const upcoming = useMemo(
        () => events.filter((event) => event.status === 'published'),
        [events],
    );

    const act = async (action, successMessage) => {
        try {
            await action();
            await load();
            notify(successMessage, 'success');
        } catch (error) {
            notify(readApiError(error, 'That did not work.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Student Activities" subtitle="Clubs, events and activity points." />
                <SkeletonRows rows={4} height={90} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Student Activities"
                subtitle="Clubs, events and activity points."
                actions={
                    <div className="nsu-points-chip">
                        <Award size={18} aria-hidden="true" />
                        <span className="tabular">{points.total_points}</span>
                        <span className="nsu-points-chip__label">points</span>
                    </div>
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist">
                {[
                    ['events', `Events (${upcoming.length})`],
                    ['mine', `My registrations (${registrations.length})`],
                    ['points', `Points (${points.points.length})`],
                ].map(([key, label]) => (
                    <button
                        type="button"
                        role="tab"
                        key={key}
                        aria-selected={tab === key}
                        className={`nsu-tab${tab === key ? ' nsu-tab--active' : ''}`}
                        onClick={() => setTab(key)}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'events' &&
                (upcoming.length === 0 ? (
                    <EmptyState
                        icon={PartyPopper}
                        title="No events yet"
                        description="Events appear here once student affairs publishes them."
                    />
                ) : (
                    <div className="nsu-event-list">
                        {upcoming.map((event) => (
                            <article className="nsu-card nsu-event-card" key={event.id}>
                                <div className="nsu-event-card__body">
                                    <div className="nsu-event-card__heading">
                                        <h2 className="nsu-event-card__title">{event.event_name}</h2>
                                        <Badge variant="neutral">{event.event_type}</Badge>
                                    </div>

                                    {event.club_name && (
                                        <p className="nsu-event-card__club">{event.club_name}</p>
                                    )}

                                    <p className="nsu-event-card__meta">
                                        <span>
                                            <CalendarDays size={14} aria-hidden="true" />
                                            {formatDate(event.event_date)} ·{' '}
                                            {formatTime(event.start_time)}
                                        </span>
                                        {event.venue && (
                                            <span>
                                                <MapPin size={14} aria-hidden="true" />
                                                {event.venue}
                                            </span>
                                        )}
                                        <span>
                                            <Users size={14} aria-hidden="true" />
                                            {event.seats_remaining} of {event.maximum_participants}{' '}
                                            left
                                        </span>
                                        {event.award_points > 0 && (
                                            <span>
                                                <Award size={14} aria-hidden="true" />
                                                {event.award_points} points
                                            </span>
                                        )}
                                    </p>

                                    {event.description && (
                                        <p className="nsu-event-card__description">
                                            {event.description}
                                        </p>
                                    )}
                                </div>

                                <div className="nsu-event-card__action">
                                    {event.my_registration_status ? (
                                        <Badge
                                            variant={
                                                registrationVariants[event.my_registration_status]
                                            }
                                        >
                                            {event.my_registration_status}
                                        </Badge>
                                    ) : event.seats_remaining === 0 ? (
                                        <Badge variant="danger">Full</Badge>
                                    ) : (
                                        <Button
                                            variant="primary"
                                            onClick={() =>
                                                act(
                                                    () => activityService.register(event.id),
                                                    'Registration submitted.',
                                                )
                                            }
                                        >
                                            Register
                                        </Button>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                ))}

            {tab === 'mine' &&
                (registrations.length === 0 ? (
                    <EmptyState
                        icon={CalendarDays}
                        title="You have not registered for anything"
                        description="Register for an event and it will appear here."
                    />
                ) : (
                    <div className="nsu-card">
                        <div className="nsu-table-wrap">
                            <table className="nsu-table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Attended</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {registrations.map((registration) => (
                                        <tr key={registration.id}>
                                            <td>
                                                {registration.event_name}
                                                <span className="nsu-table__hint">
                                                    {registration.club_name ?? ''}
                                                </span>
                                            </td>
                                            <td>{formatDate(registration.event_date)}</td>
                                            <td>
                                                <Badge
                                                    variant={
                                                        registrationVariants[registration.status]
                                                    }
                                                >
                                                    {registration.status}
                                                </Badge>
                                            </td>
                                            <td>{registration.attendance_time ? 'Yes' : 'No'}</td>
                                            <td>
                                                {registration.status !== 'Cancelled' &&
                                                    !registration.attendance_time && (
                                                        <Button
                                                            variant="ghost"
                                                            onClick={() =>
                                                                act(
                                                                    () =>
                                                                        activityService.cancelRegistration(
                                                                            registration.id,
                                                                        ),
                                                                    'Registration cancelled.',
                                                                )
                                                            }
                                                        >
                                                            Cancel
                                                        </Button>
                                                    )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}

            {tab === 'points' &&
                (points.points.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No activity points yet"
                        description="Attend an event and points are awarded after your attendance is verified."
                    />
                ) : (
                    <div className="nsu-card">
                        <div className="nsu-table-wrap">
                            <table className="nsu-table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Awarded</th>
                                        <th className="nsu-table__number">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {points.points.map((point) => (
                                        <tr key={point.id}>
                                            <td>
                                                {point.event_name}
                                                <span className="nsu-table__hint">
                                                    {point.club_name ?? ''}
                                                </span>
                                            </td>
                                            <td>{formatDate(point.awarded_date)}</td>
                                            <td className="nsu-table__number tabular">
                                                {point.points}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}
        </>
    );
}
