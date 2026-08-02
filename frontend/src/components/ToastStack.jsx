import { AlertCircle, CheckCircle2, X } from 'lucide-react';

export function ToastStack({ toasts, onDismiss }) {
    return (
        <div className="nsu-toast-stack" aria-live="polite" aria-atomic="false">
            {toasts.map((toast) => {
                const Icon = toast.variant === 'error' ? AlertCircle : CheckCircle2;

                return (
                    <div key={toast.id} className={`nsu-toast nsu-toast--${toast.variant}`}>
                        <Icon
                            size={18}
                            aria-hidden="true"
                            color={
                                toast.variant === 'error'
                                    ? 'var(--color-destructive)'
                                    : 'var(--color-success)'
                            }
                        />
                        <span className="nsu-toast__message">{toast.message}</span>
                        <button
                            type="button"
                            className="nsu-toast__close"
                            onClick={() => onDismiss(toast.id)}
                            aria-label="Dismiss notification"
                        >
                            <X size={16} />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
