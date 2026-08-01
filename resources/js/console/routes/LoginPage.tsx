import { type FormEvent, useState } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router';
import { type AuthUser, useAuth } from '@/auth/AuthContext';
import { PasswordInput } from '@/components/PasswordInput';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost, syncCsrfToken } from '@/lib/api';
import { readLocale } from '@/lib/locale';
import { capturePlanFromSearch, pathForSelectedPlan, planQuery } from '@/lib/planIntent';
import { credentialToJson, requestOptionsFromServer, toPublicKeyCredential } from '@/lib/webauthn';

type AuthResponse = {
    user: AuthUser;
    csrf_token?: string;
    mfa_required?: boolean;
    message?: string;
};

type Mode = 'password' | 'mfa' | 'email' | 'email-code';

export function LoginPage() {
    const { t } = useI18n();
    const { user, login, loading, setUser } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const plan = capturePlanFromSearch(searchParams.toString());
    const nextPath = (() => {
        const raw = searchParams.get('next');
        if (!raw?.startsWith('/') || raw.startsWith('//')) {
            return null;
        }
        return raw;
    })();
    const [mode, setMode] = useState<Mode>('password');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [mfaCode, setMfaCode] = useState('');
    const [emailCode, setEmailCode] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [pending, setPending] = useState(false);

    if (!loading && user) {
        if (user.email_verified_at == null) {
            return <Navigate to="/verify-email" replace />;
        }

        if (nextPath) {
            return <Navigate to={nextPath} replace />;
        }

        const org = user.organizations[0];
        if (!org) {
            return <Navigate to="/" replace />;
        }

        return <Navigate to={pathForSelectedPlan(org.id, plan)} replace />;
    }

    const finishAuth = (data: AuthResponse) => {
        syncCsrfToken(data.csrf_token);
        setUser(data.user);
        navigate(nextPath ?? '/');
    };

    const onPasswordSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        setMessage(null);
        try {
            const data = await apiPost<AuthResponse>('/api/v1/auth/login', {
                email,
                password,
                preferred_locale: readLocale(),
            });
            if (data.mfa_required) {
                setMode('mfa');
                setMessage(data.message ?? t('auth.login.mfa_prompt'));
                return;
            }
            finishAuth(data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.login.error'));
        } finally {
            setPending(false);
        }
    };

    const onMfaSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        try {
            const data = await apiPost<AuthResponse>('/api/v1/auth/mfa/challenge', {
                code: mfaCode,
            });
            finishAuth(data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.login.mfa_error'));
        } finally {
            setPending(false);
        }
    };

    const onSendEmailCode = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        setMessage(null);
        try {
            const data = await apiPost<{ message: string }>('/api/v1/auth/email-otp/send', {
                email,
                preferred_locale: readLocale(),
            });
            setMessage(data.message);
            setMode('email-code');
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.login.email_code_send_error'));
        } finally {
            setPending(false);
        }
    };

    const onVerifyEmailCode = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        try {
            const data = await apiPost<AuthResponse>('/api/v1/auth/email-otp/verify', {
                email,
                code: emailCode,
            });
            if (data.mfa_required) {
                setMode('mfa');
                setMessage(data.message ?? t('auth.login.mfa_prompt'));
                return;
            }
            finishAuth(data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.login.email_code_invalid'));
        } finally {
            setPending(false);
        }
    };

    const onPasskey = async () => {
        setPending(true);
        setError(null);
        try {
            if (!window.PublicKeyCredential) {
                setError(t('auth.login.passkey_unsupported'));
                return;
            }
            const options = await apiPost<Parameters<typeof requestOptionsFromServer>[0]>(
                '/api/v1/auth/passkeys/login/options',
                { email: email || null },
            );
            const credential = toPublicKeyCredential(
                await navigator.credentials.get(requestOptionsFromServer(options)),
            );
            if (!credential) {
                setError(t('auth.login.passkey_cancelled'));
                return;
            }
            const data = await apiPost<AuthResponse>('/api/v1/auth/passkeys/login/verify', {
                credential: credentialToJson(credential),
            });
            if (data.mfa_required) {
                setMode('mfa');
                setMessage(data.message ?? t('auth.login.mfa_prompt'));
                return;
            }
            finishAuth(data);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.login.passkey_error'));
        } finally {
            setPending(false);
        }
    };

    void login;

    return (
        <div className="flex min-h-screen items-center justify-center px-5 py-16">
            <div className="w-full max-w-md space-y-8">
                <div className="flex flex-col items-center gap-3 text-center">
                    <img
                        src="/images/mark.svg?v=2"
                        alt=""
                        width={40}
                        height={40}
                        className="h-10 w-10"
                    />
                    <h1 className="font-display text-3xl font-semibold tracking-tight">
                        {t('common.brand')}
                    </h1>
                    <p className="text-sm text-ink-soft">{t('auth.login.tagline')}</p>
                </div>

                <div className="console-panel space-y-4 p-6 sm:p-7">
                    {error ? (
                        <p className="text-sm text-danger" role="alert">
                            {error}
                        </p>
                    ) : null}
                    {message ? <p className="text-sm text-teal">{message}</p> : null}

                    {mode === 'password' ? (
                        <form onSubmit={onPasswordSubmit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="login-email">{t('auth.login.email')}</Label>
                                <Input
                                    id="login-email"
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    autoComplete="username"
                                />
                            </div>
                            <PasswordInput
                                label={t('auth.login.password')}
                                required
                                autoComplete="current-password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                            />
                            <div className="flex justify-end">
                                <Button variant="link" size="sm" className="h-auto px-0" asChild>
                                    <Link to="/forgot-password">
                                        {t('auth.login.forgot_password')}
                                    </Link>
                                </Button>
                            </div>
                            <Button type="submit" className="w-full" disabled={pending}>
                                {pending ? t('auth.login.submitting') : t('auth.login.submit')}
                            </Button>
                        </form>
                    ) : null}

                    {mode === 'mfa' ? (
                        <form onSubmit={onMfaSubmit} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="login-mfa">{t('auth.login.mfa_label')}</Label>
                                <Input
                                    id="login-mfa"
                                    required
                                    value={mfaCode}
                                    onChange={(e) => setMfaCode(e.target.value)}
                                    autoComplete="one-time-code"
                                />
                            </div>
                            <Button type="submit" className="w-full" disabled={pending}>
                                {pending
                                    ? t('auth.login.mfa_submitting')
                                    : t('auth.login.mfa_submit')}
                            </Button>
                        </form>
                    ) : null}

                    {mode === 'email' ? (
                        <form onSubmit={onSendEmailCode} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="login-email-otp">{t('auth.login.email')}</Label>
                                <Input
                                    id="login-email-otp"
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                />
                            </div>
                            <Button type="submit" className="w-full" disabled={pending}>
                                {pending
                                    ? t('auth.login.email_code_sending')
                                    : t('auth.login.email_code_send')}
                            </Button>
                        </form>
                    ) : null}

                    {mode === 'email-code' ? (
                        <form onSubmit={onVerifyEmailCode} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="login-code">
                                    {t('auth.login.email_code_input')}
                                </Label>
                                <Input
                                    id="login-code"
                                    required
                                    value={emailCode}
                                    onChange={(e) => setEmailCode(e.target.value)}
                                    autoComplete="one-time-code"
                                />
                            </div>
                            <Button type="submit" className="w-full" disabled={pending}>
                                {pending
                                    ? t('auth.login.email_code_verifying')
                                    : t('auth.login.email_code_verify')}
                            </Button>
                        </form>
                    ) : null}

                    {mode !== 'mfa' ? (
                        <div className="flex flex-wrap gap-x-4 gap-y-2 border-t border-mist/50 pt-4 text-sm">
                            <button
                                type="button"
                                className="font-medium text-teal hover:underline"
                                onClick={() => {
                                    setMode('password');
                                    setError(null);
                                }}
                            >
                                {t('auth.login.mode_password')}
                            </button>
                            <button
                                type="button"
                                className="font-medium text-teal hover:underline"
                                onClick={() => {
                                    setMode('email');
                                    setError(null);
                                }}
                            >
                                {t('auth.login.mode_email_code')}
                            </button>
                            <button
                                type="button"
                                className="font-medium text-teal hover:underline disabled:opacity-50"
                                disabled={pending}
                                onClick={() => void onPasskey()}
                            >
                                {pending ? t('common.processing') : t('auth.login.mode_passkey')}
                            </button>
                        </div>
                    ) : null}
                </div>

                <p className="text-center text-sm text-ink-soft">
                    {t('auth.login.no_account')}{' '}
                    <Link
                        to={`/register${planQuery(plan)}`}
                        className="font-medium text-teal hover:underline"
                    >
                        {t('auth.login.register_link')}
                    </Link>
                </p>
            </div>
        </div>
    );
}
