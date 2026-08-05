import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { SoundToggle } from '../components/SoundToggle';
import { usePortalSound } from '../hooks/usePortalSound';
import { usePortalBody } from '../hooks/usePortalBody';
import { playChime, playClick, playStartSound } from '../services/portalSound';
import '../styles/portal.css';

const CREST = '/city-university-crest.png';

function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

export function PortalLandingPage() {
    const navigate = useNavigate();
    const { soundOn, toggleSound } = usePortalSound();

    usePortalBody();

    const loaderRef = useRef(null);
    const barRef = useRef(null);
    const pctRef = useRef(null);
    const glowRef = useRef(null);
    const flashRef = useRef(null);
    const frameRef = useRef(null);

    const [isLoaded, setIsLoaded] = useState(false);

    const play = useCallback(() => {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        setIsLoaded(false);

        loaderRef.current?.classList.remove('play');
        barRef.current.style.width = '0%';
        pctRef.current.textContent = '0%';
        glowRef.current.style.opacity = 0.06;

        void loaderRef.current?.offsetWidth;

        loaderRef.current?.classList.add('play');
        playStartSound();

        const startProgress = () => {
            const duration = reduced ? 300 : 2500;
            const start = performance.now();

            const step = (now) => {
                if (!barRef.current) {
                    return;
                }

                const t = Math.min(1, (now - start) / duration);
                const value = easeOutCubic(t) * 100;

                barRef.current.style.width = value + '%';
                pctRef.current.textContent = Math.round(value) + '%';
                glowRef.current.style.opacity = 0.08 + (value / 100) * 0.6;

                if (t < 1) {
                    requestAnimationFrame(step);

                    return;
                }

                setTimeout(() => {
                    flashRef.current?.classList.add('flash');
                    playChime();

                    setTimeout(() => {
                        setIsLoaded(true);
                        flashRef.current?.classList.remove('flash');
                    }, reduced ? 50 : 450);
                }, reduced ? 50 : 350);
            };

            requestAnimationFrame(step);
        };

        setTimeout(startProgress, reduced ? 50 : 200);
    }, []);

    useEffect(() => {
        play();
    }, [play]);

    const enterPortal = () => {
        playClick();
        navigate('/login');
    };

    return (
        <div className={`nsu-portal${isLoaded ? ' loaded' : ''}`} ref={frameRef}>
            <div className="bg-ambient" />
            <div className="grain" />

            <div className="loader-panel" ref={loaderRef}>
                <div className="crest-wrap">
                    <div className="crest-glow" ref={glowRef} />
                    <img className="crest-img" src={CREST} alt="City University Malaysia crest" />
                    <div className="crest-ring" ref={flashRef} />
                </div>

                <div className="loader-title">City University Platform</div>

                <div className="bar-wrap">
                    <div className="bar-track">
                        <div className="bar-fill" ref={barRef} />
                    </div>

                    <div className="bar-meta">
                        <span className="status">
                            Preparing your portal
                            <span className="dots">
                                <span>.</span>
                                <span>.</span>
                                <span>.</span>
                            </span>
                        </span>
                        <span className="bar-pct" ref={pctRef}>0%</span>
                    </div>
                </div>
            </div>

            <div className="hero-panel">
                <div className="hero-eyebrow">Welcome to</div>
                <h1 className="hero-title">City University Platform</h1>
                <div className="hero-sub">City University Malaysia</div>
                <button type="button" className="hero-btn" onClick={enterPortal}>
                    Enter Portal
                </button>
            </div>

            <div className="footer-bar">
                <SoundToggle soundOn={soundOn} onToggle={toggleSound} />
            </div>
        </div>
    );
}
