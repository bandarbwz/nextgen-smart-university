import { useEffect, useMemo, useState } from 'react';
import { ClipboardCheck } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { assessmentService } from '../../services/assessmentService';
import { readApiError } from '../../services/apiClient';

function gradeVariant(letter) {
    if (letter === 'F') {
        return 'danger';
    }

    return ['A', 'A-', 'B+', 'B'].includes(letter) ? 'success' : 'warning';
}

export function MyResultsPage() {
    const [results, setResults] = useState([]);
    const [courseResults, setCourseResults] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        assessmentService
            .myResults()
            .then(async (data) => {
                setResults(data);

                const sectionIds = [...new Set(data.map((item) => item.section_id))].filter(Boolean);

                const totals = await Promise.all(
                    sectionIds.map((id) =>
                        assessmentService.courseResult(id).catch(() => null),
                    ),
                );

                setCourseResults(totals.filter(Boolean));
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load your results.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const byCourse = useMemo(() => {
        const grouped = {};

        results.forEach((result) => {
            const key = `${result.course_code} ${result.course_name}`;

            grouped[key] = grouped[key] ?? [];
            grouped[key].push(result);
        });

        return grouped;
    }, [results]);

    if (isLoading) {
        return (
            <>
                <PageHeader title="My Results" subtitle="Published assessment results." />
                <SkeletonRows rows={3} height={80} />
            </>
        );
    }

    return (
        <>
            <PageHeader title="My Results" subtitle="Published assessment results and course totals." />

            {notice && <Alert variant="error">{notice}</Alert>}

            {results.length === 0 ? (
                <EmptyState
                    icon={ClipboardCheck}
                    title="No results yet"
                    description="Results appear here once your lecturer publishes them."
                />
            ) : (
                <>
                    {courseResults.length > 0 && (
                        <div className="nsu-grid nsu-grid--stats" style={{ marginBottom: 'var(--space-xl)' }}>
                            {courseResults.map((course) => (
                                <article className="nsu-card" key={course.section_id}>
                                    <div className="nsu-stat">
                                        <div>
                                            <p className="nsu-stat__label">
                                                Weighted total ({course.weight_counted} per cent
                                                counted)
                                            </p>
                                            <p className="nsu-stat__value tabular">
                                                {course.weighted_percentage}
                                            </p>
                                        </div>
                                        <Badge variant={gradeVariant(course.grade_letter)}>
                                            {course.grade_letter}
                                        </Badge>
                                    </div>

                                    {!course.is_complete && (
                                        <p className="nsu-table__hint">
                                            Not all components are published yet, so this is a running
                                            total rather than a final grade.
                                        </p>
                                    )}
                                </article>
                            ))}
                        </div>
                    )}

                    {Object.entries(byCourse).map(([course, items]) => (
                        <section className="nsu-card" key={course}>
                            <h2 className="nsu-section-title">{course}</h2>

                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Type</th>
                                            <th className="nsu-table__number">Marks</th>
                                            <th className="nsu-table__number">Weight</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((result) => (
                                            <tr key={result.id}>
                                                <td>
                                                    {result.title}
                                                    {result.feedback && (
                                                        <span className="nsu-table__hint">
                                                            {result.feedback}
                                                        </span>
                                                    )}
                                                </td>
                                                <td>{result.assessment_type}</td>
                                                <td className="nsu-table__number tabular">
                                                    {result.marks} / {result.total_marks}
                                                </td>
                                                <td className="nsu-table__number tabular">
                                                    {result.weight_percentage}%
                                                </td>
                                                <td>
                                                    <Badge variant={gradeVariant(result.grade)}>
                                                        {result.grade}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    ))}
                </>
            )}
        </>
    );
}
