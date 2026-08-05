import { useEffect, useState } from 'react';
import { isSoundOn, subscribeToSound, toggleSound, unlockAudio } from '../services/portalSound';

export function usePortalSound() {
    const [soundOn, setSoundOn] = useState(isSoundOn);

    useEffect(() => subscribeToSound(setSoundOn), []);

    useEffect(() => {
        const unlock = () => unlockAudio();

        document.addEventListener('pointerdown', unlock, { once: true });

        return () => document.removeEventListener('pointerdown', unlock);
    }, []);

    return { soundOn, toggleSound };
}
