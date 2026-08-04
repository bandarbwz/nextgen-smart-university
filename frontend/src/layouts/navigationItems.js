import {
    BarChart3,
    BookOpen,
    CalendarCheck,
    FolderOpen,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    Library,
    Bell,
    MessagesSquare,
    NotebookPen,
    PartyPopper,
    ScanLine,
    ScrollText,
    ShieldCheck,
    Users,
    Utensils,
    Wallet,
} from 'lucide-react';

export const navigationGroups = [
    {
        label: 'Overview',
        items: [
            { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: null },
            { to: '/calendar', label: 'Calendar', icon: CalendarDays, roles: null },
            { to: '/notifications', label: 'Notifications', icon: Bell, roles: null },
            { to: '/chat', label: 'Chat', icon: MessagesSquare, roles: null },
            { to: '/food-court', label: 'Food Court', icon: Utensils, roles: null },
            { to: '/downloads', label: 'Download Center', icon: FolderOpen, roles: null },
            { to: '/reports', label: 'Reports', icon: BarChart3, roles: null },
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
            { to: '/examinations', label: 'Examinations', icon: ShieldCheck, roles: ['Student'] },
            { to: '/activities', label: 'Student Activities', icon: PartyPopper, roles: ['Student'] },
            { to: '/my-results', label: 'My Results', icon: ClipboardCheck, roles: ['Student'] },
            { to: '/transcript', label: 'Transcript', icon: ScrollText, roles: ['Student'] },
            { to: '/finance', label: 'Finance', icon: Wallet, roles: ['Student'] },
        ],
    },
    {
        label: 'Teaching',
        items: [
            { to: '/teaching', label: 'Course Content', icon: NotebookPen, roles: ['Lecturer'] },
            { to: '/assessments', label: 'Assessments', icon: ClipboardCheck, roles: ['Lecturer'] },
            {
                to: '/grade-approvals',
                label: 'Grade Approvals',
                icon: ShieldCheck,
                roles: ['Lecturer', 'Coordinator'],
            },
            {
                to: '/attendance-session',
                label: 'Attendance Session',
                icon: ScanLine,
                roles: ['Lecturer'],
            },
            {
                to: '/exam-monitor',
                label: 'Examination Monitor',
                icon: ShieldCheck,
                roles: ['Lecturer', 'Coordinator'],
            },
        ],
    },
    {
        label: 'Student Affairs',
        items: [
            {
                to: '/event-management',
                label: 'Event Management',
                icon: PartyPopper,
                roles: ['STAD Staff'],
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
