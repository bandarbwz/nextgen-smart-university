export function Skeleton({ height = 16, width = '100%', className = '' }) {
    return (
        <div
            className={`nsu-skeleton ${className}`}
            style={{ height, width }}
            aria-hidden="true"
        />
    );
}

export function SkeletonRows({ rows = 4, height = 48 }) {
    return (
        <div
            style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-sm)' }}
            role="status"
            aria-label="Loading content"
        >
            {Array.from({ length: rows }, (_, index) => (
                <Skeleton key={index} height={height} />
            ))}
        </div>
    );
}
