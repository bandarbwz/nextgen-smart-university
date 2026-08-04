import { useEffect, useMemo, useState } from 'react';
import { GraduationCap, Search, Users } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { administrationService } from '../../services/administrationService';
import { readApiError } from '../../services/apiClient';

const statusVariants = {
    active: 'success',
    suspended: 'danger',
    graduated: 'neutral',
    withdrawn: 'neutral',
};

export function StudentsPage() {
    const [students, setStudents] = useState([]);
    const [term, setTerm] = useState('');
    const [status, setStatus] = useState('all');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        administrationService
            .students()
            .then(setStudents)
            .catch((error) => setNotice(readApiError(error, 'Unable to load students.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const visible = useMemo(() => {
        const needle = term.trim().toLowerCase();

        return students.filter((student) => {
            if (status !== 'all' && student.academic_status !== status) {
                return false;
            }

            if (needle === '') {
                return true;
            }

            return [student.full_name, student.student_number, student.email, student.program_name]
                .filter(Boolean)
                .some((field) => field.toLowerCase().includes(needle));
        });
    }, [students, term, status]);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Students" subtitle="Every enrolled student." />
                <SkeletonRows rows={5} height={56} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Students"
                subtitle={`${students.length} student${students.length === 1 ? '' : 's'} on record.`}
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-toolbar">
                <label className="nsu-search">
                    <Search size={16} aria-hidden="true" />
                    <input
                        type="search"
                        className="nsu-search__input"
                        placeholder="Search by name, number, email or programme"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        aria-label="Search students"
                    />
                </label>

                <label className="nsu-filter">
                    <span className="nsu-filter__label">Status</span>
                    <select
                        className="nsu-field__input"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                    >
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="graduated">Graduated</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </label>
            </div>

            {visible.length === 0 ? (
                <EmptyState
                    icon={Users}
                    title="No students match"
                    description="Try a different search term or clear the status filter."
                />
            ) : (
                <div className="nsu-card">
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Programme</th>
                                    <th>Level</th>
                                    <th className="nsu-table__number">Credits</th>
                                    <th className="nsu-table__number">CGPA</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visible.map((student) => (
                                    <tr key={student.id}>
                                        <td>
                                            {student.full_name}
                                            <span className="nsu-table__hint">
                                                {student.student_number} · {student.email}
                                            </span>
                                        </td>
                                        <td>
                                            {student.program_name ?? '—'}
                                            <span className="nsu-table__hint">
                                                {student.department_name ?? ''}
                                            </span>
                                        </td>
                                        <td className="tabular">{student.academic_level}</td>
                                        <td className="nsu-table__number tabular">
                                            {student.completed_credit_hours}
                                        </td>
                                        <td className="nsu-table__number tabular">
                                            {student.cumulative_gpa}
                                        </td>
                                        <td>
                                            <Badge
                                                variant={statusVariants[student.academic_status] ?? 'neutral'}
                                            >
                                                {student.academic_status}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </>
    );
}
