import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Award, BookOpen, CalendarClock, GraduationCap, Layers } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { StatusBadge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { useAuth } from '../../hooks/useAuth';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';

function StatCard({ label, value, hint, icon: Icon }) {
    return (
        <article className="nsu-card">
            <div className="nsu-stat">
                <div>
                    <p className="nsu-stat__label">{label}</p>
                    <p className="nsu-stat__value tabular">{value}</p>
                    {hint && <p className="nsu-stat__hint">{hint}</p>}
                </div>
                <span className="nsu-stat__icon">
                    <Icon size={20} aria-hidden="true" />
                </span>
            </div>
        </article>
    );
}

export function DashboardPage() {
    const { user } = useAuth();

    const [student, setStudent] = useState(null);
    const [enrollments, setEnrollments] = useState([]);
    const [semester, setSemester] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const isStudent = user.role === 'Student';

    useEffect(() => {
        let isActive = true;

        async function load() {
            try {
                const semesterData = await academicService.currentSemester().catch(() => null);

                if (!isActive) {
                    return;
                }

                setSemester(semesterData);

                if (!isStudent) {
                    return;
                }

                const [studentData, enrollmentData] = await Promise.all([
                    academicService.studentProfile(),
                    academicService.currentEnrollments(),
                ]);

                if (isActive) {
                    setStudent(studentData);
                    setEnrollments(enrollmentData);
                }
            } catch (error) {
                if (isActive) {
                    setNotice(readApiError(error, 'Unable to load your dashboard.').message);
                }
            } finally {
                if (isActive) {
                    setIsLoading(false);
                }
            }
        }

        load();

        return () => {
            isActive = false;
        };
    }, [isStudent]);

    const registeredCredits = enrollments
        .filter((item) => ['Pending', 'Approved'].includes(item.enrollment_status))
        .reduce((total, item) => total + Number(item.credit_hours), 0);

    return (
        <>
            <PageHeader
                title={`Welcome back, ${user.full_name.split(' ')[0]}`}
                subtitle={
                    semester
                        ? `${semester.name}, ${semester.academic_year}`
                        : 'No active semester is currently set.'
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {isLoading ? (
                <SkeletonRows rows={3} height={96} />
            ) : isStudent ? (
                <>
                    <div className="nsu-grid nsu-grid--stats" style={{ marginBottom: 'var(--space-xl)' }}>
                        <StatCard
                            label="Cumulative GPA"
                            value={student ? Number(student.cumulative_gpa).toFixed(2) : '0.00'}
                            hint="Out of 4.00"
                            icon={Award}
                        />
                        <StatCard
                            label="Credits completed"
                            value={student ? student.completed_credit_hours : 0}
                            hint={student ? `of ${student.required_credit_hours} required` : ''}
                            icon={GraduationCap}
                        />
                        <StatCard
                            label="Registered this semester"
                            value={registeredCredits}
                            hint="Credit hours"
                            icon={Layers}
                        />
                        <StatCard
                            label="Active courses"
                            value={enrollments.length}
                            hint="Including pending approval"
                            icon={BookOpen}
                        />
                    </div>

                    <h2 className="nsu-section-title">This semester</h2>

                    <div className="nsu-card">
                        {enrollments.length === 0 ? (
                            <EmptyState
                                icon={CalendarClock}
                                title="No courses registered yet"
                                description="Browse the catalog and register for sections while the registration period is open."
                                action={<Link to="/registration">Go to course registration</Link>}
                            />
                        ) : (
                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <caption className="visually-hidden">
                                        Courses registered this semester
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Code</th>
                                            <th scope="col">Course</th>
                                            <th scope="col">Section</th>
                                            <th scope="col">Credits</th>
                                            <th scope="col">Lecturer</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {enrollments.map((item) => (
                                            <tr key={item.id}>
                                                <td className="tabular">{item.course_code}</td>
                                                <td>{item.course_name}</td>
                                                <td className="tabular">{item.section_number}</td>
                                                <td className="tabular">{item.credit_hours}</td>
                                                <td>{item.lecturer_name}</td>
                                                <td>
                                                    <StatusBadge status={item.enrollment_status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </>
            ) : (
                <div className="nsu-card">
                    <div className="nsu-card__body">
                        <h2 className="nsu-section-title">{user.role} portal</h2>
                        <p style={{ color: 'var(--color-muted-foreground)', margin: 0 }}>
                            You are signed in with {user.permissions.length} permissions. Use the
                            navigation to manage academic records.
                        </p>
                    </div>
                </div>
            )}
        </>
    );
}
