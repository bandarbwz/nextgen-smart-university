import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { LogIn } from 'lucide-react';
import { AuthLayout } from '../../layouts/AuthLayout';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { FormField } from '../../components/FormField';
import { useAuth } from '../../hooks/useAuth';
import { readApiError } from '../../services/apiClient';
import { validateEmail, validateRequired } from '../../utils/validators';

export function LoginPage() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [errors, setErrors] = useState({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const redirectTo = location.state?.from ?? '/dashboard';

    async function handleSubmit(event) {
        event.preventDefault();

        const nextErrors = {
            email: validateEmail(email),
            password: validateRequired(password, 'Password'),
        };

        setErrors(nextErrors);
        setFormError('');

        if (nextErrors.email || nextErrors.password) {
            return;
        }

        setIsSubmitting(true);

        try {
            await login(email.trim(), password);

            navigate(redirectTo, { replace: true });
        } catch (error) {
            const { message, fieldErrors } = readApiError(error, 'Unable to sign in right now.');

            setFormError(message);
            setErrors({
                email: fieldErrors.email?.[0] ?? '',
                password: fieldErrors.password?.[0] ?? '',
            });
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <AuthLayout
            title="Sign in"
            subtitle="Use your university email address to access the platform."
        >
            <form onSubmit={handleSubmit} noValidate>
                {formError && <Alert variant="error">{formError}</Alert>}

                <FormField
                    label="University email"
                    type="email"
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    onBlur={() => setErrors((c) => ({ ...c, email: validateEmail(email) }))}
                    error={errors.email}
                    placeholder="student@nextgen.edu"
                    autoComplete="username"
                    required
                />

                <FormField
                    label="Password"
                    type="password"
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    onBlur={() =>
                        setErrors((c) => ({ ...c, password: validateRequired(password, 'Password') }))
                    }
                    error={errors.password}
                    autoComplete="current-password"
                    required
                />

                <div className="nsu-auth__meta">
                    <Link to="/forgot-password">Forgot password?</Link>
                </div>

                <Button type="submit" icon={LogIn} isLoading={isSubmitting} block>
                    {isSubmitting ? 'Signing in' : 'Sign in'}
                </Button>
            </form>

            <p className="nsu-auth__switch">
                Trouble signing in? Contact the university administration.
            </p>
        </AuthLayout>
    );
}
