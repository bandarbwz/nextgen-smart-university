import { useEffect, useState } from 'react';
import { CalendarDays, Download, FolderOpen, ScrollText } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { EmptyState } from '../components/EmptyState';
import { SkeletonRows } from '../components/Skeleton';
import { Badge } from '../components/Badge';
import { Button } from '../components/Button';
import { Alert } from '../components/Alert';
import { reportService } from '../services/reportService';
import { readApiError } from '../services/apiClient';
import { useAuth } from '../hooks/useAuth';
import { useToast } from '../hooks/useToast';

function formatBytes(bytes) {
    const value = Number(bytes);

    if (!value) {
        return '';
    }

    return value >= 1048576
        ? `${(value / 1048576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(value / 1024))} KB`;
}

export function DownloadCenterPage() {
    const { user } = useAuth();
    const { notify } = useToast();

    const [files, setFiles] = useState([]);
    const [history, setHistory] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const isStudent = user.role === 'Student';

    useEffect(() => {
        Promise.all([reportService.files(), reportService.history()])
            .then(([fileData, historyData]) => {
                setFiles(fileData);
                setHistory(historyData);
            })
            .catch((error) =>
                setNotice(readApiError(error, 'Unable to load the download center.').message),
            )
            .finally(() => setIsLoading(false));
    }, []);

    async function download(file) {
        try {
            await reportService.downloadFile(file.id, file.file_name);

            notify('Download started.');
            setHistory(await reportService.history());
        } catch (error) {
            notify(readApiError(error, 'Unable to download this file.').message, 'error');
        }
    }

    async function downloadDocument(kind) {
        try {
            if (kind === 'transcript') {
                await reportService.downloadTranscript('pdf');
            } else {
                await reportService.downloadSchedule('pdf');
            }

            notify('Document generated.');
            setHistory(await reportService.history());
        } catch (error) {
            notify(readApiError(error, 'Unable to generate this document.').message, 'error');
        }
    }

    if (isLoading) {
        return (
            <>
                <PageHeader title="Download center" subtitle="Documents available to your role." />
                <SkeletonRows rows={4} height={72} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Download center"
                subtitle="Documents available to your role, and your download history."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {isStudent && (
                <>
                    <h2 className="nsu-section-title">My documents</h2>

                    <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                        <div
                            className="nsu-card__body"
                            style={{ display: 'flex', gap: 'var(--space-sm)', flexWrap: 'wrap' }}
                        >
                            <Button
                                variant="secondary"
                                icon={ScrollText}
                                onClick={() => downloadDocument('transcript')}
                            >
                                Transcript (PDF)
                            </Button>
                            <Button
                                variant="secondary"
                                icon={CalendarDays}
                                onClick={() => downloadDocument('schedule')}
                            >
                                Class schedule (PDF)
                            </Button>
                        </div>
                    </div>
                </>
            )}

            <h2 className="nsu-section-title">Documents</h2>

            <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                {files.length === 0 ? (
                    <EmptyState
                        icon={FolderOpen}
                        title="No documents available"
                        description="Documents published for your role will appear here."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Available documents</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Title</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Downloads</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {files.map((file) => (
                                    <tr key={file.id}>
                                        <td>{file.title}</td>
                                        <td>
                                            <Badge>{file.category}</Badge>
                                        </td>
                                        <td className="tabular">{formatBytes(file.file_size)}</td>
                                        <td className="tabular">{file.download_count}</td>
                                        <td>
                                            <Button
                                                variant="ghost"
                                                icon={Download}
                                                onClick={() => download(file)}
                                            >
                                                Download
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <h2 className="nsu-section-title">Download history</h2>

            <div className="nsu-card">
                {history.length === 0 ? (
                    <EmptyState
                        icon={Download}
                        title="Nothing downloaded yet"
                        description="Every download is recorded here for audit purposes."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your download history</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Document</th>
                                    <th scope="col">Downloaded</th>
                                    <th scope="col">From</th>
                                </tr>
                            </thead>
                            <tbody>
                                {history.map((entry) => (
                                    <tr key={entry.id}>
                                        <td>{entry.file_title}</td>
                                        <td className="tabular">{entry.downloaded_at}</td>
                                        <td className="tabular">{entry.ip_address}</td>
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
