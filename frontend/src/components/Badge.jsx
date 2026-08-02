const statusVariants = {
    Approved: 'success',
    Completed: 'success',
    Pending: 'warning',
    Rejected: 'danger',
    Dropped: 'neutral',
    Withdrawn: 'neutral',
};

export function Badge({ variant = 'neutral', children }) {
    return <span className={`nsu-badge nsu-badge--${variant}`}>{children}</span>;
}

export function StatusBadge({ status }) {
    return <Badge variant={statusVariants[status] ?? 'neutral'}>{status}</Badge>;
}
