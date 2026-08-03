import { useCallback, useEffect, useState } from 'react';
import { PlayCircle, StopCircle, Users } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { attendanceService } from '../../services/attendanceService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    Present: 'success',
    Online: 'success',
    Late: 'warning',
    Excused: 'neutral',
    Absent: 'danger',
};

export function AttendanceSessionPage() {
    const { notify } = useToast();

    const [sections, setSections] = useState([]);
    const [sectionId, setSectionId] = useState('');
    const [session, setSession] = useState(null);
    const [records, setRecords] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isBusy, setIsBusy] = useState(false);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        academicService
            .sections()
            .then((data) => {
                setSections(data);

                if (data.length > 0) {
                    setSectionId(String(data[0].id));
                }
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load sections.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const refresh = useCallback(async (id) => {
        if (!id) {
            return;
        }

        const active = await attendanceService.activeSession(id).catch(() => null);

        setSession(active);

        const attendance = await attendanceService
            .sectionAttendance(id, new Date().toISOString().slice(0, 10))
            .catch(() => []);

        setRecords(attendance);
    }, []);

    useEffect(() => {
        refresh(sectionId);
    }, [sectionId, refresh]);

    async function handleOpen() {
        setIsBusy(true);

        try {
            const created = await attendanceService.openSession({ sectionId: Number(sectionId) });

            setSession(created);
            notify('Attendance session opened.');
        } catch (error) {
            notify(readApiError(error, 'Unable to open the session.').message, 'error');
        } finally {
            setIsBusy(false);
        }
    }

    async function handleClose() {
        setIsBusy(true);

        try {
            await attendanceService.closeSession(session.id);

            notify('Attendance session closed.');

            await refresh(sectionId);
        } catch (error) {
            notify(readApiError(error, 'Unable to close the session.').message, 'error');
        } finally {
            setIsBusy(false);
        }
    }

    const isOpen = session !== null && session.status === 'active';

    return (
        <>
            <PageHeader
                title="Attendance session"
                subtitle="Open a session and share the code with your class."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {isLoading ? (
                <SkeletonRows rows={2} height={80} />
            ) : sections.length === 0 ? (
                <div className="nsu-card">
                    <EmptyState
                        icon={Users}
                        title="No sections assigned"
                        description="Sections you teach will appear here once a coordinator assigns them."
                    />
                </div>
            ) : (
                <>
                    <section className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                        <div className="nsu-card__body">
                            <div className="nsu-field">
                                <label className="nsu-field__label" htmlFor="section-select">
                                    Section
                                </label>
                                <select
                                    id="section-select"
                                    className="nsu-field__input"
                                    value={sectionId}
                                    onChange={(event) => setSectionId(event.target.value)}
                                >
                                    {sections.map((section) => (
                                        <option key={section.id} value={section.id}>
                                            {section.course_code} - {section.course_name} (
                                            {section.section_number})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {isOpen ? (
                                <>
                                    <Alert variant="success">
                                        Session is open. Expires at {session.expires_at} UTC.
                                    </Alert>

                                    <p className="nsu-field__label">Attendance code</p>
                                    <p
                                        className="tabular"
                                        style={{
                                            fontFamily: 'var(--font-heading)',
                                            fontSize: 'var(--text-lg)',
                                            wordBreak: 'break-all',
                                            padding: 'var(--space-md)',
                                            background: 'var(--color-muted)',
                                            borderRadius: 'var(--radius-md)',
                                            marginTop: 0,
                                        }}
                                    >
                                        {session.qr_token}
                                    </p>

                                    <Button
                                        variant="danger"
                                        icon={StopCircle}
                                        isLoading={isBusy}
                                        onClick={handleClose}
                                    >
                                        Close session
                                    </Button>
                                </>
                            ) : (
                                <Button icon={PlayCircle} isLoading={isBusy} onClick={handleOpen}>
                                    Open attendance session
                                </Button>
                            )}
                        </div>
                    </section>

                    <h2 className="nsu-section-title">Today&apos;s attendance</h2>

                    <div className="nsu-card">
                        {records.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title="No attendance recorded today"
                                description="Students who record attendance will appear here."
                            />
                        ) : (
                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <caption className="visually-hidden">
                                        Attendance recorded today
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Student number</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Time</th>
                                            <th scope="col">Method</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {records.map((record) => (
                                            <tr key={record.id}>
                                                <td className="tabular">{record.student_number}</td>
                                                <td>{record.student_name}</td>
                                                <td className="tabular">{record.attendance_time}</td>
                                                <td>{record.attendance_method}</td>
                                                <td>
                                                    <Badge
                                                        variant={
                                                            statusVariants[record.attendance_status] ??
                                                            'neutral'
                                                        }
                                                    >
                                                        {record.attendance_status}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </>
            )}
        </>
    );
}
