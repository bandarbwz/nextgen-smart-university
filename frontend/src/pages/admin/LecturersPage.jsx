import { useEffect, useMemo, useState } from 'react';
import { GraduationCap, Search } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { administrationService } from '../../services/administrationService';
import { readApiError } from '../../services/apiClient';

const employmentVariants = {
    full_time: 'success',
    part_time: 'warning',
    visiting: 'neutral',
};

function readable(value) {
    return value ? value.replace(/_/g, ' ') : '—';
}

export function LecturersPage() {
    const [lecturers, setLecturers] = useState([]);
    const [sections, setSections] = useState([]);
    const [term, setTerm] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        Promise.all([administrationService.lecturers(), administrationService.sections()])
            .then(([lecturerData, sectionData]) => {
                setLecturers(lecturerData);
                setSections(sectionData);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load lecturers.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const teachingLoad = useMemo(() => {
        const load = {};

        sections.forEach((section) => {
            const id = section.lecturer_id;

            load[id] = load[id] ?? { sections: 0, credits: 0, students: 0 };
            load[id].sections += 1;
            load[id].credits += Number(section.credit_hours ?? 0);
            load[id].students += Number(section.registered_students ?? 0);
        });

        return load;
    }, [sections]);

    const visible = useMemo(() => {
        const needle = term.trim().toLowerCase();

        if (needle === '') {
            return lecturers;
        }

        return lecturers.filter((lecturer) =>
            [lecturer.full_name, lecturer.email, lecturer.department_name, lecturer.specialization]
                .filter(Boolean)
                .some((field) => field.toLowerCase().includes(needle)),
        );
    }, [lecturers, term]);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Lecturers" subtitle="Teaching staff and their load." />
                <SkeletonRows rows={5} height={56} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Lecturers"
                subtitle={`${lecturers.length} lecturer${lecturers.length === 1 ? '' : 's'} on record.`}
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-toolbar">
                <label className="nsu-search">
                    <Search size={16} aria-hidden="true" />
                    <input
                        type="search"
                        className="nsu-search__input"
                        placeholder="Search by name, email, department or specialisation"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        aria-label="Search lecturers"
                    />
                </label>
            </div>

            {visible.length === 0 ? (
                <EmptyState
                    icon={GraduationCap}
                    title="No lecturers match"
                    description="Try a different search term."
                />
            ) : (
                <div className="nsu-card">
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <thead>
                                <tr>
                                    <th>Lecturer</th>
                                    <th>Department</th>
                                    <th>Employment</th>
                                    <th className="nsu-table__number">Sections</th>
                                    <th className="nsu-table__number">Credit hours</th>
                                    <th className="nsu-table__number">Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visible.map((lecturer) => {
                                    const load = teachingLoad[lecturer.id] ?? {
                                        sections: 0,
                                        credits: 0,
                                        students: 0,
                                    };

                                    return (
                                        <tr key={lecturer.id}>
                                            <td>
                                                {lecturer.full_name}
                                                <span className="nsu-table__hint">{lecturer.email}</span>
                                            </td>
                                            <td>
                                                {lecturer.department_name ?? '—'}
                                                <span className="nsu-table__hint">
                                                    {lecturer.specialization ?? ''}
                                                </span>
                                            </td>
                                            <td>
                                                <Badge
                                                    variant={
                                                        employmentVariants[lecturer.employment_status] ??
                                                        'neutral'
                                                    }
                                                >
                                                    {readable(lecturer.employment_status)}
                                                </Badge>
                                            </td>
                                            <td className="nsu-table__number tabular">{load.sections}</td>
                                            <td className="nsu-table__number tabular">{load.credits}</td>
                                            <td className="nsu-table__number tabular">{load.students}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </>
    );
}
