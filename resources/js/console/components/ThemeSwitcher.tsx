import { Check, Monitor, Moon, Sun } from 'lucide-react';
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
import type { ThemePreference } from '@/lib/theme';
import { useTheme } from '@/theme/ThemeProvider';

const OPTIONS: Array<{
    value: ThemePreference;
    labelKey: string;
    icon: typeof Sun;
}> = [
    { value: 'light', labelKey: 'console.theme.light', icon: Sun },
    { value: 'dark', labelKey: 'console.theme.dark', icon: Moon },
    { value: 'system', labelKey: 'console.theme.system', icon: Monitor },
];

type ThemeSwitcherProps = {
    className?: string;
};

export function ThemeSwitcher({ className }: ThemeSwitcherProps) {
    const { t } = useI18n();
    const { preference, resolved, setPreference } = useTheme();
    const { user, setUser } = useAuth();

    const ActiveIcon = preference === 'system' ? Monitor : resolved === 'dark' ? Moon : Sun;

    const onSelect = (next: ThemePreference) => {
        setPreference(next);
        if (!user) {
            return;
        }
        void (async () => {
            try {
                const data = await apiPatch<{ user: typeof user }>('/api/v1/auth/profile', {
                    theme_preference: next,
                });
                setUser(data.user);
            } catch {
                // Theme still applied locally for this session.
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
                    aria-label={t('console.theme.aria_label')}
                >
                    <ActiveIcon className="h-3.5 w-3.5 shrink-0 opacity-70" />
                    <span className="hidden sm:inline">{t('console.theme.label')}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>{t('console.theme.label')}</DropdownMenuLabel>
                {OPTIONS.map((option) => {
                    const Icon = option.icon;
                    const selected = preference === option.value;
                    return (
                        <DropdownMenuItem
                            key={option.value}
                            onSelect={() => onSelect(option.value)}
                            className="gap-2"
                        >
                            <Icon className="h-4 w-4 shrink-0 opacity-70" />
                            <span className="flex-1">{t(option.labelKey)}</span>
                            <Check
                                className={cn(
                                    'h-4 w-4 shrink-0',
                                    selected ? 'opacity-100' : 'opacity-0',
                                )}
                            />
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
