import { useCallback, useEffect, useState } from 'react';
import { ClipboardList, PlusCircle, Trash2 } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { StatusBadge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

export function RegistrationPage() {
    const { notify } = useToast();

    const [sections, setSections] = useState([]);
    const [enrollments, setEnrollments] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [pendingId, setPendingId] = useState(null);
    const [notice, setNotice] = useState('');

    const load = useCallback(async () => {
        try {
            const [sectionData, enrollmentData] = await Promise.all([
                academicService.sections(),
                academicService.currentEnrollments().catch(() => []),
            ]);

            setSections(sectionData);
            setEnrollments(enrollmentData);
            setNotice('');
        } catch (error) {
            setNotice(readApiError(error, 'Unable to load available sections.').message);
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const registeredSectionIds = new Set(
        enrollments
            .filter((item) => ['Pending', 'Approved'].includes(item.enrollment_status))
            .map((item) => item.section_id),
    );

    async function handleRegister(sectionId) {
        setPendingId(sectionId);

        try {
            await academicService.register(sectionId);

            notify('Course registered. Awaiting coordinator approval.');

            await load();
        } catch (error) {
            notify(readApiError(error, 'Registration failed.').message, 'error');
        } finally {
            setPendingId(null);
        }
    }

    async function handleDrop(enrollmentId) {
        setPendingId(enrollmentId);

        try {
            await academicService.drop(enrollmentId);

            notify('Course dropped.');

            await load();
        } catch (error) {
            notify(readApiError(error, 'Unable to drop this course.').message, 'error');
        } finally {
            setPendingId(null);
        }
    }

    return (
        <>
            <PageHeader
                title="Course registration"
                subtitle="Register for open sections in the current semester."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <h2 className="nsu-section-title">Your registered courses</h2>

            <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                {isLoading ? (
                    <div style={{ padding: 'var(--space-md)' }}>
                        <SkeletonRows rows={2} height={44} />
                    </div>
                ) : enrollments.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="Nothing registered yet"
                        description="Pick a section from the list below to start building your semester."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your registered courses</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Section</th>
                                    <th scope="col">Credits</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {enrollments.map((item) => (
                                    <tr key={item.id}>
                                        <td className="tabular">{item.course_code}</td>
                                        <td>{item.course_name}</td>
                                        <td className="tabular">{item.section_number}</td>
                                        <td className="tabular">{item.credit_hours}</td>
                                        <td>
                                            <StatusBadge status={item.enrollment_status} />
                                        </td>
                                        <td>
                                            {['Pending', 'Approved'].includes(item.enrollment_status) && (
                                                <Button
                                                    variant="ghost"
                                                    icon={Trash2}
                                                    isLoading={pendingId === item.id}
                                                    onClick={() => handleDrop(item.id)}
                                                >
                                                    Drop
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <h2 className="nsu-section-title">Available sections</h2>

            <div className="nsu-card">
                {isLoading ? (
                    <div style={{ padding: 'var(--space-md)' }}>
                        <SkeletonRows rows={4} height={44} />
                    </div>
                ) : sections.length === 0 ? (
                    <EmptyState
                        icon={ClipboardList}
                        title="No sections are open"
                        description="Sections appear here once a coordinator opens them for the current semester."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Sections open for registration</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Section</th>
                                    <th scope="col">Lecturer</th>
                                    <th scope="col">Seats</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sections.map((section) => {
                                    const isFull =
                                        Number(section.registered_students) >= Number(section.capacity);
                                    const isRegistered = registeredSectionIds.has(section.id);

                                    return (
                                        <tr key={section.id}>
                                            <td className="tabular">{section.course_code}</td>
                                            <td>{section.course_name}</td>
                                            <td className="tabular">{section.section_number}</td>
                                            <td>{section.lecturer_name}</td>
                                            <td className="tabular">
                                                {section.registered_students} / {section.capacity}
                                            </td>
                                            <td>
                                                <Button
                                                    variant="accent"
                                                    icon={PlusCircle}
                                                    disabled={isFull || isRegistered}
                                                    isLoading={pendingId === section.id}
                                                    onClick={() => handleRegister(section.id)}
                                                >
                                                    {isRegistered ? 'Registered' : isFull ? 'Full' : 'Register'}
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
