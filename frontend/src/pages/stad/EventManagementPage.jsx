import { useCallback, useEffect, useState } from 'react';
import { Award, Check, PartyPopper, QrCode, UserCheck, X } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { activityService } from '../../services/activityService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const eventVariants = {
    draft: 'neutral',
    published: 'success',
    cancelled: 'danger',
    completed: 'neutral',
};

const registrationVariants = {
    Pending: 'warning',
    Approved: 'success',
    Rejected: 'danger',
    Cancelled: 'neutral',
};

function formatDate(value) {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export function EventManagementPage() {
    const { notify } = useToast();

    const [events, setEvents] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [registrations, setRegistrations] = useState([]);
    const [qrSession, setQrSession] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        activityService
            .events()
            .then((data) => {
                setEvents(data);
                setSelectedId(data.length > 0 ? data[0].id : null);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load events.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const loadRegistrations = useCallback(() => {
        if (selectedId === null) {
            return Promise.resolve();
        }

        return activityService
            .eventRegistrations(selectedId)
            .then(setRegistrations)
            .catch((error) =>
                setNotice(readApiError(error, 'Unable to load registrations.').message),
            );
    }, [selectedId]);

    useEffect(() => {
        setQrSession(null);
        loadRegistrations();
    }, [loadRegistrations]);

    const act = async (action, successMessage) => {
        try {
            await action();
            await loadRegistrations();
            notify(successMessage, 'success');
        } catch (error) {
            notify(readApiError(error, 'That did not work.').message, 'error');
        }
    };

    const openQr = async () => {
        try {
            setQrSession(await activityService.openQr(selectedId));
            notify('QR attendance is open.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to open QR attendance.').message, 'error');
        }
    };

    const award = async (registration) => {
        const event = events.find((candidate) => candidate.id === selectedId);

        try {
            await activityService.awardPoints({
                student_id: registration.student_id,
                event_id: selectedId,
                points: event?.award_points > 0 ? event.award_points : 10,
            });
            notify('Activity points awarded.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to award points.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Event Management" subtitle="Registrations, attendance and points." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    if (events.length === 0) {
        return (
            <>
                <PageHeader title="Event Management" subtitle="Registrations, attendance and points." />
                <EmptyState
                    icon={PartyPopper}
                    title="No events yet"
                    description="Create an event through the API and it will appear here."
                />
            </>
        );
    }

    const selected = events.find((event) => event.id === selectedId);

    return (
        <>
            <PageHeader
                title="Event Management"
                subtitle="Registrations, attendance and activity points."
                actions={
                    selected?.qr_enabled ? (
                        <Button variant="secondary" icon={QrCode} onClick={openQr}>
                            Open QR attendance
                        </Button>
                    ) : null
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {qrSession && (
                <Alert variant="success">
                    QR attendance is open. Code <strong>{qrSession.qr_token}</strong>, valid for{' '}
                    {Math.round(qrSession.seconds_remaining / 60)} more minutes.
                </Alert>
            )}

            <div className="nsu-tabs" role="tablist">
                {events.map((event) => (
                    <button
                        type="button"
                        role="tab"
                        key={event.id}
                        aria-selected={event.id === selectedId}
                        className={`nsu-tab${event.id === selectedId ? ' nsu-tab--active' : ''}`}
                        onClick={() => setSelectedId(event.id)}
                    >
                        {event.event_name}
                    </button>
                ))}
            </div>

            {selected && (
                <div className="nsu-card nsu-event-summary">
                    <div>
                        <h2 className="nsu-section-title">{selected.event_name}</h2>
                        <p className="nsu-event-summary__meta">
                            {formatDate(selected.event_date)} · {selected.venue ?? 'Venue to confirm'} ·{' '}
                            {selected.registered_count} of {selected.maximum_participants} approved
                        </p>
                    </div>
                    <Badge variant={eventVariants[selected.status]}>{selected.status}</Badge>
                </div>
            )}

            <section className="nsu-card">
                <h2 className="nsu-section-title">Registrations</h2>

                {registrations.length === 0 ? (
                    <EmptyState
                        title="Nobody has registered yet"
                        description="Registrations appear here as students sign up."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                    <th>Attended</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {registrations.map((registration) => (
                                    <tr key={registration.id}>
                                        <td>
                                            {registration.student_name}
                                            <span className="nsu-table__hint">
                                                {registration.student_number}
                                            </span>
                                        </td>
                                        <td>{registration.registration_date?.slice(0, 10)}</td>
                                        <td>
                                            <Badge
                                                variant={registrationVariants[registration.status]}
                                            >
                                                {registration.status}
                                            </Badge>
                                        </td>
                                        <td>{registration.attendance_time ? 'Yes' : 'No'}</td>
                                        <td>
                                            <div className="nsu-table__actions">
                                                {registration.status === 'Pending' && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            icon={Check}
                                                            onClick={() =>
                                                                act(
                                                                    () =>
                                                                        activityService.approve(
                                                                            registration.id,
                                                                        ),
                                                                    'Registration approved.',
                                                                )
                                                            }
                                                        >
                                                            Approve
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            icon={X}
                                                            onClick={() =>
                                                                act(
                                                                    () =>
                                                                        activityService.reject(
                                                                            registration.id,
                                                                            'Not eligible.',
                                                                        ),
                                                                    'Registration rejected.',
                                                                )
                                                            }
                                                        >
                                                            Reject
                                                        </Button>
                                                    </>
                                                )}

                                                {registration.status === 'Approved' &&
                                                    !registration.attendance_time && (
                                                        <Button
                                                            variant="ghost"
                                                            icon={UserCheck}
                                                            onClick={() =>
                                                                act(
                                                                    () =>
                                                                        activityService.recordAttendance(
                                                                            registration.id,
                                                                        ),
                                                                    'Attendance recorded.',
                                                                )
                                                            }
                                                        >
                                                            Attended
                                                        </Button>
                                                    )}

                                                {registration.attendance_time && (
                                                    <Button
                                                        variant="ghost"
                                                        icon={Award}
                                                        onClick={() => award(registration)}
                                                    >
                                                        Award points
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </>
    );
}
