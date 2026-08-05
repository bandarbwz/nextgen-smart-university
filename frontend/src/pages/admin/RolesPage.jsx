import { useCallback, useEffect, useState } from 'react';
import { KeyRound, Plus, Save, ShieldCheck, Trash2 } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { roleService } from '../../services/roleService';
import { readApiError } from '../../services/apiClient';
import { useToast } from '../../hooks/useToast';

function moment(value) {
    return new Date(`${value}Z`).toLocaleString(undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export function RolesPage() {
    const { notify } = useToast();

    const [roles, setRoles] = useState([]);
    const [catalogue, setCatalogue] = useState({});
    const [selectedId, setSelectedId] = useState(null);
    const [detail, setDetail] = useState(null);
    const [checked, setChecked] = useState([]);
    const [log, setLog] = useState([]);
    const [newName, setNewName] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    const loadRoles = useCallback(
        () =>
            roleService.list().then((data) => {
                setRoles(data);
                setSelectedId((current) =>
                    current !== null && data.some((role) => role.id === current)
                        ? current
                        : (data[0]?.id ?? null),
                );
            }),
        [],
    );

    useEffect(() => {
        Promise.all([loadRoles(), roleService.permissions().then(setCatalogue)])
            .catch((error) => setNotice(readApiError(error, 'Unable to load roles.').message))
            .finally(() => setIsLoading(false));
    }, [loadRoles]);

    useEffect(() => {
        if (selectedId === null) {
            setDetail(null);

            return;
        }

        Promise.all([roleService.get(selectedId), roleService.auditLog(selectedId)])
            .then(([role, entries]) => {
                setDetail(role);
                setChecked(role.permission_ids.map(Number));
                setLog(entries);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load the role.').message));
    }, [selectedId]);

    const toggle = (permissionId) =>
        setChecked((current) =>
            current.includes(permissionId)
                ? current.filter((id) => id !== permissionId)
                : [...current, permissionId],
        );

    const savePermissions = async () => {
        try {
            const updated = await roleService.assignPermissions(selectedId, checked);

            setDetail(updated);
            await loadRoles();
            setLog(await roleService.auditLog(selectedId));
            notify('Permissions saved.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to save permissions.').message, 'error');
        }
    };

    const createRole = async () => {
        if (newName.trim() === '') {
            notify('Give the role a name.', 'error');

            return;
        }

        try {
            const role = await roleService.create({ name: newName.trim() });

            setNewName('');
            await loadRoles();
            setSelectedId(role.id);
            notify('Role created.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to create the role.').message, 'error');
        }
    };

    const removeRole = async () => {
        try {
            await roleService.remove(selectedId);

            setSelectedId(null);
            await loadRoles();
            notify('Role deleted.', 'success');
        } catch (error) {
            notify(readApiError(error, 'Unable to delete the role.').message, 'error');
        }
    };

    if (isLoading) {
        return (
            <>
                <PageHeader title="Roles and Permissions" subtitle="Who can do what." />
                <SkeletonRows rows={4} height={70} />
            </>
        );
    }

    return (
        <>
            <PageHeader title="Roles and Permissions" subtitle="Who can do what." />

            {notice && <Alert variant="error">{notice}</Alert>}

            <section className="nsu-card">
                <h2 className="nsu-section-title">Roles</h2>

                <div className="nsu-table-wrap">
                    <table className="nsu-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th className="nsu-table__number">Permissions</th>
                                <th className="nsu-table__number">Users</th>
                                <th>Status</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            {roles.map((role) => (
                                <tr
                                    key={role.id}
                                    className={role.id === selectedId ? 'nsu-row--selected' : ''}
                                    onClick={() => setSelectedId(role.id)}
                                    style={{ cursor: 'pointer' }}
                                >
                                    <td>
                                        {role.name}
                                        {role.description && (
                                            <span className="nsu-table__hint">{role.description}</span>
                                        )}
                                    </td>
                                    <td className="nsu-table__number tabular">
                                        {role.permission_count}
                                    </td>
                                    <td className="nsu-table__number tabular">{role.user_count}</td>
                                    <td>
                                        <Badge
                                            variant={role.status === 'active' ? 'success' : 'neutral'}
                                        >
                                            {role.status}
                                        </Badge>
                                    </td>
                                    <td>
                                        {Number(role.is_system) === 1 ? (
                                            <Badge variant="neutral">System</Badge>
                                        ) : (
                                            <Badge variant="warning">Custom</Badge>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="nsu-toolbar" style={{ marginTop: 'var(--space-md)' }}>
                    <label className="nsu-search">
                        <Plus size={16} aria-hidden="true" />
                        <input
                            type="text"
                            className="nsu-search__input"
                            placeholder="New role name"
                            value={newName}
                            onChange={(event) => setNewName(event.target.value)}
                            aria-label="New role name"
                        />
                    </label>

                    <Button variant="secondary" onClick={createRole}>
                        Create role
                    </Button>
                </div>
            </section>

            {detail && (
                <>
                    <div className="nsu-card nsu-assessment-summary">
                        <div>
                            <h2 className="nsu-section-title">
                                <KeyRound size={16} aria-hidden="true" /> {detail.name}
                            </h2>
                            <p className="nsu-assessment-summary__meta">
                                {checked.length} permission(s) selected · {detail.user_count} user(s)
                                hold this role
                            </p>
                        </div>

                        <div className="nsu-table__actions">
                            <Button variant="primary" icon={Save} onClick={savePermissions}>
                                Save permissions
                            </Button>

                            {Number(detail.is_system) === 0 && (
                                <Button variant="danger" icon={Trash2} onClick={removeRole}>
                                    Delete role
                                </Button>
                            )}
                        </div>
                    </div>

                    {Number(detail.is_system) === 1 && (
                        <Alert variant="info">
                            This is a system default role. Its permissions can be changed and it can
                            be deactivated, but it cannot be renamed or deleted because other parts
                            of the platform refer to it by name.
                        </Alert>
                    )}

                    <section className="nsu-card">
                        <h2 className="nsu-section-title">Permissions</h2>

                        {Object.entries(catalogue).map(([module, permissions]) => (
                            <div className="nsu-permission-group" key={module}>
                                <p className="nsu-permission-group__title">{module}</p>

                                <div className="nsu-permission-grid">
                                    {permissions.map((permission) => (
                                        <label className="nsu-permission" key={permission.id}>
                                            <input
                                                type="checkbox"
                                                checked={checked.includes(Number(permission.id))}
                                                onChange={() => toggle(Number(permission.id))}
                                            />
                                            <span>
                                                <strong>{permission.name}</strong>
                                                {permission.description && (
                                                    <span className="nsu-table__hint">
                                                        {permission.description}
                                                    </span>
                                                )}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </section>

                    <section className="nsu-card">
                        <h2 className="nsu-section-title">
                            <ShieldCheck size={16} aria-hidden="true" /> Authorization log
                        </h2>

                        {log.length === 0 ? (
                            <EmptyState
                                title="No changes recorded"
                                description="Every role and permission change appears here."
                            />
                        ) : (
                            <ul className="nsu-roster">
                                {log.map((entry) => (
                                    <li className="nsu-roster__row" key={entry.id}>
                                        <span>
                                            <strong>{entry.action}</strong>
                                            <span className="nsu-table__hint">
                                                {entry.detail} · by {entry.performed_by_name}
                                            </span>
                                        </span>
                                        <span className="nsu-table__hint">
                                            {moment(entry.created_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </>
            )}
        </>
    );
}
