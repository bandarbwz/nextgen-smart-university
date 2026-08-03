import { useEffect, useState } from 'react';
import { BarChart3, Download, FileSpreadsheet, FileText, Play } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Badge } from '../components/Badge';
import { Button } from '../components/Button';
import { Alert } from '../components/Alert';
import { reportService } from '../services/reportService';
import { readApiError } from '../services/apiClient';
import { useToast } from '../hooks/useToast';

function heading(column) {
    return column.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function ReportsPage() {
    const { notify } = useToast();

    const [reports, setReports] = useState([]);
    const [activeKey, setActiveKey] = useState(null);
    const [result, setResult] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isRunning, setIsRunning] = useState(false);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        reportService
            .available()
            .then(setReports)
            .catch((error) => setNotice(readApiError(error, 'Unable to load reports.').message))
            .finally(() => setIsLoading(false));
    }, []);

    async function runReport(key) {
        setActiveKey(key);
        setIsRunning(true);
        setResult(null);

        try {
            setResult(await reportService.run(key));
        } catch (error) {
            notify(readApiError(error, 'Unable to run this report.').message, 'error');
        } finally {
            setIsRunning(false);
        }
    }

    async function exportReport(format) {
        try {
            await reportService.export(activeKey, format);

            notify(`Exported as ${format.toUpperCase()}.`);
        } catch (error) {
            notify(readApiError(error, 'Export failed.').message, 'error');
        }
    }

    const grouped = reports.reduce((groups, report) => {
        groups[report.category] = groups[report.category] ?? [];
        groups[report.category].push(report);

        return groups;
    }, {});

    if (isLoading) {
        return (
            <>
                <PageHeader title="Reports" subtitle="Reporting across every module." />
                <SkeletonRows rows={4} height={72} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Reports"
                subtitle="Only the reports your role is allowed to run are listed."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {reports.length === 0 ? (
                <div className="nsu-card">
                    <EmptyState
                        icon={BarChart3}
                        title="No reports available"
                        description="Your role does not currently have access to any reports."
                    />
                </div>
            ) : (
                <div
                    className="nsu-grid"
                    style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))' }}
                >
                    {Object.entries(grouped).map(([category, items]) => (
                        <section className="nsu-card" key={category}>
                            <div className="nsu-card__body">
                                <h2 className="nsu-section-title">{category}</h2>

                                <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                                    {items.map((report) => (
                                        <li key={report.key} className="nsu-menu-line">
                                            <span style={{ fontSize: 'var(--text-sm)' }}>
                                                {report.name}
                                            </span>
                                            <Button
                                                variant="ghost"
                                                icon={Play}
                                                onClick={() => runReport(report.key)}
                                            >
                                                Run
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </section>
                    ))}
                </div>
            )}

            {activeKey && (
                <section style={{ marginTop: 'var(--space-xl)' }}>
                    <div className="nsu-page-header">
                        <div>
                            <h2 className="nsu-section-title">
                                {result?.title ?? 'Running report'}
                            </h2>
                            {result && (
                                <p className="nsu-page-header__subtitle">
                                    {result.rows.length} rows — generated {result.generated_at} UTC
                                </p>
                            )}
                        </div>

                        {result && (
                            <div style={{ display: 'flex', gap: 'var(--space-sm)', flexWrap: 'wrap' }}>
                                <Button variant="secondary" icon={FileText} onClick={() => exportReport('pdf')}>
                                    PDF
                                </Button>
                                <Button
                                    variant="secondary"
                                    icon={FileSpreadsheet}
                                    onClick={() => exportReport('xlsx')}
                                >
                                    Excel
                                </Button>
                                <Button variant="secondary" icon={Download} onClick={() => exportReport('csv')}>
                                    CSV
                                </Button>
                            </div>
                        )}
                    </div>

                    <div className="nsu-card">
                        {isRunning ? (
                            <div style={{ padding: 'var(--space-md)' }}>
                                <SkeletonRows rows={4} height={40} />
                            </div>
                        ) : result === null ? (
                            <EmptyState icon={BarChart3} title="Nothing to show" />
                        ) : result.rows.length === 0 ? (
                            <EmptyState
                                icon={BarChart3}
                                title="No data"
                                description="This report returned no rows for the current parameters."
                            />
                        ) : (
                            <div className="nsu-table-wrap">
                                <table className="nsu-table">
                                    <caption className="visually-hidden">{result.title}</caption>
                                    <thead>
                                        <tr>
                                            {result.columns.map((column) => (
                                                <th scope="col" key={column}>
                                                    {heading(column)}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {result.rows.map((row, index) => (
                                            <tr key={index}>
                                                {result.columns.map((column) => (
                                                    <td className="tabular" key={column}>
                                                        {row[column] ?? ''}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {result?.summary && (
                        <p style={{ marginTop: 'var(--space-md)' }}>
                            <Badge variant="success">
                                CGPA {result.summary.cgpa} — {result.summary.completed_credit_hours}{' '}
                                credits earned
                            </Badge>
                        </p>
                    )}
                </section>
            )}
        </>
    );
}
