import { Fragment, useEffect, useMemo, useState } from 'react';
import { BookOpen, Check, Lock, Search, Unlock, Users, X } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { administrationService } from '../../services/administrationService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    open: 'success',
    closed: 'neutral',
    cancelled: 'danger',
    full: 'warning',
};

function fillVariant(registered, capacity) {
    const ratio = capacity === 0 ? 0 : registered / capacity;

    if (ratio >= 1) {
        return 'danger';
    }

    return ratio >= 0.8 ? 'warning' : 'success';
}

export function SectionsPage() {
    const { notify } = useToast();

    const [sections, setSections] = useState([]);
    const [term, setTerm] = useState('');
    const [expandedId, setExpandedId] = useState(null);
    const [roster, setRoster] = useState([]);
    const [editingId, setEditingId] = useState(null);
    const [capacityDraft, setCapacityDraft] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        administrationService
            .sections()
            .then(setSections)
            .catch((error) => setNotice(readApiError(error, 'Unable to load sections.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const visible = useMemo(() => {
        const needle = term.trim().toLowerCase();

        if (needle === '') {
            return sections;
        }

        return sections.filter((section) =>
            [section.course_code, section.course_name, section.lecturer_name, section.classroom]
                .filter(Boolean)
                .some((field) => field.toLowerCase().includes(needle)),
        );
    }, [sections, term]);

    const replace = (updated) =>
        setSections((current) =>
            current.map((section) =>
                section.id === updated.id ? { ...section, ...updated } : section,
            ),
        );

    const toggleRoster = async (section) => {
        if (expandedId === section.id) {
            setExpandedId(null);

            return;
        }

        setExpandedId(section.id);
        setRoster([]);

        try {
            setRoster(await administrationService.sectionStudents(section.id));
        } catch (error) {
            notify(readApiError(error, 'Unable to load the roster.').message, 'error');
        }
    };

    const changeStatus = async (section) => {
        const action =
            section.status === 'open'
                ? administrationService.closeRegistration
                : administrationService.openRegistration;

        try {
            replace(await action(section.id));
            notify(
                section.status === 'open' ? 'Registration closed.' : 'Registration opened.',
                'success',
            );
        } catch (error) {
            notify(readApiError(error, 'Unable to change the section status.').message, 'error');
        }
    };

    const saveCapacity = async (section) => {
        const value = Number(capacityDraft);

        if (!Number.isInteger(value) || value <= 0) {
            notify('The capacity must be a whole number greater than zero.', 'error');

            return;
        }

        try {
            replace(await administrationService.updateCapacity(section.id, value));
            setEditingId(null);
            notify('Capacity updated.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to update the capacity.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Sections" subtitle="Capacity, registration and rosters." />
                <SkeletonRows rows={5} height={56} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Sections"
                subtitle={`${sections.length} section${sections.length === 1 ? '' : 's'} this semester.`}
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-toolbar">
                <label className="nsu-search">
                    <Search size={16} aria-hidden="true" />
                    <input
                        type="search"
                        className="nsu-search__input"
                        placeholder="Search by course, lecturer or classroom"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        aria-label="Search sections"
                    />
                </label>
            </div>

            {visible.length === 0 ? (
                <EmptyState
                    icon={BookOpen}
                    title="No sections match"
                    description="Try a different search term."
                />
            ) : (
                <div className="nsu-card">
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Lecturer</th>
                                    <th>Room</th>
                                    <th className="nsu-table__number">Enrolled</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visible.map((section) => (
                                    <Fragment key={section.id}>
                                        <tr>
                                            <td>
                                                {section.course_code} · Section {section.section_number}
                                                <span className="nsu-table__hint">
                                                    {section.course_name}
                                                </span>
                                            </td>
                                            <td>{section.lecturer_name ?? 'Unassigned'}</td>
                                            <td>{section.classroom ?? '—'}</td>
                                            <td className="nsu-table__number">
                                                {editingId === section.id ? (
                                                    <span className="nsu-capacity-edit">
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            className="nsu-field__input nsu-capacity-edit__input"
                                                            value={capacityDraft}
                                                            onChange={(event) =>
                                                                setCapacityDraft(event.target.value)
                                                            }
                                                            aria-label="New capacity"
                                                        />
                                                        <button
                                                            type="button"
                                                            className="nsu-icon-button"
                                                            onClick={() => saveCapacity(section)}
                                                            aria-label="Save capacity"
                                                        >
                                                            <Check size={16} />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="nsu-icon-button"
                                                            onClick={() => setEditingId(null)}
                                                            aria-label="Cancel"
                                                        >
                                                            <X size={16} />
                                                        </button>
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        className="nsu-capacity"
                                                        onClick={() => {
                                                            setEditingId(section.id);
                                                            setCapacityDraft(String(section.capacity));
                                                        }}
                                                        aria-label={`Change capacity, currently ${section.capacity}`}
                                                    >
                                                        <Badge
                                                            variant={fillVariant(
                                                                section.registered_students,
                                                                section.capacity,
                                                            )}
                                                        >
                                                            {section.registered_students} / {section.capacity}
                                                        </Badge>
                                                    </button>
                                                )}
                                            </td>
                                            <td>
                                                <Badge variant={statusVariants[section.status] ?? 'neutral'}>
                                                    {section.status}
                                                </Badge>
                                            </td>
                                            <td>
                                                <div className="nsu-table__actions">
                                                    <Button
                                                        variant="ghost"
                                                        icon={section.status === 'open' ? Lock : Unlock}
                                                        onClick={() => changeStatus(section)}
                                                    >
                                                        {section.status === 'open' ? 'Close' : 'Open'}
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        icon={Users}
                                                        onClick={() => toggleRoster(section)}
                                                    >
                                                        {expandedId === section.id ? 'Hide' : 'Roster'}
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>

                                        {expandedId === section.id && (
                                            <tr>
                                                <td colSpan={6} className="nsu-roster-cell">
                                                    {roster.length === 0 ? (
                                                        <p className="nsu-roster__empty">
                                                            Nobody is enrolled in this section yet.
                                                        </p>
                                                    ) : (
                                                        <ul className="nsu-roster">
                                                            {roster.map((student) => (
                                                                <li className="nsu-roster__row" key={student.id}>
                                                                    <span>
                                                                        {student.full_name}
                                                                        <span className="nsu-table__hint">
                                                                            {student.student_number}
                                                                        </span>
                                                                    </span>
                                                                    <Badge
                                                                        variant={
                                                                            student.enrollment_status ===
                                                                            'Approved'
                                                                                ? 'success'
                                                                                : 'warning'
                                                                        }
                                                                    >
                                                                        {student.enrollment_status}
                                                                    </Badge>
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </td>
                                            </tr>
                                        )}
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </>
    );
}
