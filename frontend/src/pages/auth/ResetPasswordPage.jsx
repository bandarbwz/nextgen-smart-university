import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { KeyRound } from 'lucide-react';
import { AuthLayout } from '../../layouts/AuthLayout';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { FormField } from '../../components/FormField';
import { authService } from '../../services/authService';
import { readApiError } from '../../services/apiClient';
import { validateMatch, validatePassword } from '../../utils/validators';

export function ResetPasswordPage() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const token = searchParams.get('token') ?? '';

    const [password, setPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [errors, setErrors] = useState({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event) {
        event.preventDefault();

        const nextErrors = {
            password: validatePassword(password),
            confirmation: validateMatch(password, confirmation),
        };

        setErrors(nextErrors);
        setFormError('');

        if (nextErrors.password || nextErrors.confirmation) {
            return;
        }

        setIsSubmitting(true);

        try {
            await authService.resetPassword({
                token,
                password,
                password_confirmation: confirmation,
            });

            navigate('/login', { replace: true });
        } catch (error) {
            setFormError(readApiError(error).message);
        } finally {
            setIsSubmitting(false);
        }
    }

    if (!token) {
        return (
            <AuthLayout title="Reset your password" subtitle="This reset link is not valid.">
                <Alert variant="error">
                    The reset link is missing its token. Request a new link to continue.
                </Alert>
                <p className="nsu-auth__switch">
                    <Link to="/forgot-password">Request a new link</Link>
                </p>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout title="Choose a new password" subtitle="Your new password must be strong.">
            <form onSubmit={handleSubmit} noValidate>
                {formError && <Alert variant="error">{formError}</Alert>}

                <FormField
                    label="New password"
                    type="password"
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    onBlur={() => setErrors((c) => ({ ...c, password: validatePassword(password) }))}
                    error={errors.password}
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
                        setErrors((c) => ({ ...c, confirmation: validateMatch(password, confirmation) }))
                    }
                    error={errors.confirmation}
                    autoComplete="new-password"
                    required
                />

                <Button type="submit" icon={KeyRound} isLoading={isSubmitting} block>
                    {isSubmitting ? 'Updating' : 'Update password'}
                </Button>

                <p className="nsu-auth__switch">
                    <Link to="/login">Back to sign in</Link>
                </p>
            </form>
        </AuthLayout>
    );
}
