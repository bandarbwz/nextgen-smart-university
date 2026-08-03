export function Button({
    variant = 'primary',
    type = 'button',
    isLoading = false,
    block = false,
    icon: Icon = null,
    children,
    className = '',
    disabled,
    ...rest
}) {
    const classes = [
        'nsu-button',
        `nsu-button--${variant}`,
        block ? 'nsu-button--block' : '',
        className,
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <button type={type} className={classes} disabled={disabled || isLoading} {...rest}>
            {isLoading ? (
                <span className="nsu-spinner" aria-hidden="true" />
            ) : (
                Icon && <Icon size={18} aria-hidden="true" />
            )}
            <span>{children}</span>
        </button>
    );
}
