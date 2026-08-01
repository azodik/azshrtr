import { Check, Languages } from 'lucide-react';
import { useAuth } from '@/auth/AuthContext';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/i18n/useI18n';
import { apiPatch } from '@/lib/api';
import { cn } from '@/lib/cn';
import { LOCALES, type LocaleCode, localeNativeLabel } from '@/lib/locale';

type LanguageSwitcherProps = {
    className?: string;
};

export function LanguageSwitcher({ className }: LanguageSwitcherProps) {
    const { t, locale, setLocale } = useI18n();
    const { user, setUser } = useAuth();

    const onSelect = (next: LocaleCode) => {
        setLocale(next);
        if (!user) {
            return;
        }
        void (async () => {
            try {
                const data = await apiPatch<{ user: typeof user }>('/api/v1/auth/profile', {
                    preferred_locale: next,
                });
                setUser(data.user);
            } catch {
                // Locale still applied locally for this session.
            }
        })();
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className={cn('h-9 gap-1.5 px-2.5 font-medium', className)}
                    aria-label={t('console.language.aria_label')}
                >
                    <Languages className="h-3.5 w-3.5 shrink-0 opacity-70" />
                    <span className="hidden sm:inline">{localeNativeLabel(locale)}</span>
                    <span className="sm:hidden">
                        {LOCALES.find((item) => item.value === locale)?.short ?? 'EN'}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>{t('console.language.label')}</DropdownMenuLabel>
                {LOCALES.map((item) => {
                    const selected = item.value === locale;
                    return (
                        <DropdownMenuItem
                            key={item.value}
                            onSelect={() => onSelect(item.value)}
                            className="gap-2"
                        >
                            <Check
                                className={cn(
                                    'h-4 w-4 shrink-0',
                                    selected ? 'opacity-100' : 'opacity-0',
                                )}
                            />
                            <span>{item.label}</span>
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
