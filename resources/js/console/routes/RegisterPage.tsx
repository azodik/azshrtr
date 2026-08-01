import { type FormEvent, useState } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router';
import { useAuth } from '@/auth/AuthContext';
import { PasswordInput } from '@/components/PasswordInput';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n/useI18n';
import { ApiError } from '@/lib/api';
import { capturePlanFromSearch, pathForSelectedPlan, planQuery } from '@/lib/planIntent';

export function RegisterPage() {
    const { t } = useI18n();
    const { user, register, loading } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const plan = capturePlanFromSearch(searchParams.toString());
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [acceptedTerms, setAcceptedTerms] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [pending, setPending] = useState(false);

    if (!loading && user) {
        if (user.email_verified_at == null) {
            return <Navigate to="/verify-email" replace />;
        }

        const org = user.organizations[0];
        if (!org) {
            return <Navigate to="/" replace />;
        }

        return <Navigate to={pathForSelectedPlan(org.id, plan)} replace />;
    }

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();
        if (!acceptedTerms) {
            setError(t('auth.register.terms_required'));
            return;
        }
        setPending(true);
        setError(null);
        try {
            await register(name, email, password, passwordConfirmation, acceptedTerms);
            navigate('/verify-email');
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.register.error'));
        } finally {
            setPending(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-5 py-16">
            <form
                onSubmit={onSubmit}
                className="console-panel w-full max-w-md space-y-4 p-6 sm:p-7"
            >
                <div className="space-y-1 text-center">
                    <p className="font-display text-2xl font-semibold tracking-tight">
                        {t('common.brand')}
                    </p>
                    <h1 className="font-display text-xl font-semibold text-ink-soft">
                        {t('auth.register.title')}
                    </h1>
                </div>
                {error ? <p className="text-sm text-danger">{error}</p> : null}
                <div className="space-y-1.5">
                    <Label htmlFor="register-name">{t('auth.register.name')}</Label>
                    <Input
                        id="register-name"
                        required
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                    />
                </div>
                <div className="space-y-1.5">
                    <Label htmlFor="register-email">{t('auth.register.email')}</Label>
                    <Input
                        id="register-email"
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                    />
                </div>
                <PasswordInput
                    label={t('auth.register.password')}
                    required
                    autoComplete="new-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                />
                <PasswordInput
                    label={t('auth.register.confirm_password')}
                    required
                    autoComplete="new-password"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                />
                <label className="flex cursor-pointer items-start gap-2.5 text-sm text-ink-soft">
                    <input
                        type="checkbox"
                        checked={acceptedTerms}
                        onChange={(e) => setAcceptedTerms(e.target.checked)}
                        className="peer sr-only"
                        required
                    />
                    <span
                        aria-hidden="true"
                        className="mt-0.5 grid size-4 shrink-0 place-items-center rounded border border-mist bg-paper peer-checked:border-teal peer-checked:bg-teal peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-teal"
                    >
                        <svg
                            viewBox="0 0 12 12"
                            aria-hidden="true"
                            className={`size-2.5 text-paper-elevated ${acceptedTerms ? 'opacity-100' : 'opacity-0'}`}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M2 6.5 4.5 9 10 3" />
                        </svg>
                    </span>
                    <span className="leading-snug">
                        {t('auth.register.terms_prefix')}{' '}
                        <a
                            href="/privacy"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-medium text-teal hover:underline"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {t('auth.register.privacy_policy')}
                        </a>{' '}
                        {t('auth.register.terms_and')}{' '}
                        <a
                            href="/terms"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-medium text-teal hover:underline"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {t('auth.register.terms_of_service')}
                        </a>
                        {t('auth.register.terms_suffix')}
                    </span>
                </label>
                <Button type="submit" className="w-full" disabled={pending || !acceptedTerms}>
                    {pending ? t('auth.register.submitting') : t('auth.register.submit')}
                </Button>
                <p className="text-center text-sm text-ink-soft">
                    {t('auth.register.have_account')}{' '}
                    <Link
                        to={`/login${planQuery(plan)}`}
                        className="font-medium text-teal hover:underline"
                    >
                        {t('auth.register.sign_in_link')}
                    </Link>
                </p>
            </form>
        </div>
    );
}
