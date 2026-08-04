import { useCallback, useEffect, useState } from 'react';
import { Check, RotateCcw, ShieldCheck, X } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { gradeApprovalService } from '../../services/gradeApprovalService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    Pending: 'warning',
    Approved: 'success',
    Rejected: 'danger',
    'Returned for Revision': 'warning',
};

function moment(value) {
    return value === null ? '—' : new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function GradeApprovalsPage() {
    const { notify } = useToast();

    const [approvals, setApprovals] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [detail, setDetail] = useState(null);
    const [remarks, setRemarks] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const loadList = useCallback(
        () =>
            gradeApprovalService.list().then((data) => {
                setApprovals(data);

                setSelectedId((current) =>
                    current !== null && data.some((item) => item.id === current)
                        ? current
                        : (data[0]?.id ?? null),
                );
            }),
        [],
    );

    useEffect(() => {
        loadList()
            .catch((error) => setNotice(readApiError(error, 'Unable to load approvals.').message))
            .finally(() => setIsLoading(false));
    }, [loadList]);

    useEffect(() => {
        if (selectedId === null) {
            setDetail(null);

            return;
        }

        gradeApprovalService
            .get(selectedId)
            .then((data) => {
                setDetail(data);
                setRemarks('');
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load the request.').message));
    }, [selectedId]);

    const decide = async (action, label, needsRemarks) => {
        if (needsRemarks && remarks.trim() === '') {
            notify('Remarks are required so the lecturer knows what to change.', 'error');

            return;
        }

        try {
            const updated = await action(selectedId, remarks.trim() || null);

            setDetail(updated);
            await loadList();
            notify(label, 'success');
        } catch (error) {
            notify(readApiError(error, 'That did not work.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Grade Approvals" subtitle="Review and approve final grades." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    if (approvals.length === 0) {
        return (
            <>
                <PageHeader title="Grade Approvals" subtitle="Review and approve final grades." />
                <EmptyState
                    icon={ShieldCheck}
                    title="Nothing waiting"
                    description="Grade submissions from lecturers appear here for review."
                />
            </>
        );
    }

    const isPending = detail?.approval_status === 'Pending';

    return (
        <>
            <PageHeader title="Grade Approvals" subtitle="Review and approve final grades." />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist">
                {approvals.map((approval) => (
                    <button
                        type="button"
                        role="tab"
                        key={approval.id}
                        aria-selected={approval.id === selectedId}
                        className={`nsu-tab${approval.id === selectedId ? ' nsu-tab--active' : ''}`}
                        onClick={() => setSelectedId(approval.id)}
                    >
                        {approval.course_code} · {approval.approval_status}
                    </button>
                ))}
            </div>

            {detail && (
                <>
                    <div className="nsu-card nsu-assessment-summary">
                        <div>
                            <h2 className="nsu-section-title">
                                {detail.course_code} {detail.course_name} · Section{' '}
                                {detail.section_number}
                            </h2>
                            <p className="nsu-assessment-summary__meta">
                                Submitted by {detail.lecturer_name} on {moment(detail.submitted_at)} ·{' '}
                                {detail.student_count} student(s)
                            </p>
                        </div>
                        <Badge variant={statusVariants[detail.approval_status]}>
                            {detail.approval_status}
                        </Badge>
                    </div>

                    {detail.remarks && (
                        <Alert variant={detail.approval_status === 'Approved' ? 'success' : 'error'}>
                            {detail.remarks}
                        </Alert>
                    )}

                    <section className="nsu-card">
                        <h2 className="nsu-section-title">Grades for review</h2>

                        <div className="nsu-table-wrap">
                            <table className="nsu-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th className="nsu-table__number">Weighted</th>
                                        <th>Grade</th>
                                        <th className="nsu-table__number">Points</th>
                                        <th>Complete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {detail.grades.map((grade) => (
                                        <tr key={grade.student_id}>
                                            <td>{grade.student_number}</td>
                                            <td className="nsu-table__number tabular">
                                                {grade.weighted_percentage}
                                            </td>
                                            <td>
                                                <Badge
                                                    variant={
                                                        grade.grade_letter === 'F'
                                                            ? 'danger'
                                                            : 'success'
                                                    }
                                                >
                                                    {grade.grade_letter}
                                                </Badge>
                                            </td>
                                            <td className="nsu-table__number tabular">
                                                {grade.grade_points}
                                            </td>
                                            <td>{grade.is_complete ? 'Yes' : 'No'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {isPending && (
                        <section className="nsu-card">
                            <h2 className="nsu-section-title">Decision</h2>

                            <label className="nsu-field">
                                <span className="nsu-field__label">
                                    Remarks
                                    <span className="nsu-field__helper">
                                        Required when rejecting or returning for revision.
                                    </span>
                                </span>
                                <textarea
                                    className="nsu-field__input"
                                    rows={3}
                                    value={remarks}
                                    onChange={(event) => setRemarks(event.target.value)}
                                    placeholder="Explain what needs to change"
                                />
                            </label>

                            <div className="nsu-table__actions" style={{ marginTop: 'var(--space-md)' }}>
                                <Button
                                    variant="primary"
                                    icon={Check}
                                    onClick={() =>
                                        decide(
                                            gradeApprovalService.approve,
                                            'Grades approved and published.',
                                            false,
                                        )
                                    }
                                >
                                    Approve and publish
                                </Button>

                                <Button
                                    variant="secondary"
                                    icon={RotateCcw}
                                    onClick={() =>
                                        decide(
                                            gradeApprovalService.returnForRevision,
                                            'Returned for revision.',
                                            true,
                                        )
                                    }
                                >
                                    Return for revision
                                </Button>

                                <Button
                                    variant="danger"
                                    icon={X}
                                    onClick={() =>
                                        decide(gradeApprovalService.reject, 'Grades rejected.', true)
                                    }
                                >
                                    Reject
                                </Button>
                            </div>
                        </section>
                    )}

                    <section className="nsu-card">
                        <h2 className="nsu-section-title">Approval history</h2>

                        <ul className="nsu-roster">
                            {detail.log.map((entry) => (
                                <li className="nsu-roster__row" key={entry.id}>
                                    <span>
                                        <strong>{entry.action}</strong>
                                        <span className="nsu-table__hint">
                                            {entry.performed_by_name} ({entry.performed_by_role})
                                            {entry.remarks ? ` · ${entry.remarks}` : ''}
                                        </span>
                                    </span>
                                    <span className="nsu-table__hint">{moment(entry.created_at)}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                </>
            )}
        </>
    );
}
