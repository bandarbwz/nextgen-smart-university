import { useEffect, useState } from 'react';
import { Bell, Download, FileText, GraduationCap, Link2, NotebookPen } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { Alert } from '../../components/Alert';
import { lmsService } from '../../services/lmsService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const tabs = [
    { key: 'announcements', label: 'Announcements', icon: Bell },
    { key: 'materials', label: 'Materials', icon: FileText },
    { key: 'assignments', label: 'Assignments', icon: NotebookPen },
    { key: 'quizzes', label: 'Quizzes', icon: GraduationCap },
    { key: 'resources', label: 'Resources', icon: Link2 },
    { key: 'grades', label: 'Grades', icon: GraduationCap },
];

function formatBytes(bytes) {
    const value = Number(bytes);

    if (!value) {
        return '';
    }

    return value >= 1048576
        ? `${(value / 1048576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(value / 1024))} KB`;
}

export function CourseContentPage() {
    const { notify } = useToast();

    const [activeTab, setActiveTab] = useState('announcements');
    const [content, setContent] = useState({});
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        Promise.all([
            lmsService.announcements(),
            lmsService.materials(),
            lmsService.assignments(),
            lmsService.quizzes(),
            lmsService.resources(),
            lmsService.grades(),
        ])
            .then(([announcements, materials, assignments, quizzes, resources, grades]) =>
                setContent({ announcements, materials, assignments, quizzes, resources, grades }),
            )
            .catch((error) => setNotice(readApiError(error, 'Unable to load course content.').message))
            .finally(() => setIsLoading(false));
    }, []);

    async function handleDownload(material) {
        try {
            await lmsService.downloadMaterial(material.id, material.original_name);
        } catch (error) {
            notify(readApiError(error, 'Unable to download this file.').message, 'error');
        }
    }

    const items = content[activeTab] ?? [];

    return (
        <>
            <PageHeader
                title="Course content"
                subtitle="Everything shared by your lecturers across your enrolled courses."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-tabs" role="tablist" aria-label="Course content sections">
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        role="tab"
                        id={`tab-${tab.key}`}
                        aria-selected={activeTab === tab.key}
                        aria-controls={`panel-${tab.key}`}
                        className={`nsu-tab ${activeTab === tab.key ? 'nsu-tab--active' : ''}`}
                        onClick={() => setActiveTab(tab.key)}
                    >
                        <tab.icon size={16} aria-hidden="true" />
                        {tab.label}
                        {(content[tab.key] ?? []).length > 0 && (
                            <span className="nsu-tab__count">{content[tab.key].length}</span>
                        )}
                    </button>
                ))}
            </div>

            <div
                className="nsu-card"
                role="tabpanel"
                id={`panel-${activeTab}`}
                aria-labelledby={`tab-${activeTab}`}
            >
                {isLoading ? (
                    <div style={{ padding: 'var(--space-md)' }}>
                        <SkeletonRows rows={4} height={48} />
                    </div>
                ) : items.length === 0 ? (
                    <EmptyState
                        icon={tabs.find((tab) => tab.key === activeTab).icon}
                        title={`No ${activeTab} yet`}
                        description="Content published by your lecturers will appear here."
                    />
                ) : activeTab === 'announcements' ? (
                    <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                        {items.map((item) => (
                            <li key={item.id} className="nsu-feed-item">
                                <div className="nsu-feed-item__head">
                                    <h3 className="nsu-feed-item__title">{item.title}</h3>
                                    <Badge>{item.course_code}</Badge>
                                </div>
                                <p className="nsu-feed-item__body">{item.content}</p>
                                <p className="nsu-feed-item__meta">
                                    {item.lecturer_name} - {item.published_at}
                                </p>
                            </li>
                        ))}
                    </ul>
                ) : activeTab === 'materials' ? (
                    <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                        {items.map((item) => (
                            <li key={item.id} className="nsu-feed-item">
                                <div className="nsu-feed-item__head">
                                    <h3 className="nsu-feed-item__title">{item.title}</h3>
                                    <Badge>{item.course_code}</Badge>
                                </div>
                                {item.description && (
                                    <p className="nsu-feed-item__body">{item.description}</p>
                                )}
                                <p className="nsu-feed-item__meta">
                                    {item.original_name} - {formatBytes(item.file_size)}
                                </p>
                                <Button
                                    variant="secondary"
                                    icon={Download}
                                    onClick={() => handleDownload(item)}
                                >
                                    Download
                                </Button>
                            </li>
                        ))}
                    </ul>
                ) : activeTab === 'resources' ? (
                    <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
                        {items.map((item) => (
                            <li key={item.id} className="nsu-feed-item">
                                <div className="nsu-feed-item__head">
                                    <h3 className="nsu-feed-item__title">{item.title}</h3>
                                    <Badge>{item.resource_type}</Badge>
                                </div>
                                <a href={item.link} target="_blank" rel="noopener noreferrer">
                                    Open resource
                                </a>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">{activeTab}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Course</th>
                                    <th scope="col">Title</th>
                                    {activeTab === 'assignments' && <th scope="col">Due</th>}
                                    {activeTab === 'quizzes' && <th scope="col">Opens</th>}
                                    <th scope="col">
                                        {activeTab === 'grades' ? 'Marks' : 'Total marks'}
                                    </th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr key={item.id}>
                                        <td className="tabular">{item.course_code}</td>
                                        <td>{item.title}</td>
                                        {activeTab === 'assignments' && (
                                            <td className="tabular">{item.due_date}</td>
                                        )}
                                        {activeTab === 'quizzes' && (
                                            <td className="tabular">{item.start_time}</td>
                                        )}
                                        <td className="tabular">
                                            {activeTab === 'grades'
                                                ? `${item.marks} / ${item.total_marks}`
                                                : item.total_marks}
                                        </td>
                                        <td>
                                            {activeTab === 'assignments' && (
                                                <Badge
                                                    variant={
                                                        item.my_submission ? 'success' : 'warning'
                                                    }
                                                >
                                                    {item.my_submission
                                                        ? item.my_submission.submission_status
                                                        : 'Not submitted'}
                                                </Badge>
                                            )}
                                            {activeTab === 'quizzes' && (
                                                <Badge
                                                    variant={
                                                        item.attempts_used > 0 ? 'success' : 'neutral'
                                                    }
                                                >
                                                    {item.attempts_used > 0
                                                        ? 'Attempted'
                                                        : `${item.attempts} attempt(s)`}
                                                </Badge>
                                            )}
                                            {activeTab === 'grades' && (
                                                <Badge variant="success">{item.grade_letter}</Badge>
                                            )}
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
