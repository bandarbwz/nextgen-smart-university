import { useEffect, useState } from 'react';
import { CalendarDays, MapPin } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';

const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

function formatTime(value) {
    return value ? value.slice(0, 5) : '';
}

export function SchedulePage() {
    const [schedule, setSchedule] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        academicService
            .schedule()
            .then((data) => setSchedule(Array.isArray(data) ? {} : data))
            .catch((error) => setNotice(readApiError(error, 'Unable to load your schedule.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const hasClasses = weekDays.some((day) => (schedule[day] ?? []).length > 0);

    return (
        <>
            <PageHeader
                title="My schedule"
                subtitle="Your approved classes for the current semester."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {isLoading ? (
                <SkeletonRows rows={3} height={110} />
            ) : !hasClasses ? (
                <div className="nsu-card">
                    <EmptyState
                        icon={CalendarDays}
                        title="No approved classes yet"
                        description="Once a coordinator approves your registration, your weekly timetable appears here."
                    />
                </div>
            ) : (
                <div className="nsu-grid nsu-grid--cards">
                    {weekDays.map((day) => {
                        const slots = schedule[day] ?? [];

                        return (
                            <section key={day} className="nsu-day-column">
                                <h2 className="nsu-day-column__head">{day}</h2>

                                {slots.length === 0 ? (
                                    <p
                                        style={{
                                            color: 'var(--color-muted-foreground)',
                                            fontSize: 'var(--text-sm)',
                                            margin: 0,
                                        }}
                                    >
                                        No classes
                                    </p>
                                ) : (
                                    slots.map((slot, index) => (
                                        <article
                                            key={`${slot.course_code}-${index}`}
                                            className="nsu-class-card"
                                        >
                                            <p className="nsu-class-card__code">
                                                {slot.course_code} - {slot.section_number}
                                            </p>
                                            <p className="nsu-class-card__meta">{slot.course_name}</p>
                                            <p className="nsu-class-card__meta tabular">
                                                {formatTime(slot.start_time)} - {formatTime(slot.end_time)}
                                            </p>
                                            {(slot.room || slot.building) && (
                                                <p className="nsu-class-card__meta">
                                                    <MapPin
                                                        size={12}
                                                        aria-hidden="true"
                                                        style={{ verticalAlign: '-2px' }}
                                                    />{' '}
                                                    {[slot.building, slot.room].filter(Boolean).join(' ')}
                                                </p>
                                            )}
                                        </article>
                                    ))
                                )}
                            </section>
                        );
                    })}
                </div>
            )}
        </>
    );
}
