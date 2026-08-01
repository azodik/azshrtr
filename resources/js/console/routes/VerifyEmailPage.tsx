import { type FormEvent, useEffect, useState } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router';
import { type AuthUser, useAuth } from '@/auth/AuthContext';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost, syncCsrfToken } from '@/lib/api';
import { pathForSelectedPlan, readSelectedPlan } from '@/lib/planIntent';

function destinationFor(user: AuthUser): string {
    const org = user.organizations[0];
    if (!org) {
        return '/';
    }

    return pathForSelectedPlan(org.id, readSelectedPlan());
}

type VerifyResponse = {
    message: string;
    user?: AuthUser;
    csrf_token?: string;
};

export function VerifyEmailPage() {
    const { t } = useI18n();
    const [searchParams] = useSearchParams();
    const tokenFromQuery = searchParams.get('token') ?? '';
    const { user, loading: authLoading, setUser } = useAuth();
    const navigate = useNavigate();
    const [code, setCode] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [autoTried, setAutoTried] = useState(false);

    const applyVerifiedSession = (response: VerifyResponse): void => {
        syncCsrfToken(response.csrf_token);
        if (!response.user) {
            throw new Error(t('auth.verify.error'));
        }
        setUser(response.user);
        setMessage(response.message);
        navigate(destinationFor(response.user), { replace: true });
    };

    useEffect(() => {
        if (authLoading || tokenFromQuery === '' || autoTried) {
            return;
        }

        setAutoTried(true);
        setBusy(true);
        setError(null);

        void apiPost<VerifyResponse>('/api/v1/auth/email/verify', {
            token: tokenFromQuery,
        })
            .then((response) => {
                syncCsrfToken(response.csrf_token);
                if (response.user) {
                    setUser(response.user);
                    setMessage(response.message);
                    navigate(destinationFor(response.user), { replace: true });
                }
            })
            .catch((err: unknown) => {
                setError(err instanceof ApiError ? err.message : t('auth.verify.error'));
            })
            .finally(() => {
                setBusy(false);
            });
    }, [authLoading, tokenFromQuery, autoTried, setUser, navigate, t]);

    if (!authLoading && user?.email_verified_at != null && tokenFromQuery === '') {
        return <Navigate to={destinationFor(user)} replace />;
    }

    const onVerifyCode = async (event: FormEvent) => {
        event.preventDefault();
        setBusy(true);
        setError(null);
        setMessage(null);

        try {
            const response = await apiPost<VerifyResponse>('/api/v1/auth/email/verify', {
                code: code.trim(),
            });
            applyVerifiedSession(response);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.verify.error'));
        } finally {
            setBusy(false);
        }
    };

    const onResend = async () => {
        setBusy(true);
        setError(null);
        setMessage(null);

        try {
            const response = await apiPost<{ message: string; csrf_token?: string }>(
                '/api/v1/auth/email/resend-confirmation',
            );
            syncCsrfToken(response.csrf_token);
            setMessage(response.message);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.verify.resend_error'));
        } finally {
            setBusy(false);
        }
    };

    const autoVerifying = authLoading || (busy && tokenFromQuery !== '' && error === null);

    return (
        <div className="flex min-h-screen items-center justify-center px-5 py-16">
            <div className="w-full max-w-md console-panel space-y-6 p-6 sm:p-7">
                <div className="flex flex-col items-center gap-3 text-center">
                    <img
                        src="/images/mark.svg?v=2"
                        alt=""
                        width={40}
                        height={40}
                        className="h-10 w-10"
                    />
                    <h1 className="font-display text-2xl font-semibold tracking-tight">
                        {t('auth.verify.title')}
                    </h1>
                    <p className="text-sm text-ink-soft">
                        {t('auth.verify.description_prefix')}{' '}
                        <strong className="text-ink">
                            {user?.email ?? t('auth.verify.your_inbox')}
                        </strong>
                        {t('auth.verify.description_suffix')}
                    </p>
                </div>

                {autoVerifying ? (
                    <p className="text-center text-sm text-ink-soft">
                        {t('auth.verify.verifying')}
                    </p>
                ) : null}

                {message ? <p className="text-sm text-success">{message}</p> : null}
                {error ? (
                    <p className="text-sm text-danger" role="alert">
                        {error}
                    </p>
                ) : null}

                <form className="space-y-3" onSubmit={(e) => void onVerifyCode(e)}>
                    <label className="block text-sm font-medium">
                        {t('auth.verify.code_label')}
                        <input
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            maxLength={6}
                            inputMode="numeric"
                            pattern="[0-9]{6}"
                            placeholder={t('auth.verify.code_placeholder')}
                            className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25 font-mono tracking-widest"
                        />
                    </label>
                    <button
                        type="submit"
                        disabled={busy || code.trim().length !== 6}
                        className="inline-flex w-full items-center justify-center inline-flex items-center justify-center rounded-[var(--radius-control)] bg-teal px-4 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright disabled:opacity-60"
                    >
                        {busy ? t('auth.verify.submitting') : t('auth.verify.submit')}
                    </button>
                </form>

                <div className="flex flex-col gap-2 text-sm">
                    {user ? (
                        <button
                            type="button"
                            disabled={busy}
                            onClick={() => void onResend()}
                            className="text-left text-teal hover:underline disabled:opacity-50"
                        >
                            {t('auth.verify.resend')}
                        </button>
                    ) : (
                        <Link to="/login" className="text-teal hover:underline">
                            {t('auth.verify.sign_in_to_resend')}
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}
