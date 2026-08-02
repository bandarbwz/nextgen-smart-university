import { Link } from 'react-router-dom';
import { Compass } from 'lucide-react';
import { EmptyState } from '../components/EmptyState';

export function NotFoundPage() {
    return (
        <div style={{ display: 'grid', placeItems: 'center', minHeight: '100dvh' }}>
            <EmptyState
                icon={Compass}
                title="Page not found"
                description="The page you are looking for does not exist or has been moved."
                action={<Link to="/dashboard">Return to dashboard</Link>}
            />
        </div>
    );
}
