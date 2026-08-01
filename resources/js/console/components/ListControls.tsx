import { ListFilter } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import { DateRangePicker } from '@/components/DateRangePicker';
import { FilterBar } from '@/components/FilterBar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';
import { isSortDirection, type ListQueryState, type SortDirection } from '@/lib/listQuery';

export type SortOption = {
    value: string;
    label: string;
};

type ListControlsProps = {
    state: ListQueryState;
    onQueryChange: (q: string) => void;
    onSortChange: (sort: string) => void;
    onDirectionChange: (direction: SortDirection) => void;
    onDateChange: (next: { from: string; to: string }) => void;
    onApply: () => void;
    onClear?: () => void;
    sortOptions: SortOption[];
    searchPlaceholder?: string;
    children?: ReactNode;
    trailing?: ReactNode;
    defaultOpen?: boolean;
};

export function ListControls({
    state,
    onQueryChange,
    onSortChange,
    onDirectionChange,
    onDateChange,
    onApply,
    onClear,
    sortOptions,
    searchPlaceholder,
    children,
    trailing,
    defaultOpen = false,
}: ListControlsProps) {
    const { t } = useI18n();
    const hasActiveFilters = state.q !== '' || state.from !== '' || state.to !== '';
    const [open, setOpen] = useState(defaultOpen || hasActiveFilters);

    useEffect(() => {
        if (hasActiveFilters) {
            setOpen(true);
        }
    }, [hasActiveFilters]);

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant={open ? 'secondary' : 'outline'}
                    size="sm"
                    className="gap-2"
                    aria-expanded={open}
                    aria-controls="console-list-filters"
                    onClick={() => setOpen((value) => !value)}
                >
                    <ListFilter className="size-3.5 shrink-0" />
                    <span>{t('common.filters')}</span>
                    {hasActiveFilters ? (
                        <span
                            className={cn(
                                'inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-teal px-1.5 text-[0.65rem] font-semibold text-paper-elevated',
                            )}
                        >
                            {t('common.filters_on')}
                        </span>
                    ) : null}
                </Button>
                {trailing}
            </div>

            {open ? (
                <div id="console-list-filters">
                    <FilterBar
                        query={state.q}
                        onQueryChange={onQueryChange}
                        searchPlaceholder={searchPlaceholder}
                        showApply
                        onSubmit={onApply}
                        onClear={hasActiveFilters ? onClear : undefined}
                    >
                        <div className="min-w-0 space-y-1.5">
                            <Label>{t('common.date_range.label')}</Label>
                            <DateRangePicker
                                from={state.from}
                                to={state.to}
                                onChange={onDateChange}
                            />
                        </div>
                        <div className="min-w-0 space-y-1.5">
                            <Label>{t('common.sort_by')}</Label>
                            <Select value={state.sort} onValueChange={onSortChange}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sortOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="min-w-0 space-y-1.5">
                            <Label>{t('common.sort_direction')}</Label>
                            <Select
                                value={state.direction}
                                onValueChange={(value) => {
                                    if (isSortDirection(value)) {
                                        onDirectionChange(value);
                                    }
                                }}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="desc">{t('common.sort_desc')}</SelectItem>
                                    <SelectItem value="asc">{t('common.sort_asc')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {children}
                    </FilterBar>
                </div>
            ) : null}
        </div>
    );
}
