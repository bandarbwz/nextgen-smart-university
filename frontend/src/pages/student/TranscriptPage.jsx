import { useEffect, useState } from 'react';
import { ScrollText } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';

export function TranscriptPage() {
    const [transcript, setTranscript] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        academicService
            .transcript()
            .then(setTranscript)
            .catch((error) => setNotice(readApiError(error, 'Unable to load your transcript.').message))
            .finally(() => setIsLoading(false));
    }, []);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Transcript" subtitle="Your complete academic record." />
                <SkeletonRows rows={4} height={64} />
            </>
        );
    }

    const semesters = transcript?.semesters ?? [];

    return (
        <>
            <PageHeader
                title="Transcript"
                subtitle={
                    transcript?.student
                        ? `${transcript.student.full_name} - ${transcript.student.program}`
                        : 'Your complete academic record.'
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {transcript?.summary && (
                <div className="nsu-grid nsu-grid--stats" style={{ marginBottom: 'var(--space-xl)' }}>
                    <article className="nsu-card">
                        <div className="nsu-stat">
                            <div>
                                <p className="nsu-stat__label">Cumulative GPA</p>
                                <p className="nsu-stat__value tabular">
                                    {Number(transcript.summary.cgpa).toFixed(2)}
                                </p>
                            </div>
                        </div>
                    </article>
                    <article className="nsu-card">
                        <div className="nsu-stat">
                            <div>
                                <p className="nsu-stat__label">Credits earned</p>
                                <p className="nsu-stat__value tabular">
                                    {transcript.summary.completed_credit_hours}
                                </p>
                                <p className="nsu-stat__hint">
                                    of {transcript.student.required_credit_hours} required
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            )}

            {semesters.length === 0 ? (
                <div className="nsu-card">
                    <EmptyState
                        icon={ScrollText}
                        title="No graded courses yet"
                        description="Completed courses appear here once your grades are approved and published."
                    />
                </div>
            ) : (
                semesters.map((semester) => (
                    <section
                        key={`${semester.academic_year}-${semester.semester}`}
                        style={{ marginBottom: 'var(--space-xl)' }}
                    >
                        <h2 className="nsu-section-title">
                            {semester.semester} - {semester.academic_year}
                        </h2>

                        <div className="nsu-card">
                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <caption className="visually-hidden">
                                        Results for {semester.semester} {semester.academic_year}
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Code</th>
                                            <th scope="col">Course</th>
                                            <th scope="col">Credits</th>
                                            <th scope="col">Grade</th>
                                            <th scope="col">Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {semester.courses.map((course) => (
                                            <tr key={course.course_code}>
                                                <td className="tabular">{course.course_code}</td>
                                                <td>{course.course_name}</td>
                                                <td className="tabular">{course.credit_hours}</td>
                                                <td className="tabular">{course.grade}</td>
                                                <td className="tabular">
                                                    {Number(course.grade_points).toFixed(2)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                ))
            )}
        </>
    );
}
