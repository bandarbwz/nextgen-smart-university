import { createContext, useCallback, useMemo, useState } from 'react';
import { ToastStack } from '../components/ToastStack';

export const ToastContext = createContext(null);

let nextId = 0;

export function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);

    const dismiss = useCallback((id) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    }, []);

    const notify = useCallback(
        (message, variant = 'success') => {
            const id = nextId;

            nextId += 1;

            setToasts((current) => [...current, { id, message, variant }]);

            setTimeout(() => dismiss(id), 4000);
        },
        [dismiss],
    );

    const value = useMemo(() => ({ notify }), [notify]);

    return (
        <ToastContext.Provider value={value}>
            {children}
            <ToastStack toasts={toasts} onDismiss={dismiss} />
        </ToastContext.Provider>
    );
}
