import {
    BookOpen,
    CalendarCheck,
    Wallet,
    CalendarDays,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    Library,
    MessagesSquare,
    NotebookPen,
    ScanLine,
    ScrollText,
    Users,
} from 'lucide-react';

export const navigationGroups = [
    {
        label: 'Overview',
        items: [
            { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: null },
            { to: '/calendar', label: 'Calendar', icon: CalendarDays, roles: null },
            { to: '/chat', label: 'Chat', icon: MessagesSquare, roles: null },
        ],
    },
    {
        label: 'Academic',
        items: [
            { to: '/courses', label: 'Course Catalog', icon: Library, roles: null },
            { to: '/registration', label: 'Course Registration', icon: ClipboardList, roles: ['Student'] },
            { to: '/schedule', label: 'My Schedule', icon: CalendarDays, roles: ['Student'] },
            { to: '/course-content', label: 'Course Content', icon: BookOpen, roles: ['Student'] },
            { to: '/attendance', label: 'My Attendance', icon: CalendarCheck, roles: ['Student'] },
            { to: '/transcript', label: 'Transcript', icon: ScrollText, roles: ['Student'] },
            { to: '/finance', label: 'Finance', icon: Wallet, roles: ['Student'] },
        ],
    },
    {
        label: 'Teaching',
        items: [
            { to: '/teaching', label: 'Course Content', icon: NotebookPen, roles: ['Lecturer'] },
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
