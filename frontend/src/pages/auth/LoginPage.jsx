import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { SoundToggle } from '../../components/SoundToggle';
import { usePortalSound } from '../../hooks/usePortalSound';
import { usePortalBody } from '../../hooks/usePortalBody';
import { playClick } from '../../services/portalSound';
import { useAuth } from '../../hooks/useAuth';
import { readApiError } from '../../services/apiClient';
import '../../styles/portal.css';

const CREST = '/city-university-crest.png';

export function LoginPage() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const { soundOn, toggleSound } = usePortalSound();

    usePortalBody();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isVisible, setIsVisible] = useState(false);

    const redirectTo = location.state?.from ?? '/dashboard';

    useEffect(() => {
        const frame = requestAnimationFrame(() => setIsVisible(true));

        return () => cancelAnimationFrame(frame);
    }, []);

    const goBack = () => {
        playClick();
        navigate('/');
    };

    async function handleSubmit(event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        setError('');

        if (!email.trim() || !password) {
            setError('Enter your email address and password.');

            return;
        }

        setIsSubmitting(true);

        try {
            await login(email.trim(), password);

            navigate(redirectTo, { replace: true });
        } catch (apiError) {
            setError(readApiError(apiError, 'Unable to sign in right now.').message);
            setIsSubmitting(false);
        }
    }

    return (
        <div className={`nsu-portal${isVisible ? ' login' : ''}`}>
            <div className="bg-ambient" />
            <div className="grain" />

            <div className="login-panel">
                <button type="button" className="back-link" onClick={goBack}>
                    &larr; Back
                </button>

                <img className="login-crest" src={CREST} alt="City University Malaysia crest" />

                <div className="login-tag">Student Portal &middot; PJ Campus</div>

                <form className="login-form" onSubmit={handleSubmit} noValidate>
                    {error && <div className="login-error" role="alert">{error}</div>}

                    <label className="field">
                        <span>Email</span>
                        <input
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            placeholder="you@city.edu.my"
                            autoComplete="username"
                        />
                    </label>

                    <label className="field">
                        <span>Password</span>
                        <input
                            type="password"
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            placeholder="••••••••"
                            autoComplete="current-password"
                        />
                    </label>

                    <button type="submit" className="login-submit" disabled={isSubmitting}>
                        {isSubmitting ? 'Signing in…' : 'Login'}
                    </button>
                </form>

                <div className="login-links">
                    <span>
                        <Link to="/forgot-password">Forgot your password?</Link>
                    </span>
                </div>
            </div>

            <div className="footer-bar">
                <SoundToggle soundOn={soundOn} onToggle={toggleSound} />
            </div>
        </div>
    );
}
