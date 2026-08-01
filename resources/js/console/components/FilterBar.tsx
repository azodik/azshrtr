import { Search, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

type FilterBarProps = {
    query?: string;
    onQueryChange?: (value: string) => void;
    searchPlaceholder?: string;
    onSubmit?: () => void;
    onClear?: () => void;
    showApply?: boolean;
    children?: ReactNode;
    className?: string;
    trailing?: ReactNode;
};

export function FilterBar({
    query,
    onQueryChange,
    searchPlaceholder,
    onSubmit,
    onClear,
    showApply = false,
    children,
    className,
    trailing,
}: FilterBarProps) {
    const { t } = useI18n();
    const hasSearch = typeof onQueryChange === 'function';
    const hasClear = typeof onClear === 'function';

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit?.();
    };

    return (
        <form
            onSubmit={handleSubmit}
            className={cn('console-panel space-y-4 p-4 sm:p-5', className)}
        >
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                {hasSearch ? (
                    <div className="relative min-w-0 flex-1">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-soft/70"
                            aria-hidden="true"
                        />
                        <Input
                            type="search"
                            value={query ?? ''}
                            onChange={(e) => onQueryChange?.(e.target.value)}
                            placeholder={searchPlaceholder ?? t('common.search')}
                            className="pl-9"
                            aria-label={searchPlaceholder ?? t('common.search')}
                        />
                    </div>
                ) : (
                    <div className="min-w-0 flex-1" />
                )}
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {hasClear ? (
                        <Button type="button" variant="ghost" size="sm" onClick={onClear}>
                            <X className="h-3.5 w-3.5" />
                            {t('common.clear_filters')}
                        </Button>
                    ) : null}
                    {showApply ? (
                        <Button type="submit" size="sm">
                            {t('common.apply_filters')}
                        </Button>
                    ) : null}
                    {trailing}
                </div>
            </div>

            {children ? (
                <div className="grid grid-cols-1 gap-3 min-[720px]:grid-cols-2 min-[1100px]:grid-cols-3">
                    {children}
                </div>
            ) : null}
        </form>
    );
}
