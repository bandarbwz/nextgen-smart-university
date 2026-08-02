import { useId, useState } from 'react';
import { AlertCircle, Eye, EyeOff } from 'lucide-react';

export function FormField({
    label,
    type = 'text',
    value,
    onChange,
    onBlur,
    error = '',
    helper = '',
    required = false,
    autoComplete,
    placeholder,
    disabled = false,
}) {
    const id = useId();
    const [isRevealed, setIsRevealed] = useState(false);

    const isPassword = type === 'password';
    const inputType = isPassword && isRevealed ? 'text' : type;
    const errorId = `${id}-error`;
    const helperId = `${id}-helper`;

    const describedBy = [error ? errorId : '', helper ? helperId : ''].filter(Boolean).join(' ');

    return (
        <div className="nsu-field">
            <label className="nsu-field__label" htmlFor={id}>
                {label}
                {required && (
                    <span className="nsu-field__required" aria-hidden="true">
                        *
                    </span>
                )}
            </label>

            <div className="nsu-field__control">
                <input
                    id={id}
                    className={[
                        'nsu-field__input',
                        error ? 'nsu-field__input--invalid' : '',
                        isPassword ? 'nsu-field__input--with-toggle' : '',
                    ]
                        .filter(Boolean)
                        .join(' ')}
                    type={inputType}
                    value={value}
                    onChange={onChange}
                    onBlur={onBlur}
                    required={required}
                    disabled={disabled}
                    placeholder={placeholder}
                    autoComplete={autoComplete}
                    aria-invalid={error ? 'true' : 'false'}
                    aria-describedby={describedBy || undefined}
                />

                {isPassword && (
                    <button
                        type="button"
                        className="nsu-field__toggle"
                        onClick={() => setIsRevealed((current) => !current)}
                        aria-label={isRevealed ? 'Hide password' : 'Show password'}
                    >
                        {isRevealed ? <EyeOff size={18} /> : <Eye size={18} />}
                    </button>
                )}
            </div>

            {helper && !error && (
                <span className="nsu-field__helper" id={helperId}>
                    {helper}
                </span>
            )}

            {error && (
                <span className="nsu-field__error" id={errorId} role="alert">
                    <AlertCircle size={14} aria-hidden="true" />
                    {error}
                </span>
            )}
        </div>
    );
}
