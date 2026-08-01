import { type FormEvent, useState } from 'react';
import { Link } from 'react-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost } from '@/lib/api';
import { readLocale } from '@/lib/locale';

export function ForgotPasswordPage() {
    const { t } = useI18n();
    const [email, setEmail] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [pending, setPending] = useState(false);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setError(null);
        setMessage(null);
        try {
            const data = await apiPost<{ message: string }>('/api/v1/auth/forgot-password', {
                email: email.trim(),
                preferred_locale: readLocale(),
            });
            setMessage(data.message);
        } catch (err) {
            setError(err instanceof ApiError ? err.message : t('auth.forgot.error'));
        } finally {
            setPending(false);
        }
    };

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
                        {t('auth.forgot.title')}
                    </h1>
                    <p className="text-sm text-ink-soft">{t('auth.forgot.description')}</p>
                </div>

                <form onSubmit={onSubmit} className="console-panel space-y-4 p-6 sm:p-7">
                    {error ? (
                        <p className="text-sm text-danger" role="alert">
                            {error}
                        </p>
                    ) : null}
                    {message ? <p className="text-sm text-teal">{message}</p> : null}
                    <div className="space-y-1.5">
                        <Label htmlFor="forgot-email">{t('auth.forgot.email')}</Label>
                        <Input
                            id="forgot-email"
                            type="email"
                            required
                            autoComplete="username"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                    </div>
                    <Button type="submit" className="w-full" disabled={pending}>
                        {pending ? t('auth.forgot.submitting') : t('auth.forgot.submit')}
                    </Button>
                </form>

                <p className="text-center text-sm text-ink-soft">
                    <Link to="/login" className="font-medium text-teal hover:underline">
                        {t('auth.forgot.back_to_sign_in')}
                    </Link>
                </p>
            </div>
        </div>
    );
}
