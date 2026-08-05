import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Bell, LogOut, Menu, Moon, Settings, Sun, User } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { useTheme } from '../hooks/useTheme';
import { visibleGroupsForRole } from './navigationItems';
import { NOTIFICATIONS_CHANGED, notificationService } from '../services/notificationService';

const NAV_PREFERENCE = 'nsu.navigation-open';

function readNavPreference() {
    const stored = localStorage.getItem(NAV_PREFERENCE);

    if (stored !== null) {
        return stored === 'true';
    }

    return window.matchMedia('(min-width: 1024px)').matches;
}

function initialsOf(fullName) {
    return fullName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

export function AppLayout() {
    const { user, logout } = useAuth();
    const { theme, toggleTheme } = useTheme();
    const navigate = useNavigate();
    const location = useLocation();

    const [isNavOpen, setIsNavOpen] = useState(readNavPreference);
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [unread, setUnread] = useState(0);

    useEffect(() => {
        const load = () =>
            notificationService
                .unreadCount()
                .then(setUnread)
                .catch(() => undefined);

        const refresh = () => {
            if (!document.hidden) {
                load();
            }
        };

        load();

        const timer = setInterval(refresh, 60000);

        document.addEventListener('visibilitychange', refresh);
        window.addEventListener(NOTIFICATIONS_CHANGED, load);

        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', refresh);
            window.removeEventListener(NOTIFICATIONS_CHANGED, load);
        };
    }, [location.pathname]);

    useEffect(() => {
        setIsMenuOpen(false);

        if (!window.matchMedia('(min-width: 1024px)').matches) {
            setIsNavOpen(false);
        }
    }, [location.pathname]);

    function toggleNavigation() {
        setIsNavOpen((current) => {
            localStorage.setItem(NAV_PREFERENCE, String(!current));

            return !current;
        });
    }

    useEffect(() => {
        if (!isMenuOpen) {
            return undefined;
        }

        const close = (event) => {
            if (!event.target.closest('.nsu-shell__menu')) {
                setIsMenuOpen(false);
            }
        };

        document.addEventListener('pointerdown', close);

        return () => document.removeEventListener('pointerdown', close);
    }, [isMenuOpen]);

    const groups = visibleGroupsForRole(user.role);

    async function handleLogout() {
        await logout();

        navigate('/login', { replace: true });
    }

    return (
        <div className="nsu-shell">
            <a className="skip-link" href="#main-content">
                Skip to main content
            </a>

            <header className="nsu-shell__topbar">
                <button
                    type="button"
                    className="nsu-shell__menu-button"
                    onClick={toggleNavigation}
                    aria-label={isNavOpen ? 'Hide navigation' : 'Show navigation'}
                    aria-expanded={isNavOpen}
                    aria-controls="main-navigation"
                >
                    <Menu size={18} />
                </button>

                <div className="nsu-shell__brand">
                    <img className="nsu-shell__crest" src="/city-university-crest.png" alt="" />
                    <span className="nsu-shell__brand-text">City University Platform</span>
                </div>

                <div className="nsu-shell__spacer" />

                <button
                    type="button"
                    className="nsu-shell__icon-button"
                    onClick={toggleTheme}
                    aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
                >
                    {theme === 'dark' ? <Sun size={20} /> : <Moon size={20} />}
                </button>

                <NavLink
                    to="/notifications"
                    className="nsu-bell"
                    aria-label={
                        unread > 0 ? `Notifications, ${unread} unread` : 'Notifications'
                    }
                >
                    <Bell size={20} />
                    {unread > 0 && (
                        <span className="nsu-bell__count" aria-hidden="true">
                            {unread > 99 ? '99+' : unread}
                        </span>
                    )}
                </NavLink>

                <div className="nsu-shell__menu">
                    <button
                        type="button"
                        className="nsu-shell__profile"
                        onClick={() => setIsMenuOpen((current) => !current)}
                        aria-haspopup="menu"
                        aria-expanded={isMenuOpen}
                    >
                        <span className="nsu-shell__avatar" aria-hidden="true">
                            {initialsOf(user.full_name)}
                        </span>
                        <span className="nsu-shell__user-meta">
                            <span className="nsu-shell__user-name">{user.full_name}</span>
                            <br />
                            <span className="nsu-shell__user-role">{user.role}</span>
                        </span>
                    </button>

                    {isMenuOpen && (
                        <div className="nsu-shell__dropdown" role="menu">
                            <NavLink to="/profile" role="menuitem">
                                <User size={16} aria-hidden="true" /> View profile
                            </NavLink>
                            <NavLink to="/settings" role="menuitem">
                                <Settings size={16} aria-hidden="true" /> Settings
                            </NavLink>

                            <div className="nsu-shell__dropdown-sep" />

                            <button
                                type="button"
                                className="is-danger"
                                onClick={handleLogout}
                                role="menuitem"
                            >
                                <LogOut size={16} aria-hidden="true" /> Log out
                            </button>
                        </div>
                    )}
                </div>
            </header>

            <div className="nsu-shell__body">
                <nav
                    id="main-navigation"
                    className={`nsu-shell__sidebar ${isNavOpen ? 'nsu-shell__sidebar--open' : ''}`}
                    aria-label="Main navigation"
                >
                    <div className="nsu-nav">
                        {groups.map((group) => (
                            <div key={group.label}>
                                <p className="nsu-nav__group-label">{group.label}</p>

                                {group.items.map((item) => (
                                    <NavLink
                                        key={item.to}
                                        to={item.to}
                                        className={({ isActive }) =>
                                            `nsu-nav__link ${isActive ? 'nsu-nav__link--active' : ''}`
                                        }
                                    >
                                        <item.icon size={18} aria-hidden="true" />
                                        {item.label}
                                    </NavLink>
                                ))}
                            </div>
                        ))}
                    </div>
                </nav>

                {isNavOpen && (
                    <button
                        type="button"
                        className="nsu-shell__scrim"
                        onClick={toggleNavigation}
                        aria-label="Close navigation menu"
                    />
                )}

                <main className="nsu-shell__main" id="main-content">
                    <div className="nsu-shell__content">
                        <Outlet />
                    </div>
                </main>
            </div>
        </div>
    );
}
