import {
    BookOpen,
    CalendarDays,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    Library,
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
            { to: '/transcript', label: 'Transcript', icon: ScrollText, roles: ['Student'] },
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
