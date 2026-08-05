import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ToastProvider } from './contexts/ToastContext';
import { ProtectedRoute } from './routes/ProtectedRoute';
import { AppLayout } from './layouts/AppLayout';
import { LoginPage } from './pages/auth/LoginPage';
import { ForgotPasswordPage } from './pages/auth/ForgotPasswordPage';
import { ResetPasswordPage } from './pages/auth/ResetPasswordPage';
import { DashboardPage } from './pages/student/DashboardPage';
import { CourseCatalogPage } from './pages/student/CourseCatalogPage';
import { RegistrationPage } from './pages/student/RegistrationPage';
import { SchedulePage } from './pages/student/SchedulePage';
import { TranscriptPage } from './pages/student/TranscriptPage';
import { AttendancePage } from './pages/student/AttendancePage';
import { CourseContentPage } from './pages/student/CourseContentPage';
import { FinancePage } from './pages/student/FinancePage';
import { FoodCourtPage } from './pages/student/FoodCourtPage';
import { ActivitiesPage } from './pages/student/ActivitiesPage';
import { EventManagementPage } from './pages/stad/EventManagementPage';
import { ExaminationsPage } from './pages/student/ExaminationsPage';
import { MyResultsPage } from './pages/student/MyResultsPage';
import { AssessmentsPage } from './pages/lecturer/AssessmentsPage';
import { GradeApprovalsPage } from './pages/coordinator/GradeApprovalsPage';
import { ExamSessionPage } from './pages/student/ExamSessionPage';
import { AttendanceSessionPage } from './pages/lecturer/AttendanceSessionPage';
import { ExamMonitorPage } from './pages/lecturer/ExamMonitorPage';
import { TeachingPage } from './pages/lecturer/TeachingPage';
import { StudentsPage } from './pages/admin/StudentsPage';
import { LecturersPage } from './pages/admin/LecturersPage';
import { RolesPage } from './pages/admin/RolesPage';
import { SectionsPage } from './pages/admin/SectionsPage';
import { CalendarPage } from './pages/CalendarPage';
import { ChatPage } from './pages/ChatPage';
import { ReportsPage } from './pages/ReportsPage';
import { DownloadCenterPage } from './pages/DownloadCenterPage';
import { ExamResetPage } from './pages/ExamResetPage';
import { NotificationsPage } from './pages/NotificationsPage';
import { ProfilePage } from './pages/ProfilePage';
import { NotFoundPage } from './pages/NotFoundPage';
import './styles/components.css';
import './styles/auth.css';
import './styles/shell.css';
import './styles/admin.css';
import './styles/activities.css';
import './styles/notifications.css';
import './styles/exam.css';

export default function App() {
    return (
        <BrowserRouter>
            <AuthProvider>
                <ToastProvider>
                    <Routes>
                        <Route path="/login" element={<LoginPage />} />
                        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
                        <Route path="/reset-password" element={<ResetPasswordPage />} />

                        <Route element={<ProtectedRoute />}>
                            <Route element={<AppLayout />}>
                                <Route path="/dashboard" element={<DashboardPage />} />
                                <Route path="/courses" element={<CourseCatalogPage />} />
                                <Route path="/calendar" element={<CalendarPage />} />
                                <Route path="/chat" element={<ChatPage />} />
                                <Route path="/food-court" element={<FoodCourtPage />} />
                                <Route path="/reports" element={<ReportsPage />} />
                                <Route path="/downloads" element={<DownloadCenterPage />} />
                                <Route path="/notifications" element={<NotificationsPage />} />
                                <Route path="/profile" element={<ProfilePage />} />

                                <Route element={<ProtectedRoute allowedRoles={['Student']} />}>
                                    <Route path="/registration" element={<RegistrationPage />} />
                                    <Route path="/schedule" element={<SchedulePage />} />
                                    <Route path="/transcript" element={<TranscriptPage />} />
                                    <Route path="/attendance" element={<AttendancePage />} />
                                    <Route path="/course-content" element={<CourseContentPage />} />
                                    <Route path="/finance" element={<FinancePage />} />
                                    <Route path="/examinations" element={<ExaminationsPage />} />
                                    <Route path="/activities" element={<ActivitiesPage />} />
                                    <Route path="/my-results" element={<MyResultsPage />} />
                                    <Route
                                        path="/examinations/:id/sit"
                                        element={<ExamSessionPage />}
                                    />
                                </Route>

                                <Route
                                    element={
                                        <ProtectedRoute allowedRoles={['Coordinator', 'Administrator']} />
                                    }
                                >
                                    <Route path="/students" element={<StudentsPage />} />
                                    <Route path="/sections" element={<SectionsPage />} />
                                </Route>

                                <Route element={<ProtectedRoute allowedRoles={['Administrator']} />}>
                                    <Route path="/lecturers" element={<LecturersPage />} />
                                    <Route path="/roles" element={<RolesPage />} />
                                </Route>

                                <Route
                                    element={<ProtectedRoute allowedRoles={['Lecturer', 'Coordinator']} />}
                                >
                                    <Route
                                        path="/grade-approvals"
                                        element={<GradeApprovalsPage />}
                                    />
                                </Route>

                                <Route path="/exam-resets" element={<ExamResetPage />} />

                                <Route element={<ProtectedRoute allowedRoles={['STAD Staff']} />}>
                                    <Route
                                        path="/event-management"
                                        element={<EventManagementPage />}
                                    />
                                </Route>

                                <Route element={<ProtectedRoute allowedRoles={['Lecturer']} />}>
                                    <Route
                                        path="/attendance-session"
                                        element={<AttendanceSessionPage />}
                                    />
                                    <Route path="/teaching" element={<TeachingPage />} />
                                    <Route path="/assessments" element={<AssessmentsPage />} />
                                </Route>

                                <Route
                                    element={<ProtectedRoute allowedRoles={['Lecturer', 'Coordinator']} />}
                                >
                                    <Route path="/exam-monitor" element={<ExamMonitorPage />} />
                                </Route>
                            </Route>
                        </Route>

                        <Route path="/" element={<Navigate to="/dashboard" replace />} />
                        <Route path="*" element={<NotFoundPage />} />
                    </Routes>
                </ToastProvider>
            </AuthProvider>
        </BrowserRouter>
    );
}
