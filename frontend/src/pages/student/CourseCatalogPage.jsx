import { useEffect, useState } from 'react';
import { Library, Search } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { academicService } from '../../services/academicService';
import { readApiError } from '../../services/apiClient';

export function CourseCatalogPage() {
    const [courses, setCourses] = useState([]);
    const [search, setSearch] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        let isActive = true;
        const timer = setTimeout(() => {
            setIsLoading(true);

            academicService
                .courses(search.trim() ? { search: search.trim() } : {})
                .then((data) => {
                    if (isActive) {
                        setCourses(data);
                        setNotice('');
                    }
                })
                .catch((error) => {
                    if (isActive) {
                        setNotice(readApiError(error, 'Unable to load courses.').message);
                    }
                })
                .finally(() => {
                    if (isActive) {
                        setIsLoading(false);
                    }
                });
        }, 300);

        return () => {
            isActive = false;
            clearTimeout(timer);
        };
    }, [search]);

    return (
        <>
            <PageHeader
                title="Course catalog"
                subtitle="Browse every course offered across the university."
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <div className="nsu-toolbar">
                <div className="nsu-toolbar__search nsu-field" style={{ marginBottom: 0 }}>
                    <label className="visually-hidden" htmlFor="course-search">
                        Search courses
                    </label>
                    <div className="nsu-field__control">
                        <input
                            id="course-search"
                            className="nsu-field__input"
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by course code or name"
                        />
                    </div>
                </div>
            </div>

            <div className="nsu-card">
                {isLoading ? (
                    <div style={{ padding: 'var(--space-md)' }}>
                        <SkeletonRows rows={5} height={44} />
                    </div>
                ) : courses.length === 0 ? (
                    <EmptyState
                        icon={search ? Search : Library}
                        title={search ? 'No courses match your search' : 'No courses available'}
                        description={
                            search
                                ? 'Try a different course code or keyword.'
                                : 'Courses will appear here once the administration adds them.'
                        }
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Course catalog</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Course name</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Credits</th>
                                    <th scope="col">Level</th>
                                    <th scope="col">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                {courses.map((course) => (
                                    <tr key={course.id}>
                                        <td className="tabular">{course.course_code}</td>
                                        <td>{course.course_name}</td>
                                        <td>{course.department_name}</td>
                                        <td className="tabular">{course.credit_hours}</td>
                                        <td className="tabular">{course.level}</td>
                                        <td>
                                            <Badge
                                                variant={
                                                    course.course_type === 'Core' ? 'success' : 'neutral'
                                                }
                                            >
                                                {course.course_type}
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
