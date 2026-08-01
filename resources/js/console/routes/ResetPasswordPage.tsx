import { type FormEvent, useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { PasswordInput } from '@/components/PasswordInput';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost } from '@/lib/api';

export function ResetPasswordPage() {
    const { t } = useI18n();
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const token = useMemo(() => params.get('token') ?? '', [params]);
    const emailFromQuery = useMemo(() => params.get('email') ?? '', [params]);

    const [email, setEmail] = useState(emailFromQuery);
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [pending, setPending] = useState(false);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        try {
            await apiPost<{ message: string }>('/api/v1/auth/reset-password', {
                token,
                email: email.trim(),
                password,
                password_confirmation: passwordConfirmation,
            });
            navigate('/login', { replace: true });
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.reset.error'));
        } finally {
            setPending(false);
        }
    };

    if (token === '') {
        return (
            <div className="flex min-h-screen items-center justify-center px-5 py-16">
                <div className="w-full max-w-md space-y-4 console-panel p-6">
                    <p className="text-sm text-danger" role="alert">
                        {t('auth.reset.missing_token')}
                    </p>
                    <Link
                        to="/forgot-password"
                        className="text-sm font-medium text-teal hover:underline"
                    >
                        {t('auth.reset.request_reset')}
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="flex min-h-screen items-center justify-center px-5 py-16">
            <div className="w-full max-w-md space-y-6">
                <div className="flex flex-col items-center gap-3 text-center">
                    <img
                        src="/images/mark.svg?v=2"
                        alt=""
                        width={40}
                        height={40}
                        className="h-10 w-10"
                    />
                    <h1 className="font-display text-2xl font-semibold tracking-tight">
                        {t('auth.reset.title')}
                    </h1>
                    <p className="text-sm text-ink-soft">{t('auth.reset.description')}</p>
                </div>

                <form onSubmit={onSubmit} className="console-panel space-y-4 p-6 sm:p-7">
                    {error ? (
                        <p className="text-sm text-danger" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <label className="block text-sm font-medium">
                        {t('auth.reset.email')}
                        <input
                            type="email"
                            required
                            autoComplete="username"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                        />
                    </label>
                    <PasswordInput
                        label={t('auth.reset.new_password')}
                        required
                        autoComplete="new-password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                    />
                    <PasswordInput
                        label={t('auth.reset.confirm_password')}
                        required
                        autoComplete="new-password"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                    />
                    <button
                        type="submit"
                        disabled={pending}
                        className="inline-flex w-full items-center justify-center inline-flex items-center justify-center rounded-[var(--radius-control)] bg-teal px-4 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright disabled:opacity-60"
                    >
                        {pending ? t('auth.reset.submitting') : t('auth.reset.submit')}
                    </button>
                </form>

                <p className="text-center text-sm text-ink-soft">
                    <Link to="/login" className="text-teal hover:underline">
                        {t('auth.reset.back_to_sign_in')}
                    </Link>
                </p>
            </div>
        </div>
    );
}
