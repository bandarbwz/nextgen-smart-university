import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Award, BookOpen, CalendarClock, GraduationCap, Layers, Plus } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { StatusBadge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Alert } from '../../components/Alert';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';
import '../../styles/dashboard.css';

const TABS = [
    ['all', 'All'],
    ['approved', 'In Progress'],
    ['completed', 'Completed'],
    ['dropped', 'Dropped'],
];

const GROUPS = [
    ['approved', 'In progress', ['Pending', 'Approved']],
    ['completed', 'Completed', ['Completed']],
    ['dropped', 'Dropped', ['Dropped', 'Withdrawn', 'Rejected']],
];

function initialsOf(name) {
    return (name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

function StatCard({ label, value, hint, icon: Icon }) {
    return (
        <article className="nsu-dash__stat">
            <div className="nsu-dash__stat-top">
                <span>{label}</span>
                <span className="nsu-dash__stat-icon">
                    <Icon size={16} aria-hidden="true" />
                </span>
            </div>
            <p className="nsu-dash__stat-value tabular">{value}</p>
            <p className="nsu-dash__stat-foot">{hint}</p>
        </article>
    );
}

function CourseCard({ enrollment, isOpen, onToggle, onDrop }) {
    const canDrop = ['Pending', 'Approved'].includes(enrollment.enrollment_status);

    return (
        <article className={`nsu-course${isOpen ? ' nsu-course--open' : ''}`}>
            <button type="button" className="nsu-course__row" onClick={onToggle}>
                <div>
                    <div className="nsu-course__code tabular">{enrollment.course_code}</div>
                    <div className="nsu-course__section">
                        Section {enrollment.section_number} &middot; {enrollment.credit_hours}{' '}
                        credits
                    </div>
                </div>

                <div className="nsu-course__name">{enrollment.course_name}</div>

                <div className="nsu-course__lecturer">
                    <span className="nsu-avatar-sm" aria-hidden="true">
                        {initialsOf(enrollment.lecturer_name)}
                    </span>
                    <span>{enrollment.lecturer_name}</span>
                </div>

                <div className="nsu-course__right">
                    <StatusBadge status={enrollment.enrollment_status} />
                    <span className="nsu-course__toggle">{isOpen ? 'Show less' : 'Show more'}</span>
                </div>
            </button>

            {isOpen && (
                <div className="nsu-course__details">
                    <div className="nsu-course__actions">
                        {canDrop && (
                            <Button variant="danger" onClick={() => onDrop(enrollment)}>
                                Drop
                            </Button>
                        )}
                        <Link className="nsu-button nsu-button--secondary" to="/course-content">
                            Course content
                        </Link>
                        <Link className="nsu-button nsu-button--primary" to="/my-results">
                            {enrollment.enrollment_status === 'Completed'
                                ? 'View grades'
                                : 'Go to course'}
                        </Link>
                    </div>
                </div>
            )}
        </article>
    );
}

export function DashboardPage() {
    const { user } = useAuth();
    const { notify } = useToast();
    const navigate = useNavigate();

    const [student, setStudent] = useState(null);
    const [enrollments, setEnrollments] = useState([]);
    const [semester, setSemester] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');
    const [filter, setFilter] = useState('all');
    const [openId, setOpenId] = useState(null);

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

                const [studentData, current, history] = await Promise.all([
                    academicService.studentProfile(),
                    academicService.currentEnrollments(),
                    academicService.enrollmentHistory().catch(() => []),
                ]);

                if (!isActive) {
                    return;
                }

                const merged = [...current];

                history.forEach((item) => {
                    if (!merged.some((existing) => existing.id === item.id)) {
                        merged.push(item);
                    }
                });

                setStudent(studentData);
                setEnrollments(merged);
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

    const activeCount = enrollments.filter((item) =>
        ['Pending', 'Approved'].includes(item.enrollment_status),
    ).length;

    const groups = useMemo(
        () =>
            GROUPS.filter(([key]) => filter === 'all' || filter === key).map(
                ([key, label, statuses]) => [
                    key,
                    label,
                    enrollments.filter((item) => statuses.includes(item.enrollment_status)),
                ],
            ),
        [enrollments, filter],
    );

    async function handleDrop(enrollment) {
        try {
            await academicService.drop(enrollment.id);

            setEnrollments((current) =>
                current.map((item) =>
                    item.id === enrollment.id
                        ? { ...item, enrollment_status: 'Dropped' }
                        : item,
                ),
            );

            notify(`${enrollment.course_code} dropped.`, 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to drop this course.').message, 'error');
        }
    }

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
                    <div className="nsu-dash__stats">
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
                            value={activeCount}
                            hint="Including pending approval"
                            icon={BookOpen}
                        />
                    </div>

                    <div className="nsu-dash__head">
                        <h2>This semester</h2>
                        <Button icon={Plus} onClick={() => navigate('/registration')}>
                            Register Course
                        </Button>
                    </div>

                    <div className="nsu-dash__tabs">
                        {TABS.map(([key, label]) => (
                            <button
                                type="button"
                                key={key}
                                className={`nsu-dash__tab${filter === key ? ' nsu-dash__tab--active' : ''}`}
                                onClick={() => setFilter(key)}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    {enrollments.length === 0 ? (
                        <EmptyState
                            icon={CalendarClock}
                            title="No courses registered yet"
                            description="Browse the catalog and register for sections while the registration period is open."
                            action={<Link to="/registration">Go to course registration</Link>}
                        />
                    ) : (
                        groups.map(([key, label, items]) =>
                            items.length === 0 ? null : (
                                <div key={key}>
                                    <p className="nsu-dash__group">{label}</p>

                                    <div className="nsu-dash__list">
                                        {items.map((item) => (
                                            <CourseCard
                                                key={item.id}
                                                enrollment={item}
                                                isOpen={openId === item.id}
                                                onToggle={() =>
                                                    setOpenId(openId === item.id ? null : item.id)
                                                }
                                                onDrop={handleDrop}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ),
                        )
                    )}
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
