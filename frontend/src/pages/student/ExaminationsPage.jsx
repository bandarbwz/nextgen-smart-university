import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CheckCircle2, ShieldCheck, Timer } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { examService } from '../../services/examService';
import { readApiError } from '../../services/apiClient';

function windowState(exam) {
    const now = Date.now();
    const start = new Date(`${exam.start_time}Z`).getTime();
    const end = new Date(`${exam.end_time}Z`).getTime();

    if (now < start) {
        return { label: 'Not open yet', variant: 'neutral', canSit: false };
    }

    if (now > end) {
        return { label: 'Closed', variant: 'neutral', canSit: false };
    }

    return { label: 'Open now', variant: 'success', canSit: true };
}

function formatMoment(value) {
    return new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function ExaminationsPage() {
    const [examinations, setExaminations] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        examService
            .list()
            .then(setExaminations)
            .catch((error) =>
                setNotice(readApiError(error, 'Unable to load your examinations.').message),
            )
            .finally(() => setIsLoading(false));
    }, []);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Examinations" subtitle="Your proctored online examinations." />
                <SkeletonRows rows={3} height={90} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Examinations"
                subtitle="Your proctored online examinations."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {examinations.length === 0 ? (
                <EmptyState
                    icon={ShieldCheck}
                    title="No examinations yet"
                    description="Examinations appear here once your lecturer publishes them."
                />
            ) : (
                <div className="nsu-exam-list">
                    {examinations.map((exam) => {
                        const state = windowState(exam);

                        return (
                            <article className="nsu-card nsu-exam-card" key={exam.id}>
                                <div className="nsu-exam-card__main">
                                    <div className="nsu-exam-card__heading">
                                        <h2 className="nsu-exam-card__title">{exam.title}</h2>
                                        <Badge variant={state.variant}>{state.label}</Badge>
                                    </div>

                                    <p className="nsu-exam-card__course">
                                        {exam.course_code} · {exam.course_name} · Section{' '}
                                        {exam.section_number}
                                    </p>

                                    <dl className="nsu-exam-card__facts">
                                        <div>
                                            <dt>Opens</dt>
                                            <dd>{formatMoment(exam.start_time)}</dd>
                                        </div>
                                        <div>
                                            <dt>Closes</dt>
                                            <dd>{formatMoment(exam.end_time)}</dd>
                                        </div>
                                        <div>
                                            <dt>Duration</dt>
                                            <dd className="tabular">{exam.duration} minutes</dd>
                                        </div>
                                        <div>
                                            <dt>Total marks</dt>
                                            <dd className="tabular">{exam.total_marks}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div className="nsu-exam-card__aside">
                                    {exam.submitted ? (
                                        <p className="nsu-exam-card__done">
                                            <CheckCircle2 size={16} aria-hidden="true" />
                                            {exam.score === null
                                                ? 'Submitted, awaiting marking'
                                                : `Scored ${exam.score} of ${exam.total_marks}`}
                                        </p>
                                    ) : state.canSit ? (
                                        <Link
                                            className="nsu-button nsu-button--primary"
                                            to={`/examinations/${exam.id}/sit`}
                                        >
                                            <Timer size={16} aria-hidden="true" />
                                            Start examination
                                        </Link>
                                    ) : (
                                        <p className="nsu-exam-card__done">Not available</p>
                                    )}

                                    {Number(exam.require_camera) === 1 && (
                                        <p className="nsu-exam-card__proctor">
                                            <ShieldCheck size={14} aria-hidden="true" />
                                            Camera proctoring required
                                        </p>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}
        </>
    );
}
