import { useCallback, useEffect, useMemo, useState } from 'react';
import { CalendarPlus, ChevronLeft, ChevronRight, Download, RefreshCw, Trash2 } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Button } from '../components/Button';
import { FormField } from '../components/FormField';
import { Alert } from '../components/Alert';
import { calendarService } from '../services/calendarService';
import { readApiError } from '../services/apiClient';
import { useToast } from '../hooks/useToast';

const weekDayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

function toDateKey(value) {
    return value.slice(0, 10);
}

function buildGrid(year, month) {
    const firstOfMonth = new Date(Date.UTC(year, month - 1, 1));
    const leadingBlanks = firstOfMonth.getUTCDay();
    const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();

    const cells = Array.from({ length: leadingBlanks }, () => null);

    for (let day = 1; day <= daysInMonth; day += 1) {
        cells.push(
            `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
        );
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
}

export function CalendarPage() {
    const { notify } = useToast();

    const today = new Date();
    const [year, setYear] = useState(today.getUTCFullYear());
    const [month, setMonth] = useState(today.getUTCMonth() + 1);
    const [events, setEvents] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isSyncing, setIsSyncing] = useState(false);
    const [selectedDate, setSelectedDate] = useState(null);
    const [notice, setNotice] = useState('');

    const [title, setTitle] = useState('');
    const [startTime, setStartTime] = useState('09:00');
    const [endTime, setEndTime] = useState('10:00');
    const [isSaving, setIsSaving] = useState(false);

    const load = useCallback(async () => {
        setIsLoading(true);

        try {
            setEvents(await calendarService.monthly(year, month));
            setNotice('');
        } catch (error) {
            setNotice(readApiError(error, 'Unable to load your calendar.').message);
        } finally {
            setIsLoading(false);
        }
    }, [year, month]);

    useEffect(() => {
        load();
    }, [load]);

    const eventsByDate = useMemo(() => {
        const grouped = {};

        events.forEach((event) => {
            const key = toDateKey(event.start_datetime);

            grouped[key] = grouped[key] ?? [];
            grouped[key].push(event);
        });

        return grouped;
    }, [events]);

    const cells = useMemo(() => buildGrid(year, month), [year, month]);

    function shiftMonth(delta) {
        const next = new Date(Date.UTC(year, month - 1 + delta, 1));

        setYear(next.getUTCFullYear());
        setMonth(next.getUTCMonth() + 1);
        setSelectedDate(null);
    }

    async function handleSync() {
        setIsSyncing(true);

        try {
            const result = await calendarService.sync();

            notify(
                `Synced ${result.classes} classes, ${result.assignments} assignments, ${result.quizzes} quizzes.`,
            );

            await load();
        } catch (error) {
            notify(readApiError(error, 'Sync failed.').message, 'error');
        } finally {
            setIsSyncing(false);
        }
    }

    async function handleCreate(event) {
        event.preventDefault();

        if (!title.trim() || !selectedDate) {
            return;
        }

        setIsSaving(true);

        try {
            await calendarService.create({
                title: title.trim(),
                start_datetime: `${selectedDate} ${startTime}:00`,
                end_datetime: `${selectedDate} ${endTime}:00`,
                event_type: 'Personal Event',
            });

            notify('Event added.');
            setTitle('');

            await load();
        } catch (error) {
            notify(readApiError(error, 'Unable to add the event.').message, 'error');
        } finally {
            setIsSaving(false);
        }
    }

    async function handleDelete(id) {
        try {
            await calendarService.remove(id);
            notify('Event deleted.');

            await load();
        } catch (error) {
            notify(readApiError(error, 'Unable to delete this event.').message, 'error');
        }
    }

    const selectedEvents = selectedDate ? eventsByDate[selectedDate] ?? [] : [];

    return (
        <>
            <PageHeader
                title="Calendar"
                subtitle="Your classes, deadlines and personal events in one place."
                actions={
                    <div style={{ display: 'flex', gap: 'var(--space-sm)', flexWrap: 'wrap' }}>
                        <Button
                            variant="secondary"
                            icon={RefreshCw}
                            isLoading={isSyncing}
                            onClick={handleSync}
                        >
                            Sync
                        </Button>
                        <Button
                            variant="secondary"
                            icon={Download}
                            onClick={() => calendarService.exportCalendar()}
                        >
                            Export
                        </Button>
                    </div>
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-calendar-toolbar">
                <Button variant="ghost" onClick={() => shiftMonth(-1)} aria-label="Previous month">
                    <ChevronLeft size={18} />
                </Button>
                <h2 className="nsu-calendar-title">
                    {monthNames[month - 1]} {year}
                </h2>
                <Button variant="ghost" onClick={() => shiftMonth(1)} aria-label="Next month">
                    <ChevronRight size={18} />
                </Button>
            </div>

            {isLoading ? (
                <SkeletonRows rows={5} height={72} />
            ) : (
                <div className="nsu-card nsu-calendar-card">
                    <div className="nsu-calendar-grid" role="grid" aria-label="Month view">
                        {weekDayLabels.map((label) => (
                            <div key={label} className="nsu-calendar-weekday" role="columnheader">
                                {label}
                            </div>
                        ))}

                        {cells.map((date, index) => {
                            if (date === null) {
                                return <div key={`blank-${index}`} className="nsu-calendar-cell--empty" />;
                            }

                            const dayEvents = eventsByDate[date] ?? [];
                            const isSelected = selectedDate === date;

                            return (
                                <button
                                    key={date}
                                    type="button"
                                    role="gridcell"
                                    className={`nsu-calendar-cell ${isSelected ? 'nsu-calendar-cell--selected' : ''}`}
                                    onClick={() => setSelectedDate(date)}
                                    aria-label={`${date}, ${dayEvents.length} events`}
                                    aria-pressed={isSelected}
                                >
                                    <span className="nsu-calendar-cell__day tabular">
                                        {Number(date.slice(8))}
                                    </span>
                                    <span className="nsu-calendar-cell__dots">
                                        {dayEvents.slice(0, 4).map((event) => (
                                            <span
                                                key={event.id}
                                                className="nsu-calendar-dot"
                                                style={{ background: event.color ?? 'var(--color-primary)' }}
                                            />
                                        ))}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}

            {selectedDate && (
                <div
                    className="nsu-grid"
                    style={{
                        gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))',
                        marginTop: 'var(--space-xl)',
                    }}
                >
                    <section className="nsu-card">
                        <div className="nsu-card__body">
                            <h2 className="nsu-section-title">{selectedDate}</h2>

                            {selectedEvents.length === 0 ? (
                                <EmptyState
                                    icon={CalendarPlus}
                                    title="Nothing scheduled"
                                    description="Add a personal event using the form beside this panel."
                                />
                            ) : (
                                <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                                    {selectedEvents.map((event) => (
                                        <li key={event.id} className="nsu-calendar-event">
                                            <span
                                                className="nsu-calendar-event__bar"
                                                style={{
                                                    background: event.color ?? 'var(--color-primary)',
                                                }}
                                            />
                                            <div style={{ flex: 1 }}>
                                                <p className="nsu-calendar-event__title">
                                                    {event.title}
                                                </p>
                                                <p className="nsu-calendar-event__meta tabular">
                                                    {event.start_datetime.slice(11, 16)} -{' '}
                                                    {event.end_datetime.slice(11, 16)}
                                                    {event.location ? ` | ${event.location}` : ''}
                                                </p>
                                            </div>
                                            {event.module === null && (
                                                <Button
                                                    variant="ghost"
                                                    icon={Trash2}
                                                    onClick={() => handleDelete(event.id)}
                                                    aria-label={`Delete ${event.title}`}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>

                    <section className="nsu-card">
                        <div className="nsu-card__body">
                            <h2 className="nsu-section-title">Add personal event</h2>

                            <form onSubmit={handleCreate}>
                                <FormField
                                    label="Title"
                                    value={title}
                                    onChange={(event) => setTitle(event.target.value)}
                                    required
                                />

                                <div style={{ display: 'flex', gap: 'var(--space-md)' }}>
                                    <div className="nsu-field" style={{ flex: 1 }}>
                                        <label className="nsu-field__label" htmlFor="event-start">
                                            Starts
                                        </label>
                                        <input
                                            id="event-start"
                                            className="nsu-field__input"
                                            type="time"
                                            value={startTime}
                                            onChange={(event) => setStartTime(event.target.value)}
                                        />
                                    </div>

                                    <div className="nsu-field" style={{ flex: 1 }}>
                                        <label className="nsu-field__label" htmlFor="event-end">
                                            Ends
                                        </label>
                                        <input
                                            id="event-end"
                                            className="nsu-field__input"
                                            type="time"
                                            value={endTime}
                                            onChange={(event) => setEndTime(event.target.value)}
                                        />
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    icon={CalendarPlus}
                                    isLoading={isSaving}
                                    disabled={!title.trim()}
                                >
                                    Add to {selectedDate}
                                </Button>
                            </form>
                        </div>
                    </section>
                </div>
            )}
        </>
    );
}
