import { useCallback, useEffect, useState } from 'react';
import { CalendarCheck, ScanLine } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { FormField } from '../../components/FormField';
import { Alert } from '../../components/Alert';
import { attendanceService } from '../../services/attendanceService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    Present: 'success',
    Online: 'success',
    Late: 'warning',
    Excused: 'neutral',
    Absent: 'danger',
    Pending: 'warning',
};

function rateVariant(rate) {
    if (rate >= 80) {
        return 'success';
    }

    return rate >= 60 ? 'warning' : 'danger';
}

export function AttendancePage() {
    const { notify } = useToast();

    const [records, setRecords] = useState([]);
    const [statistics, setStatistics] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const [token, setToken] = useState('');
    const [isScanning, setIsScanning] = useState(false);

    const load = useCallback(async () => {
        try {
            const data = await attendanceService.myAttendance();

            setRecords(data.attendance);
            setStatistics(data.statistics);
            setNotice('');
        } catch (error) {
            setNotice(readApiError(error, 'Unable to load your attendance.').message);
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    function requestPosition() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ latitude: undefined, longitude: undefined });

                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) =>
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                    }),
                () => resolve({ latitude: undefined, longitude: undefined }),
                { timeout: 8000 },
            );
        });
    }

    async function handleScan(event) {
        event.preventDefault();

        if (!token.trim()) {
            return;
        }

        setIsScanning(true);

        try {
            const position = await requestPosition();

            await attendanceService.scan({ qrToken: token.trim(), ...position });

            notify('Attendance recorded.');
            setToken('');

            await load();
        } catch (error) {
            notify(readApiError(error, 'Unable to record attendance.').message, 'error');
        } finally {
            setIsScanning(false);
        }
    }

    return (
        <>
            <PageHeader
                title="My attendance"
                subtitle="Record your attendance and review your history."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <section className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                <div className="nsu-card__body">
                    <h2 className="nsu-section-title">Record attendance</h2>

                    <form onSubmit={handleScan} noValidate>
                        <FormField
                            label="Attendance code"
                            value={token}
                            onChange={(event) => setToken(event.target.value)}
                            helper="Enter the code shown by your lecturer. Your location is shared when the session requires it."
                            placeholder="Paste or type the session code"
                        />

                        <Button
                            type="submit"
                            icon={ScanLine}
                            isLoading={isScanning}
                            disabled={!token.trim()}
                        >
                            {isScanning ? 'Recording' : 'Record attendance'}
                        </Button>
                    </form>
                </div>
            </section>

            <h2 className="nsu-section-title">Attendance rate by course</h2>

            {isLoading ? (
                <SkeletonRows rows={2} height={72} />
            ) : statistics.length === 0 ? (
                <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                    <EmptyState
                        icon={CalendarCheck}
                        title="No attendance recorded yet"
                        description="Once you record attendance for a class it will be summarised here."
                    />
                </div>
            ) : (
                <div
                    className="nsu-grid nsu-grid--stats"
                    style={{ marginBottom: 'var(--space-xl)' }}
                >
                    {statistics.map((item) => (
                        <article className="nsu-card" key={item.section_id}>
                            <div className="nsu-stat">
                                <div>
                                    <p className="nsu-stat__label">
                                        {item.course_code} - {item.course_name}
                                    </p>
                                    <p className="nsu-stat__value tabular">{item.attendance_rate}%</p>
                                    <p className="nsu-stat__hint">
                                        {item.attended} attended, {item.excused} excused,{' '}
                                        {item.absent} absent of {item.total_sessions}
                                    </p>
                                </div>
                                <Badge variant={rateVariant(item.attendance_rate)}>
                                    {item.attendance_rate >= 80 ? 'Good' : 'At risk'}
                                </Badge>
                            </div>
                        </article>
                    ))}
                </div>
            )}

            <h2 className="nsu-section-title">History</h2>

            <div className="nsu-card">
                {isLoading ? (
                    <div style={{ padding: 'var(--space-md)' }}>
                        <SkeletonRows rows={4} height={44} />
                    </div>
                ) : records.length === 0 ? (
                    <EmptyState
                        icon={CalendarCheck}
                        title="No attendance history"
                        description="Your recorded sessions will appear here."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Attendance history</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {records.map((record) => (
                                    <tr key={record.id}>
                                        <td className="tabular">{record.attendance_date}</td>
                                        <td className="tabular">{record.course_code}</td>
                                        <td>{record.course_name}</td>
                                        <td>{record.attendance_method}</td>
                                        <td>
                                            <Badge
                                                variant={
                                                    statusVariants[record.attendance_status] ?? 'neutral'
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
    );
}
