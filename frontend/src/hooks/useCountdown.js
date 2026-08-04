import { useEffect, useState } from 'react';

export function useCountdown(seconds, onElapsed) {
    const [remaining, setRemaining] = useState(seconds ?? 0);

    useEffect(() => {
        setRemaining(seconds ?? 0);
    }, [seconds]);

    useEffect(() => {
        if (remaining <= 0) {
            return undefined;
        }

        const timer = setInterval(() => {
            setRemaining((current) => {
                if (current <= 1) {
                    clearInterval(timer);
                    onElapsed?.();

                    return 0;
                }

                return current - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [remaining > 0, onElapsed]);

    return remaining;
}

export function formatCountdown(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [minutes, seconds].map((part) => String(part).padStart(2, '0'));

    return hours > 0 ? [String(hours).padStart(2, '0'), ...parts].join(':') : parts.join(':');
}
