import { useCallback, useEffect, useState } from 'react';
import { Check, RotateCcw, ThumbsUp, X } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Badge } from '../components/Badge';
import { Alert } from '../components/Alert';
import { Button } from '../components/Button';
import { examResetService } from '../services/examResetService';
import { examService } from '../services/examService';
import { readApiError } from '../services/apiClient';
import { useAuth } from '../hooks/useAuth';
import { useToast } from '../hooks/useToast';

const statusVariants = {
    Pending: 'warning',
    Recommended: 'warning',
    Approved: 'success',
    Completed: 'success',
    Rejected: 'danger',
};

function moment(value) {
    return value === null ? '—' : new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function ExamResetPage() {
    const { user } = useAuth();
    const { notify } = useToast();

    const isStudent = user.role === 'Student';

    const [requests, setRequests] = useState([]);
    const [exams, setExams] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [detail, setDetail] = useState(null);
    const [remarks, setRemarks] = useState('');
    const [newExamId, setNewExamId] = useState('');
    const [reason, setReason] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const loadList = useCallback(
        () =>
            examResetService.list().then((data) => {
                setRequests(data);
                setSelectedId((current) =>
                    current !== null && data.some((item) => item.id === current)
                        ? current
                        : (data[0]?.id ?? null),
                );
            }),
        [],
    );

    useEffect(() => {
        const tasks = [loadList()];

        if (isStudent) {
            tasks.push(examService.list().then(setExams).catch(() => undefined));
        }

        Promise.all(tasks)
            .catch((error) => setNotice(readApiError(error, 'Unable to load reset requests.').message))
            .finally(() => setIsLoading(false));
    }, [loadList, isStudent]);

    useEffect(() => {
        if (selectedId === null) {
            setDetail(null);

            return;
        }

        examResetService
            .get(selectedId)
            .then((data) => {
                setDetail(data);
                setRemarks('');
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load the request.').message));
    }, [selectedId]);

    const submitRequest = async () => {
        if (newExamId === '' || reason.trim() === '') {
            notify('Choose the examination and say what happened.', 'error');

            return;
        }

        try {
            await examResetService.request(Number(newExamId), reason.trim());
            await loadList();
            setReason('');
            setNewExamId('');
            notify('Reset request submitted.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to submit the request.').message, 'error');
        }
    };

    const decide = async (action, label, needsRemarks) => {
        if (needsRemarks && remarks.trim() === '') {
            notify('Remarks are required so the student knows why.', 'error');

            return;
        }

        try {
            setDetail(await action(selectedId, remarks.trim() || null));
            await loadList();
            notify(label, 'success');
        } catch (error) {
            notify(readApiError(error, 'That did not work.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Examination Resets" subtitle="Requests to sit an examination again." />
                <SkeletonRows rows={3} height={70} />
            </>
        );
    }

    const canDecide = detail && ['Pending', 'Recommended'].includes(detail.approval_status);

    return (
        <>
            <PageHeader
                title="Examination Resets"
                subtitle="Requests to sit an examination again."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {isStudent && (
                <section className="nsu-card">
                    <h2 className="nsu-section-title">Request a reset</h2>

                    <p className="nsu-assessment-summary__meta">
                        Only an examination you have already sat and could not finish can be reset.
                    </p>

                    <label className="nsu-field">
                        <span className="nsu-field__label">Examination</span>
                        <select
                            className="nsu-field__input"
                            value={newExamId}
                            onChange={(event) => setNewExamId(event.target.value)}
                        >
                            <option value="">Choose an examination</option>
                            {exams.map((exam) => (
                                <option value={exam.id} key={exam.id}>
                                    {exam.course_code} · {exam.title}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="nsu-field">
                        <span className="nsu-field__label">
                            What happened
                            <span className="nsu-field__helper">
                                Be specific. The lecturer and coordinator both read this.
                            </span>
                        </span>
                        <textarea
                            className="nsu-field__input"
                            rows={3}
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                            placeholder="The network dropped at 09:14 and the session ended"
                        />
                    </label>

                    <Button variant="primary" icon={RotateCcw} onClick={submitRequest}>
                        Submit request
                    </Button>
                </section>
            )}

            {requests.length === 0 ? (
                <EmptyState
                    icon={RotateCcw}
                    title="No reset requests"
                    description={
                        isStudent
                            ? 'Requests you submit appear here with their status.'
                            : 'Requests from students appear here for review.'
                    }
                />
            ) : (
                <>
                    <div className="nsu-tabs" role="tablist">
                        {requests.map((request) => (
                            <button
                                type="button"
                                role="tab"
                                key={request.id}
                                aria-selected={request.id === selectedId}
                                className={`nsu-tab${request.id === selectedId ? ' nsu-tab--active' : ''}`}
                                onClick={() => setSelectedId(request.id)}
                            >
                                {request.course_code} · {request.approval_status}
                            </button>
                        ))}
                    </div>

                    {detail && (
                        <>
                            <div className="nsu-card nsu-assessment-summary">
                                <div>
                                    <h2 className="nsu-section-title">{detail.exam_title}</h2>
                                    <p className="nsu-assessment-summary__meta">
                                        {detail.course_code} · {detail.student_name} (
                                        {detail.student_number}) · requested{' '}
                                        {moment(detail.request_date)}
                                    </p>
                                </div>
                                <Badge variant={statusVariants[detail.approval_status]}>
                                    {detail.approval_status}
                                </Badge>
                            </div>

                            <section className="nsu-card">
                                <h2 className="nsu-section-title">Reason given</h2>
                                <p className="nsu-notification__message">{detail.request_reason}</p>

                                {detail.remarks && (
                                    <Alert
                                        variant={
                                            detail.approval_status === 'Rejected' ? 'error' : 'success'
                                        }
                                    >
                                        {detail.remarks}
                                    </Alert>
                                )}
                            </section>

                            {!isStudent && canDecide && (
                                <section className="nsu-card">
                                    <h2 className="nsu-section-title">Decision</h2>

                                    <label className="nsu-field">
                                        <span className="nsu-field__label">
                                            Remarks
                                            <span className="nsu-field__helper">
                                                Required when rejecting.
                                            </span>
                                        </span>
                                        <textarea
                                            className="nsu-field__input"
                                            rows={3}
                                            value={remarks}
                                            onChange={(event) => setRemarks(event.target.value)}
                                        />
                                    </label>

                                    <div
                                        className="nsu-table__actions"
                                        style={{ marginTop: 'var(--space-md)' }}
                                    >
                                        {user.role === 'Lecturer' &&
                                            detail.approval_status === 'Pending' && (
                                                <Button
                                                    variant="secondary"
                                                    icon={ThumbsUp}
                                                    onClick={() =>
                                                        decide(
                                                            examResetService.recommend,
                                                            'Recommended to the coordinator.',
                                                            false,
                                                        )
                                                    }
                                                >
                                                    Recommend
                                                </Button>
                                            )}

                                        {user.role === 'Coordinator' && (
                                            <>
                                                <Button
                                                    variant="primary"
                                                    icon={Check}
                                                    onClick={() =>
                                                        decide(
                                                            examResetService.approve,
                                                            'Reset approved and applied.',
                                                            false,
                                                        )
                                                    }
                                                >
                                                    Approve and reset
                                                </Button>

                                                <Button
                                                    variant="danger"
                                                    icon={X}
                                                    onClick={() =>
                                                        decide(
                                                            examResetService.reject,
                                                            'Request rejected.',
                                                            true,
                                                        )
                                                    }
                                                >
                                                    Reject
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </section>
                            )}

                            <section className="nsu-card">
                                <h2 className="nsu-section-title">History</h2>

                                <ul className="nsu-roster">
                                    {detail.log.map((entry) => (
                                        <li className="nsu-roster__row" key={entry.id}>
                                            <span>
                                                <strong>{entry.action}</strong>
                                                <span className="nsu-table__hint">
                                                    {entry.performed_by_name} (
                                                    {entry.performed_by_role})
                                                    {entry.remarks ? ` · ${entry.remarks}` : ''}
                                                </span>
                                            </span>
                                            <span className="nsu-table__hint">
                                                {moment(entry.created_at)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        </>
                    )}
                </>
            )}
        </>
    );
}
