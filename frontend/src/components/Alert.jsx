import { AlertCircle, CheckCircle2, Info } from 'lucide-react';

const icons = {
    error: AlertCircle,
    success: CheckCircle2,
    info: Info,
};

export function Alert({ variant = 'info', children }) {
    const Icon = icons[variant] ?? Info;

    return (
        <div className={`nsu-alert nsu-alert--${variant}`} role={variant === 'error' ? 'alert' : 'status'}>
            <Icon size={18} aria-hidden="true" />
            <span>{children}</span>
        </div>
    );
}
