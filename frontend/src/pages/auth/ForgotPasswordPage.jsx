import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Send } from 'lucide-react';
import { AuthLayout } from '../../layouts/AuthLayout';
import { Alert } from '../../components/Alert';
import { Button } from '../../components/Button';
import { FormField } from '../../components/FormField';
import { authService } from '../../services/authService';
import { readApiError } from '../../services/apiClient';
import { validateEmail } from '../../utils/validators';

export function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [error, setError] = useState('');
    const [formError, setFormError] = useState('');
    const [isSent, setIsSent] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event) {
        event.preventDefault();

        const emailError = validateEmail(email);

        setError(emailError);
        setFormError('');

        if (emailError) {
            return;
        }

        setIsSubmitting(true);

        try {
            await authService.forgotPassword(email.trim());

            setIsSent(true);
        } catch (apiError) {
            setFormError(readApiError(apiError).message);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <AuthLayout
            title="Reset your password"
            subtitle="Enter your university email and we will send you a reset link."
        >
            {isSent ? (
                <>
                    <Alert variant="success">
                        If an account matches that email, reset instructions have been sent. The
                        link expires in one hour.
                    </Alert>
                    <p className="nsu-auth__switch">
                        <Link to="/login">Back to sign in</Link>
                    </p>
                </>
            ) : (
                <form onSubmit={handleSubmit} noValidate>
                    {formError && <Alert variant="error">{formError}</Alert>}

                    <FormField
                        label="University email"
                        type="email"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        onBlur={() => setError(validateEmail(email))}
                        error={error}
                        placeholder="student@nextgen.edu"
                        autoComplete="username"
                        required
                    />

                    <Button type="submit" icon={Send} isLoading={isSubmitting} block>
                        {isSubmitting ? 'Sending' : 'Send reset link'}
                    </Button>

                    <p className="nsu-auth__switch">
                        <Link to="/login">Back to sign in</Link>
                    </p>
                </form>
            )}
        </AuthLayout>
    );
}
