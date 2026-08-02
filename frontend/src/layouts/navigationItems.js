import {
    BookOpen,
    CalendarCheck,
    CalendarDays,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    Library,
    ScanLine,
    ScrollText,
    Users,
} from 'lucide-react';

export const navigationGroups = [
    {
        label: 'Overview',
        items: [
            { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: null },
        ],
    },
    {
        label: 'Academic',
        items: [
            { to: '/courses', label: 'Course Catalog', icon: Library, roles: null },
            { to: '/registration', label: 'Course Registration', icon: ClipboardList, roles: ['Student'] },
            { to: '/schedule', label: 'My Schedule', icon: CalendarDays, roles: ['Student'] },
            { to: '/attendance', label: 'My Attendance', icon: CalendarCheck, roles: ['Student'] },
            { to: '/transcript', label: 'Transcript', icon: ScrollText, roles: ['Student'] },
        ],
    },
    {
        label: 'Teaching',
        items: [
            {
                to: '/attendance-session',
                label: 'Attendance Session',
                icon: ScanLine,
                roles: ['Lecturer'],
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            { to: '/students', label: 'Students', icon: Users, roles: ['Coordinator', 'Administrator'] },
            { to: '/lecturers', label: 'Lecturers', icon: GraduationCap, roles: ['Administrator'] },
            { to: '/sections', label: 'Sections', icon: BookOpen, roles: ['Coordinator', 'Administrator'] },
        ],
    },
];

export function visibleGroupsForRole(role) {
    return navigationGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.roles === null || item.roles.includes(role)),
        }))
        .filter((group) => group.items.length > 0);
}
