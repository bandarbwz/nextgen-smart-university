import { CalendarDays, GraduationCap, ShieldCheck, Users } from 'lucide-react';

export function AuthLayout({ title, subtitle, children }) {
    return (
        <div className="nsu-auth">
            <aside className="nsu-auth__brand">
                <div className="nsu-auth__brand-mark">
                    <GraduationCap size={26} aria-hidden="true" />
                    NextGen Smart University
                </div>

                <div>
                    <h2 className="nsu-auth__headline">One platform for every campus service</h2>
                    <p className="nsu-auth__tagline">
                        Registration, attendance, learning materials, results and campus life,
                        together in a single secure portal.
                    </p>

                    <ul className="nsu-auth__points">
                        <li>
                            <CalendarDays size={16} aria-hidden="true" />
                            Course registration with automatic clash detection
                        </li>
                        <li>
                            <Users size={16} aria-hidden="true" />
                            Dedicated portals for students, lecturers and staff
                        </li>
                        <li>
                            <ShieldCheck size={16} aria-hidden="true" />
                            Role based access with full activity logging
                        </li>
                    </ul>
                </div>

                <p className="nsu-auth__footnote">Copyright 2026 NextGen Smart University</p>
            </aside>

            <main className="nsu-auth__panel" id="main-content">
                <div className="nsu-auth__form">
                    <div className="nsu-auth__mobile-mark">
                        <GraduationCap size={22} aria-hidden="true" />
                        NextGen Smart University
                    </div>

                    <h1 className="nsu-auth__title">{title}</h1>
                    <p className="nsu-auth__subtitle">{subtitle}</p>

                    {children}
                </div>
            </main>
        </div>
    );
}
