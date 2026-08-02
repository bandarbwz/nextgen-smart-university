import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export function ProtectedRoute({ allowedRoles = null }) {
    const { isAuthenticated, isLoading, user } = useAuth();
    const location = useLocation();

    if (isLoading) {
        return (
            <div
                style={{ display: 'grid', placeItems: 'center', minHeight: '100dvh' }}
                role="status"
                aria-label="Checking your session"
            >
                <span className="nsu-spinner" style={{ width: 28, height: 28 }} />
            </div>
        );
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    if (allowedRoles && !allowedRoles.includes(user.role) && user.role !== 'Administrator') {
        return <Navigate to="/dashboard" replace />;
    }

    return <Outlet />;
}
