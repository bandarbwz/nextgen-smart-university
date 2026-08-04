import { useCallback, useEffect, useState } from 'react';
import { AlertTriangle, FileText, PauseCircle, PlayCircle, ShieldCheck } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { examService } from '../../services/examService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const REFRESH_MS = 10000;

const sessionVariants = {
    active: 'success',
    paused: 'warning',
    submitted: 'neutral',
    terminated: 'danger',
    expired: 'neutral',
};

const severityVariants = {
    critical: 'danger',
    warning: 'warning',
    info: 'neutral',
};

function integrityVariant(score) {
    if (score >= 85) {
        return 'success';
    }

    return score >= 60 ? 'warning' : 'danger';
}

function moment(value) {
    return value === null ? '—' : new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'short',
        timeStyle: 'medium',
    });
}

export function ExamMonitorPage() {
    const { notify } = useToast();

    const [examinations, setExaminations] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [sessions, setSessions] = useState([]);
    const [violations, setViolations] = useState([]);
    const [report, setReport] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        examService
            .list()
            .then((data) => {
                setExaminations(data);
                setSelectedId(data.length > 0 ? data[0].id : null);
            })
            .catch((error) =>
                setNotice(readApiError(error, 'Unable to load your examinations.').message),
            )
            .finally(() => setIsLoading(false));
    }, []);

    const load = useCallback(() => {
        if (selectedId === null) {
            return;
        }

        Promise.all([
            examService.sessionsForExam(selectedId),
            examService.violationsForExam(selectedId),
        ])
            .then(([sessionData, violationData]) => {
                setSessions(sessionData);
                setViolations(violationData);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load the sessions.').message));
    }, [selectedId]);

    useEffect(() => {
        setReport(null);
        load();

        const tick = () => {
            if (!document.hidden) {
                load();
            }
        };

        const timer = setInterval(tick, REFRESH_MS);

        document.addEventListener('visibilitychange', tick);

        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', tick);
        };
    }, [load]);

    const act = async (action, sessionId) => {
        try {
            await action(sessionId);
            load();
        } catch (error) {
            notify(readApiError(error, 'The session could not be updated.').message, 'error');
        }
    };

    const generate = async (sessionId) => {
        try {
            setReport(await examService.generateReport(sessionId));
        } catch (error) {
            notify(readApiError(error, 'The report could not be generated.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Examination Monitor" subtitle="Live sessions and integrity." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    if (examinations.length === 0) {
        return (
            <>
                <PageHeader title="Examination Monitor" subtitle="Live sessions and integrity." />
                <EmptyState
                    icon={ShieldCheck}
                    title="No examinations yet"
                    description="Create an examination for one of your sections to monitor it here."
                />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Examination Monitor"
                subtitle="Live sessions, AI violations and integrity reports."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist">
                {examinations.map((exam) => (
                    <button
                        type="button"
                        role="tab"
                        key={exam.id}
                        aria-selected={exam.id === selectedId}
                        className={`nsu-tab${exam.id === selectedId ? ' nsu-tab--active' : ''}`}
                        onClick={() => setSelectedId(exam.id)}
                    >
                        {exam.course_code} · {exam.title}
                    </button>
                ))}
            </div>

            <section className="nsu-card">
                <h2 className="nsu-section-title">Sessions</h2>

                {sessions.length === 0 ? (
                    <EmptyState title="Nobody has started yet" description="Sessions appear as students begin." />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Started</th>
                                    <th>Status</th>
                                    <th>Identity</th>
                                    <th>Violations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sessions.map((session) => (
                                    <tr key={session.id}>
                                        <td>
                                            {session.student_name}
                                            <span className="nsu-table__hint">
                                                {session.student_number}
                                            </span>
                                        </td>
                                        <td>{moment(session.session_start)}</td>
                                        <td>
                                            <Badge variant={sessionVariants[session.status]}>
                                                {session.status}
                                            </Badge>
                                        </td>
                                        <td>
                                            {Number(session.identity_verified) === 1 ? (
                                                <Badge variant="success">Verified</Badge>
                                            ) : (
                                                <Badge variant="warning">Unverified</Badge>
                                            )}
                                        </td>
                                        <td className="tabular">{session.violation_count}</td>
                                        <td>
                                            <div className="nsu-table__actions">
                                                {session.status === 'active' && (
                                                    <Button
                                                        variant="ghost"
                                                        icon={PauseCircle}
                                                        onClick={() =>
                                                            act(examService.pauseSession, session.id)
                                                        }
                                                    >
                                                        Pause
                                                    </Button>
                                                )}

                                                {session.status === 'paused' && (
                                                    <Button
                                                        variant="ghost"
                                                        icon={PlayCircle}
                                                        onClick={() =>
                                                            act(examService.resumeSession, session.id)
                                                        }
                                                    >
                                                        Resume
                                                    </Button>
                                                )}

                                                <Button
                                                    variant="ghost"
                                                    icon={FileText}
                                                    onClick={() => generate(session.id)}
                                                >
                                                    Report
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            {report && (
                <section className="nsu-card nsu-exam-report">
                    <div className="nsu-exam-report__head">
                        <h2 className="nsu-section-title">
                            Integrity report · {report.student_name}
                        </h2>
                        <Badge variant={integrityVariant(Number(report.integrity_score))}>
                            {report.integrity_score} / 100
                        </Badge>
                    </div>

                    <p className="nsu-exam-report__summary">{report.summary}</p>

                    {Number(report.identity_verified) === 0 && (
                        <Alert variant="error">
                            This report cannot confirm who sat the examination.
                        </Alert>
                    )}
                </section>
            )}

            <section className="nsu-card">
                <h2 className="nsu-section-title">AI violations</h2>

                {violations.length === 0 ? (
                    <EmptyState
                        icon={ShieldCheck}
                        title="No violations recorded"
                        description="Everything the proctor has seen so far looks clean."
                    />
                ) : (
                    <ul className="nsu-violation-list">
                        {violations.map((violation) => (
                            <li className="nsu-violation" key={violation.id}>
                                <span
                                    className={`nsu-violation__mark nsu-violation__mark--${violation.severity}`}
                                    aria-hidden="true"
                                >
                                    <AlertTriangle size={14} />
                                </span>

                                <div className="nsu-violation__body">
                                    <p className="nsu-violation__title">
                                        {violation.violation_type}
                                        <Badge variant={severityVariants[violation.severity]}>
                                            {violation.severity}
                                        </Badge>
                                    </p>
                                    <p className="nsu-violation__meta">
                                        {violation.student_name} · {moment(violation.detected_at)}
                                        {violation.detail ? ` · ${violation.detail}` : ''}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}
