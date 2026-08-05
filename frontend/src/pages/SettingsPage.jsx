import { useEffect, useState } from 'react';
import { Save, SlidersHorizontal } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { SkeletonRows } from '../components/Skeleton';
import { Alert } from '../components/Alert';
import { Button } from '../components/Button';
import { systemService } from '../services/systemService';
import { readApiError } from '../services/apiClient';
import { useToast } from '../hooks/useToast';

const LANGUAGES = [
    ['en', 'English'],
    ['ar', 'العربية'],
];

const THEMES = [
    ['system', 'Match my device'],
    ['light', 'Light'],
    ['dark', 'Dark'],
];

const TIMEZONES = ['UTC', 'Asia/Riyadh', 'Asia/Dubai', 'Europe/London', 'America/New_York'];

export function SettingsPage() {
    const { notify } = useToast();

    const [settings, setSettings] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        systemService
            .mySettings()
            .then(setSettings)
            .catch((error) => setNotice(readApiError(error, 'Unable to load your settings.').message))
            .finally(() => setIsLoading(false));
    }, []);

    const save = async () => {
        try {
            setSettings(await systemService.updateMySettings(settings));
            notify('Settings saved.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to save your settings.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Settings" subtitle="Your language, theme and time zone." />
                <SkeletonRows rows={3} height={70} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="Settings"
                subtitle="Your language, theme and time zone."
                actions={
                    <Button variant="primary" icon={Save} onClick={save}>
                        Save
                    </Button>
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            <section className="nsu-card">
                <h2 className="nsu-section-title">
                    <SlidersHorizontal size={16} aria-hidden="true" /> Preferences
                </h2>

                <label className="nsu-field">
                    <span className="nsu-field__label">Language</span>
                    <select
                        className="nsu-field__input"
                        value={settings.language}
                        onChange={(event) =>
                            setSettings({ ...settings, language: event.target.value })
                        }
                    >
                        {LANGUAGES.map(([value, label]) => (
                            <option value={value} key={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="nsu-field">
                    <span className="nsu-field__label">
                        Theme
                        <span className="nsu-field__helper">
                            The toggle in the header changes the theme immediately. This is the
                            preference stored on your account.
                        </span>
                    </span>
                    <select
                        className="nsu-field__input"
                        value={settings.theme}
                        onChange={(event) => setSettings({ ...settings, theme: event.target.value })}
                    >
                        {THEMES.map(([value, label]) => (
                            <option value={value} key={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="nsu-field">
                    <span className="nsu-field__label">Time zone</span>
                    <select
                        className="nsu-field__input"
                        value={settings.timezone}
                        onChange={(event) =>
                            setSettings({ ...settings, timezone: event.target.value })
                        }
                    >
                        {TIMEZONES.map((zone) => (
                            <option value={zone} key={zone}>
                                {zone}
                            </option>
                        ))}
                    </select>
                </label>

                <Alert variant="info">
                    Arabic is offered as a language preference and is stored on your account, but the
                    interface itself is not translated yet.
                </Alert>
            </section>
        </>
    );
}
