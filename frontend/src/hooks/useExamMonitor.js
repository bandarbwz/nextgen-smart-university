import { useEffect, useRef } from 'react';
import { examService } from '../services/examService';

/**
 * Reports what the browser can see on its own: tab switches, fullscreen exits,
 * window focus and clipboard use. Camera work needs the AI service and is
 * handled separately.
 */
export function useExamMonitor(sessionId, { enabled, onSessionUpdate }) {
    const queue = useRef(Promise.resolve());

    useEffect(() => {
        if (!enabled || !sessionId) {
            return undefined;
        }

        const report = (activityType, detail = null) => {
            queue.current = queue.current
                .then(() => examService.reportBrowserActivity(sessionId, activityType, detail))
                .then((session) => onSessionUpdate?.(session))
                .catch(() => undefined);
        };

        const onVisibility = () => report(document.hidden ? 'tab_hidden' : 'tab_visible');
        const onFullscreen = () =>
            report(document.fullscreenElement ? 'fullscreen_enter' : 'fullscreen_exit');
        const onBlur = () => report('window_blur');
        const onFocus = () => report('window_focus');
        const onCopy = () => report('copy');
        const onPaste = () => report('paste');

        document.addEventListener('visibilitychange', onVisibility);
        document.addEventListener('fullscreenchange', onFullscreen);
        document.addEventListener('copy', onCopy);
        document.addEventListener('paste', onPaste);
        window.addEventListener('blur', onBlur);
        window.addEventListener('focus', onFocus);

        return () => {
            document.removeEventListener('visibilitychange', onVisibility);
            document.removeEventListener('fullscreenchange', onFullscreen);
            document.removeEventListener('copy', onCopy);
            document.removeEventListener('paste', onPaste);
            window.removeEventListener('blur', onBlur);
            window.removeEventListener('focus', onFocus);
        };
    }, [sessionId, enabled, onSessionUpdate]);
}
