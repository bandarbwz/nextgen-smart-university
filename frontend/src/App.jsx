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
import { AttendanceSessionPage } from './pages/lecturer/AttendanceSessionPage';
import { ProfilePage } from './pages/ProfilePage';
import { NotFoundPage } from './pages/NotFoundPage';
import './styles/components.css';
import './styles/auth.css';
import './styles/shell.css';

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
                                <Route path="/profile" element={<ProfilePage />} />

                                <Route element={<ProtectedRoute allowedRoles={['Student']} />}>
                                    <Route path="/registration" element={<RegistrationPage />} />
                                    <Route path="/schedule" element={<SchedulePage />} />
                                    <Route path="/transcript" element={<TranscriptPage />} />
                                    <Route path="/attendance" element={<AttendancePage />} />
                                </Route>

                                <Route element={<ProtectedRoute allowedRoles={['Lecturer']} />}>
                                    <Route
                                        path="/attendance-session"
                                        element={<AttendanceSessionPage />}
                                    />
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
