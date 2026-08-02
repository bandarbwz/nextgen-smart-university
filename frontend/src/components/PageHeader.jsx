export function PageHeader({ title, subtitle, actions = null }) {
    return (
        <header className="nsu-page-header">
            <div>
                <h1 className="nsu-page-header__title">{title}</h1>
                {subtitle && <p className="nsu-page-header__subtitle">{subtitle}</p>}
            </div>
            {actions}
        </header>
    );
}
