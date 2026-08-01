import { Link } from 'react-router';
import { AuthShell } from '@/components/AuthShell';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';

type NotFoundPageProps = {
    /** When true, skip AuthShell (already inside ConsoleShell). */
    embedded?: boolean;
};

export function NotFoundPage({ embedded = false }: NotFoundPageProps) {
    const { t } = useI18n();

    const content = (
        <div
            className={
                embedded
                    ? 'flex min-h-[50vh] items-center justify-center px-4 py-12'
                    : 'flex min-h-screen items-center justify-center px-4 py-16'
            }
        >
            <div className="w-full max-w-md space-y-6 text-center">
                <p className="font-display text-sm font-semibold tracking-[0.12em] text-teal uppercase">
                    {t('errors.404.code')}
                </p>
                <h1 className="font-display text-3xl font-semibold tracking-tight text-ink">
                    {t('errors.404.title')}
                </h1>
                <p className="text-ink-soft">{t('errors.404.body')}</p>
                <div className="flex flex-wrap items-center justify-center gap-3">
                    <Button asChild>
                        <Link to="/">{t('errors.404.console_home')}</Link>
                    </Button>
                    <Button asChild variant="secondary">
                        <a href="/">{t('errors.404.marketing_home')}</a>
                    </Button>
                </div>
            </div>
        </div>
    );

    if (embedded) {
        return content;
    }

    return <AuthShell>{content}</AuthShell>;
}
