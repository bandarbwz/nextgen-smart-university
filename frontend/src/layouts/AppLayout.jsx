import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { GraduationCap, LogOut, Menu, Moon, Sun, User } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { useTheme } from '../hooks/useTheme';
import { visibleGroupsForRole } from './navigationItems';

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

    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    useEffect(() => {
        setIsSidebarOpen(false);
    }, [location.pathname]);

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
                    className="nsu-shell__icon-button nsu-shell__menu-button"
                    onClick={() => setIsSidebarOpen((current) => !current)}
                    aria-label="Toggle navigation menu"
                    aria-expanded={isSidebarOpen}
                >
                    <Menu size={20} />
                </button>

                <div className="nsu-shell__brand">
                    <GraduationCap size={24} aria-hidden="true" />
                    <span className="nsu-shell__brand-text">NextGen Smart University</span>
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

                <NavLink to="/profile" className="nsu-shell__icon-button" aria-label="Open profile">
                    <User size={20} />
                </NavLink>

                <div className="nsu-shell__user">
                    <span className="nsu-shell__avatar" aria-hidden="true">
                        {initialsOf(user.full_name)}
                    </span>
                    <span className="nsu-shell__user-meta">
                        <span className="nsu-shell__user-name">{user.full_name}</span>
                        <br />
                        <span className="nsu-shell__user-role">{user.role}</span>
                    </span>
                </div>

                <button
                    type="button"
                    className="nsu-shell__icon-button"
                    onClick={handleLogout}
                    aria-label="Sign out"
                >
                    <LogOut size={20} />
                </button>
            </header>

            <div className="nsu-shell__body">
                <nav
                    className={`nsu-shell__sidebar ${isSidebarOpen ? 'nsu-shell__sidebar--open' : ''}`}
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

                {isSidebarOpen && (
                    <button
                        type="button"
                        className="nsu-shell__scrim"
                        onClick={() => setIsSidebarOpen(false)}
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
