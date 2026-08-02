import { useState } from 'react';
import { KeyRound, Save } from 'lucide-react';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { FormField } from '../components/FormField';
import { Alert } from '../components/Alert';
import { Badge } from '../components/Badge';
import { useAuth } from '../hooks/useAuth';
import { useToast } from '../hooks/useToast';
import { authService } from '../services/authService';
import { readApiError } from '../services/apiClient';
import { validateMatch, validatePassword, validateRequired } from '../utils/validators';

export function ProfilePage() {
    const { user, setUser } = useAuth();
    const { notify } = useToast();

    const [fullName, setFullName] = useState(user.full_name);
    const [phone, setPhone] = useState(user.phone ?? '');
    const [profileErrors, setProfileErrors] = useState({});
    const [isSavingProfile, setIsSavingProfile] = useState(false);

    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [passwordErrors, setPasswordErrors] = useState({});
    const [passwordNotice, setPasswordNotice] = useState('');
    const [isSavingPassword, setIsSavingPassword] = useState(false);

    async function handleProfileSubmit(event) {
        event.preventDefault();

        const nameError = validateRequired(fullName, 'Full name');

        setProfileErrors({ fullName: nameError });

        if (nameError) {
            return;
        }

        setIsSavingProfile(true);

        try {
            const updated = await authService.updateProfile({
                full_name: fullName.trim(),
                phone: phone.trim(),
            });

            setUser(updated);
            notify('Profile updated.');
        } catch (error) {
            const { message, fieldErrors } = readApiError(error);

            setProfileErrors({
                fullName: fieldErrors.full_name?.[0] ?? '',
                phone: fieldErrors.phone?.[0] ?? '',
            });
            notify(message, 'error');
        } finally {
            setIsSavingProfile(false);
        }
    }

    async function handlePasswordSubmit(event) {
        event.preventDefault();

        const nextErrors = {
            currentPassword: validateRequired(currentPassword, 'Current password'),
            newPassword: validatePassword(newPassword),
            confirmation: validateMatch(newPassword, confirmation),
        };

        setPasswordErrors(nextErrors);
        setPasswordNotice('');

        if (Object.values(nextErrors).some(Boolean)) {
            return;
        }

        setIsSavingPassword(true);

        try {
            await authService.changePassword({
                current_password: currentPassword,
                new_password: newPassword,
                password_confirmation: confirmation,
            });

            setCurrentPassword('');
            setNewPassword('');
            setConfirmation('');
            notify('Password changed. Other sessions were signed out.');
        } catch (error) {
            setPasswordNotice(readApiError(error, 'Unable to change your password.').message);
        } finally {
            setIsSavingPassword(false);
        }
    }

    return (
        <>
            <PageHeader title="My profile" subtitle="Manage your account details and password." />

            <div className="nsu-grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))' }}>
                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h2 className="nsu-section-title">Account details</h2>

                        <form onSubmit={handleProfileSubmit} noValidate>
                            <FormField
                                label="Full name"
                                value={fullName}
                                onChange={(event) => setFullName(event.target.value)}
                                onBlur={() =>
                                    setProfileErrors((c) => ({
                                        ...c,
                                        fullName: validateRequired(fullName, 'Full name'),
                                    }))
                                }
                                error={profileErrors.fullName}
                                autoComplete="name"
                                required
                            />

                            <FormField
                                label="Phone"
                                type="tel"
                                value={phone}
                                onChange={(event) => setPhone(event.target.value)}
                                error={profileErrors.phone}
                                helper="Optional. Used for important account notifications."
                                autoComplete="tel"
                            />

                            <FormField
                                label="University email"
                                value={user.email}
                                onChange={() => {}}
                                helper="Contact the administration to change your email."
                                disabled
                            />

                            <FormField
                                label="University ID"
                                value={user.university_id}
                                onChange={() => {}}
                                disabled
                            />

                            <div style={{ display: 'flex', gap: 'var(--space-sm)', alignItems: 'center' }}>
                                <Button type="submit" icon={Save} isLoading={isSavingProfile}>
                                    {isSavingProfile ? 'Saving' : 'Save changes'}
                                </Button>

                                <Badge variant={user.email_verified ? 'success' : 'warning'}>
                                    {user.email_verified ? 'Email verified' : 'Email not verified'}
                                </Badge>
                            </div>
                        </form>
                    </div>
                </section>

                <section className="nsu-card">
                    <div className="nsu-card__body">
                        <h2 className="nsu-section-title">Change password</h2>

                        <form onSubmit={handlePasswordSubmit} noValidate>
                            {passwordNotice && <Alert variant="error">{passwordNotice}</Alert>}

                            <FormField
                                label="Current password"
                                type="password"
                                value={currentPassword}
                                onChange={(event) => setCurrentPassword(event.target.value)}
                                error={passwordErrors.currentPassword}
                                autoComplete="current-password"
                                required
                            />

                            <FormField
                                label="New password"
                                type="password"
                                value={newPassword}
                                onChange={(event) => setNewPassword(event.target.value)}
                                onBlur={() =>
                                    setPasswordErrors((c) => ({
                                        ...c,
                                        newPassword: validatePassword(newPassword),
                                    }))
                                }
                                error={passwordErrors.newPassword}
                                helper="Minimum 8 characters with upper, lower, number and special character."
                                autoComplete="new-password"
                                required
                            />

                            <FormField
                                label="Confirm new password"
                                type="password"
                                value={confirmation}
                                onChange={(event) => setConfirmation(event.target.value)}
                                onBlur={() =>
                                    setPasswordErrors((c) => ({
                                        ...c,
                                        confirmation: validateMatch(newPassword, confirmation),
                                    }))
                                }
                                error={passwordErrors.confirmation}
                                autoComplete="new-password"
                                required
                            />

                            <Button type="submit" icon={KeyRound} isLoading={isSavingPassword}>
                                {isSavingPassword ? 'Updating' : 'Update password'}
                            </Button>
                        </form>
                    </div>
                </section>
            </div>
        </>
    );
}
