import { Inbox } from 'lucide-react';

export function EmptyState({ icon: Icon = Inbox, title, description, action = null }) {
    return (
        <div className="nsu-empty">
            <span className="nsu-empty__icon">
                <Icon size={24} aria-hidden="true" />
            </span>
            <h3>{title}</h3>
            {description && <p style={{ margin: 0, maxWidth: '46ch' }}>{description}</p>}
            {action}
        </div>
    );
}
