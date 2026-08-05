import { useCallback, useEffect, useState } from 'react';
import { Activity, PauseOctagon, PlayCircle, Save, ScrollText } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { systemService } from '../../services/systemService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

const statusVariants = {
    up: 'success',
    configured: 'success',
    degraded: 'warning',
    'not configured': 'warning',
    down: 'danger',
};

const severityVariants = {
    info: 'neutral',
    warning: 'warning',
    error: 'danger',
    critical: 'danger',
};

function moment(value) {
    return new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'short',
        timeStyle: 'medium',
    });
}

export function SystemPage() {
    const { notify } = useToast();

    const [health, setHealth] = useState(null);
    const [settings, setSettings] = useState({});
    const [drafts, setDrafts] = useState({});
    const [logs, setLogs] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const load = useCallback(
        () =>
            Promise.all([
                systemService.health(),
                systemService.systemSettings(),
                systemService.logs(),
            ]).then(([healthData, settingsData, logData]) => {
                setHealth(healthData);
                setSettings(settingsData);
                setLogs(logData.log);
                setDrafts({});
            }),
        [],
    );

    useEffect(() => {
        load()
            .catch((error) => setNotice(readApiError(error, 'Unable to load the system view.').message))
            .finally(() => setIsLoading(false));
    }, [load]);

    const saveSettings = async () => {
        if (Object.keys(drafts).length === 0) {
            notify('Nothing has changed.', 'error');

            return;
        }

        try {
            await systemService.updateSystemSettings(drafts);
            await load();
            notify('System settings saved.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to save the settings.').message, 'error');
        }
    };

    const toggleMaintenance = async () => {
        const next = !health.maintenance_mode;

        try {
            await systemService.setMaintenance(next, null);
            await load();
            notify(next ? 'Maintenance mode is on.' : 'Maintenance mode is off.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to change maintenance mode.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="System" subtitle="Health, configuration and logs." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    return (
        <>
            <PageHeader
                title="System"
                subtitle="Health, configuration and logs."
                actions={
                    <Button
                        variant={health.maintenance_mode ? 'primary' : 'danger'}
                        icon={health.maintenance_mode ? PlayCircle : PauseOctagon}
                        onClick={toggleMaintenance}
                    >
                        {health.maintenance_mode ? 'End maintenance' : 'Start maintenance'}
                    </Button>
                }
            />

            {notice && <Alert variant="error">{notice}</Alert>}

            {health.maintenance_mode && (
                <Alert variant="error">
                    Maintenance mode is on. Everyone except administrators is locked out of the
                    platform right now.
                </Alert>
            )}

            <section className="nsu-card">
                <h2 className="nsu-section-title">
                    <Activity size={16} aria-hidden="true" /> Health
                </h2>

                <p className="nsu-assessment-summary__meta">Checked {moment(health.checked_at)}</p>

                <ul className="nsu-roster">
                    {health.checks.map((check) => (
                        <li className="nsu-roster__row" key={check.name}>
                            <span>
                                <strong>{check.name}</strong>
                                <span className="nsu-table__hint">
                                    {Object.entries(check.detail)
                                        .map(([key, value]) => `${key.replace(/_/g, ' ')}: ${value}`)
                                        .join(' · ')}
                                </span>
                            </span>
                            <Badge variant={statusVariants[check.status] ?? 'neutral'}>
                                {check.status}
                            </Badge>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="nsu-card">
                <div className="nsu-assessment-summary">
                    <h2 className="nsu-section-title">Configuration</h2>
                    <Button variant="secondary" icon={Save} onClick={saveSettings}>
                        Save changes
                    </Button>
                </div>

                {Object.entries(settings).map(([category, items]) => (
                    <div className="nsu-permission-group" key={category}>
                        <p className="nsu-permission-group__title">{category}</p>

                        {items.map((setting) => (
                            <label className="nsu-field" key={setting.setting_key}>
                                <span className="nsu-field__label">
                                    {setting.setting_key.replace(/_/g, ' ')}
                                    {setting.description && (
                                        <span className="nsu-field__helper">
                                            {setting.description}
                                        </span>
                                    )}
                                </span>

                                {setting.value_type === 'boolean' ? (
                                    <select
                                        className="nsu-field__input"
                                        value={drafts[setting.setting_key] ?? setting.setting_value}
                                        onChange={(event) =>
                                            setDrafts({
                                                ...drafts,
                                                [setting.setting_key]: event.target.value,
                                            })
                                        }
                                    >
                                        <option value="true">true</option>
                                        <option value="false">false</option>
                                    </select>
                                ) : (
                                    <input
                                        type={setting.value_type === 'integer' ? 'number' : 'text'}
                                        className="nsu-field__input"
                                        defaultValue={setting.setting_value}
                                        onChange={(event) =>
                                            setDrafts({
                                                ...drafts,
                                                [setting.setting_key]: event.target.value,
                                            })
                                        }
                                    />
                                )}
                            </label>
                        ))}
                    </div>
                ))}
            </section>

            <section className="nsu-card">
                <h2 className="nsu-section-title">
                    <ScrollText size={16} aria-hidden="true" /> System log
                </h2>

                {logs.length === 0 ? (
                    <EmptyState
                        title="Nothing logged yet"
                        description="Configuration changes and maintenance events appear here."
                    />
                ) : (
                    <ul className="nsu-roster">
                        {logs.map((entry) => (
                            <li className="nsu-roster__row" key={entry.id}>
                                <span>
                                    <strong>{entry.action}</strong>
                                    <span className="nsu-table__hint">
                                        {entry.module} · {entry.message}
                                        {entry.created_by_name ? ` · ${entry.created_by_name}` : ''}
                                    </span>
                                </span>
                                <span className="nsu-table__actions">
                                    <Badge variant={severityVariants[entry.severity]}>
                                        {entry.severity}
                                    </Badge>
                                    <span className="nsu-table__hint">{moment(entry.created_at)}</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}
