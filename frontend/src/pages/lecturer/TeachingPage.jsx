import { useCallback, useEffect, useState } from 'react';
import { Bell, Megaphone, NotebookPen, Upload } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Button } from '../../components/Button';
import { FormField } from '../../components/FormField';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { lmsService } from '../../services/lmsService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

export function TeachingPage() {
    const { notify } = useToast();

    const [sections, setSections] = useState([]);
    const [sectionId, setSectionId] = useState('');
    const [materials, setMaterials] = useState([]);
    const [assignments, setAssignments] = useState([]);
    const [announcements, setAnnouncements] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const [materialTitle, setMaterialTitle] = useState('');
    const [materialFile, setMaterialFile] = useState(null);
    const [isUploading, setIsUploading] = useState(false);

    const [announcementTitle, setAnnouncementTitle] = useState('');
    const [announcementContent, setAnnouncementContent] = useState('');
    const [isPublishing, setIsPublishing] = useState(false);

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

        const [materialData, assignmentData, announcementData] = await Promise.all([
            lmsService.materials(id).catch(() => []),
            lmsService.assignments(id).catch(() => []),
            lmsService.announcements(id).catch(() => []),
        ]);

        setMaterials(materialData);
        setAssignments(assignmentData);
        setAnnouncements(announcementData);
    }, []);

    useEffect(() => {
        refresh(sectionId);
    }, [sectionId, refresh]);

    async function handleUpload(event) {
        event.preventDefault();

        if (!materialFile || !materialTitle.trim()) {
            return;
        }

        setIsUploading(true);

        try {
            await lmsService.uploadMaterial({
                sectionId: Number(sectionId),
                title: materialTitle.trim(),
                visibility: 'visible',
                file: materialFile,
            });

            notify('Material uploaded.');
            setMaterialTitle('');
            setMaterialFile(null);
            event.target.reset();

            await refresh(sectionId);
        } catch (error) {
            notify(readApiError(error, 'Upload failed.').message, 'error');
        } finally {
            setIsUploading(false);
        }
    }

    async function handleAnnounce(event) {
        event.preventDefault();

        if (!announcementTitle.trim() || !announcementContent.trim()) {
            return;
        }

        setIsPublishing(true);

        try {
            await lmsService.createAnnouncement({
                section_id: Number(sectionId),
                title: announcementTitle.trim(),
                content: announcementContent.trim(),
            });

            notify('Announcement published.');
            setAnnouncementTitle('');
            setAnnouncementContent('');

            await refresh(sectionId);
        } catch (error) {
            notify(readApiError(error, 'Unable to publish.').message, 'error');
        } finally {
            setIsPublishing(false);
        }
    }

    if (isLoading) {
        return (
            <>
                <PageHeader title="Teaching" subtitle="Manage content for your sections." />
                <SkeletonRows rows={3} height={80} />
            </>
        );
    }

    if (sections.length === 0) {
        return (
            <>
                <PageHeader title="Teaching" subtitle="Manage content for your sections." />
                <div className="nsu-card">
                    <EmptyState
                        icon={NotebookPen}
                        title="No sections assigned"
                        description="Sections you teach will appear here once a coordinator assigns them."
                    />
                </div>
            </>
        );
    }

    return (
        <>
            <PageHeader title="Teaching" subtitle="Manage content for your sections." />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-field" style={{ maxWidth: 520 }}>
                <label className="nsu-field__label" htmlFor="teaching-section">
                    Section
                </label>
                <select
                    id="teaching-section"
                    className="nsu-field__input"
                    value={sectionId}
                    onChange={(event) => setSectionId(event.target.value)}
                >
                    {sections.map((section) => (
                        <option key={section.id} value={section.id}>
                            {section.course_code} - {section.course_name} ({section.section_number})
                        </option>
                    ))}
                </select>
            </div>

            <div
                className="nsu-grid"
                style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))' }}
            >
                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h2 className="nsu-section-title">Upload material</h2>

                        <form onSubmit={handleUpload}>
                            <FormField
                                label="Title"
                                value={materialTitle}
                                onChange={(event) => setMaterialTitle(event.target.value)}
                                required
                            />

                            <div className="nsu-field">
                                <label className="nsu-field__label" htmlFor="material-file">
                                    File
                                    <span className="nsu-field__required" aria-hidden="true">
                                        *
                                    </span>
                                </label>
                                <input
                                    id="material-file"
                                    className="nsu-field__input"
                                    type="file"
                                    onChange={(event) => setMaterialFile(event.target.files[0] ?? null)}
                                    required
                                    style={{ paddingTop: 10 }}
                                />
                                <span className="nsu-field__helper">
                                    PDF, Office documents, images, text or ZIP up to 25 MB.
                                </span>
                            </div>

                            <Button
                                type="submit"
                                icon={Upload}
                                isLoading={isUploading}
                                disabled={!materialFile || !materialTitle.trim()}
                            >
                                Upload
                            </Button>
                        </form>
                    </div>
                </section>

                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h2 className="nsu-section-title">Publish announcement</h2>

                        <form onSubmit={handleAnnounce}>
                            <FormField
                                label="Title"
                                value={announcementTitle}
                                onChange={(event) => setAnnouncementTitle(event.target.value)}
                                required
                            />

                            <div className="nsu-field">
                                <label className="nsu-field__label" htmlFor="announcement-content">
                                    Message
                                    <span className="nsu-field__required" aria-hidden="true">
                                        *
                                    </span>
                                </label>
                                <textarea
                                    id="announcement-content"
                                    className="nsu-field__input"
                                    rows={4}
                                    value={announcementContent}
                                    onChange={(event) => setAnnouncementContent(event.target.value)}
                                    required
                                    style={{ paddingTop: 10, minHeight: 110, resize: 'vertical' }}
                                />
                            </div>

                            <Button
                                type="submit"
                                icon={Megaphone}
                                isLoading={isPublishing}
                                disabled={!announcementTitle.trim() || !announcementContent.trim()}
                            >
                                Publish
                            </Button>
                        </form>
                    </div>
                </section>
            </div>

            <h2 className="nsu-section-title" style={{ marginTop: 'var(--space-xl)' }}>
                Section content
            </h2>

            <div
                className="nsu-grid"
                style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))' }}
            >
                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h3 className="nsu-section-title">Materials ({materials.length})</h3>
                        {materials.length === 0 ? (
                            <p style={{ color: 'var(--color-muted-foreground)', margin: 0 }}>
                                Nothing uploaded yet.
                            </p>
                        ) : (
                            <ul style={{ margin: 0, paddingLeft: 'var(--space-lg)' }}>
                                {materials.map((item) => (
                                    <li key={item.id}>
                                        {item.title}{' '}
                                        <Badge
                                            variant={
                                                item.visibility === 'hidden' ? 'warning' : 'success'
                                            }
                                        >
                                            {item.visibility}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h3 className="nsu-section-title">Assignments ({assignments.length})</h3>
                        {assignments.length === 0 ? (
                            <p style={{ color: 'var(--color-muted-foreground)', margin: 0 }}>
                                No assignments yet.
                            </p>
                        ) : (
                            <ul style={{ margin: 0, paddingLeft: 'var(--space-lg)' }}>
                                {assignments.map((item) => (
                                    <li key={item.id}>
                                        {item.title} - {item.summary?.submitted ?? 0} submitted
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h3 className="nsu-section-title">
                            <Bell size={16} aria-hidden="true" /> Announcements ({announcements.length})
                        </h3>
                        {announcements.length === 0 ? (
                            <p style={{ color: 'var(--color-muted-foreground)', margin: 0 }}>
                                Nothing published yet.
                            </p>
                        ) : (
                            <ul style={{ margin: 0, paddingLeft: 'var(--space-lg)' }}>
                                {announcements.map((item) => (
                                    <li key={item.id}>{item.title}</li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}
