export function validateEmail(value) {
    if (!value.trim()) {
        return 'Email is required.';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return 'Enter a valid email address.';
    }

    return '';
}

export function validateRequired(value, label) {
    return value.trim() ? '' : `${label} is required.`;
}

export function validatePassword(value) {
    if (!value) {
        return 'Password is required.';
    }

    const rules = [
        [value.length >= 8, 'at least 8 characters'],
        [/[A-Z]/.test(value), 'one uppercase letter'],
        [/[a-z]/.test(value), 'one lowercase letter'],
        [/[0-9]/.test(value), 'one number'],
        [/[^A-Za-z0-9]/.test(value), 'one special character'],
    ];

    const missing = rules.filter(([passed]) => !passed).map(([, text]) => text);

    return missing.length ? `Password needs ${missing.join(', ')}.` : '';
}

export function validateMatch(value, confirmation) {
    return value === confirmation ? '' : 'Passwords do not match.';
}
