import { useCallback, useEffect, useState } from 'react';
import { ClipboardCheck, Send, ShieldCheck } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { assessmentService } from '../../services/assessmentService';
import { gradeApprovalService } from '../../services/gradeApprovalService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    draft: 'neutral',
    published: 'success',
    closed: 'warning',
};

export function AssessmentsPage() {
    const { notify } = useToast();

    const [assessments, setAssessments] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [detail, setDetail] = useState(null);
    const [weights, setWeights] = useState(null);
    const [drafts, setDrafts] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        assessmentService
            .list()
            .then((data) => {
                setAssessments(data);
                setSelectedId(data.length > 0 ? data[0].id : null);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load assessments.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const load = useCallback(() => {
        if (selectedId === null) {
            return Promise.resolve();
        }

        return assessmentService
            .get(selectedId)
            .then((data) => {
                setDetail(data);
                setDrafts({});

                return assessmentService.weights(data.section_id);
            })
            .then(setWeights)
            .catch((error) => setNotice(readApiError(error, 'Unable to load the assessment.').message));
    }, [selectedId]);

    useEffect(() => {
        load();
    }, [load]);

    const saveMark = async (studentId) => {
        const marks = drafts[studentId];

        if (marks === undefined || marks === '') {
            return;
        }

        try {
            await assessmentService.recordResult(selectedId, {
                student_id: studentId,
                marks: Number(marks),
            });

            await load();
            notify('Mark saved.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to save the mark.').message, 'error');
        }
    };

    const submitForApproval = async () => {
        try {
            await gradeApprovalService.submit(detail.section_id);
            notify('Grades submitted to the coordinator.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to submit the grades.').message, 'error');
        }
    };

    const publish = async () => {
        try {
            const outcome = await assessmentService.publish(selectedId);

            await load();
            notify(`Published ${outcome.published} result(s).`, 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to publish.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Assessments" subtitle="Weighted components and grade entry." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    if (assessments.length === 0) {
        return (
            <>
                <PageHeader title="Assessments" subtitle="Weighted components and grade entry." />
                <EmptyState
                    icon={ClipboardCheck}
                    title="No assessments yet"
                    description="Create an assessment for one of your sections and it will appear here."
                />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Assessments"
                subtitle="Weighted components and grade entry."
                actions={
                    detail ? (
                        <div className="nsu-table__actions">
                            {detail.status !== 'closed' && (
                                <Button variant="secondary" icon={Send} onClick={publish}>
                                    Publish results
                                </Button>
                            )}

                            {weights?.is_complete && (
                                <Button
                                    variant="primary"
                                    icon={ShieldCheck}
                                    onClick={submitForApproval}
                                >
                                    Submit for approval
                                </Button>
                            )}
                        </div>
                    ) : null
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist">
                {assessments.map((assessment) => (
                    <button
                        type="button"
                        role="tab"
                        key={assessment.id}
                        aria-selected={assessment.id === selectedId}
                        className={`nsu-tab${assessment.id === selectedId ? ' nsu-tab--active' : ''}`}
                        onClick={() => setSelectedId(assessment.id)}
                    >
                        {assessment.course_code} · {assessment.title}
                    </button>
                ))}
            </div>

            {weights && (
                <Alert variant={weights.is_complete ? 'success' : 'info'}>
                    This section is weighted at {weights.weight_used} per cent
                    {weights.is_complete
                        ? '. The scheme is complete.'
                        : `, so ${weights.weight_remaining} per cent is still unallocated. A course result cannot be final until it reaches 100.`}
                </Alert>
            )}

            {detail && (
                <>
                    <div className="nsu-card nsu-assessment-summary">
                        <div>
                            <h2 className="nsu-section-title">{detail.title}</h2>
                            <p className="nsu-assessment-summary__meta">
                                {detail.assessment_type} · out of {detail.total_marks} · worth{' '}
                                {detail.weight_percentage} per cent
                            </p>
                        </div>
                        <Badge variant={statusVariants[detail.status]}>{detail.status}</Badge>
                    </div>

                    {detail.rubric?.length > 0 && (
                        <div className="nsu-card">
                            <h2 className="nsu-section-title">Rubric</h2>
                            <ul className="nsu-rubric">
                                {detail.rubric.map((criterion) => (
                                    <li className="nsu-rubric__row" key={criterion.id}>
                                        <span>
                                            <strong>{criterion.criterion}</strong>
                                            {criterion.description && (
                                                <span className="nsu-table__hint">
                                                    {criterion.description}
                                                </span>
                                            )}
                                        </span>
                                        <span className="tabular">{criterion.maximum_marks}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <section className="nsu-card">
                        <h2 className="nsu-section-title">Grade entry</h2>

                        {detail.statistics?.graded > 0 && (
                            <p className="nsu-assessment-summary__meta">
                                {detail.statistics.graded} graded · average{' '}
                                {detail.statistics.average} per cent · range{' '}
                                {detail.statistics.lowest} to {detail.statistics.highest}
                            </p>
                        )}

                        {detail.results.length === 0 ? (
                            <EmptyState
                                title="Nothing graded yet"
                                description="Marks recorded here appear to students only after you publish."
                            />
                        ) : (
                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th className="nsu-table__number">Marks</th>
                                            <th className="nsu-table__number">Percentage</th>
                                            <th>Grade</th>
                                            <th>Published</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {detail.results.map((result) => (
                                            <tr key={result.id}>
                                                <td>
                                                    {result.student_name}
                                                    <span className="nsu-table__hint">
                                                        {result.student_number}
                                                    </span>
                                                </td>
                                                <td className="nsu-table__number">
                                                    {result.published_at ? (
                                                        <span className="tabular">{result.marks}</span>
                                                    ) : (
                                                        <span className="nsu-mark-entry">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max={detail.total_marks}
                                                                step="0.5"
                                                                className="nsu-field__input nsu-mark-entry__input"
                                                                defaultValue={result.marks}
                                                                onChange={(event) =>
                                                                    setDrafts((current) => ({
                                                                        ...current,
                                                                        [result.student_id]:
                                                                            event.target.value,
                                                                    }))
                                                                }
                                                                aria-label={`Marks for ${result.student_name}`}
                                                            />
                                                            <Button
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    saveMark(result.student_id)
                                                                }
                                                            >
                                                                Save
                                                            </Button>
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="nsu-table__number tabular">
                                                    {result.percentage}
                                                </td>
                                                <td>
                                                    <Badge variant="neutral">{result.grade}</Badge>
                                                </td>
                                                <td>{result.published_at ? 'Yes' : 'No'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </>
            )}
        </>
    );
}
