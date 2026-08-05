import { useEffect } from 'react';

export function usePortalBody() {
    useEffect(() => {
        document.body.classList.add('nsu-portal-open');

        return () => document.body.classList.remove('nsu-portal-open');
    }, []);
}
