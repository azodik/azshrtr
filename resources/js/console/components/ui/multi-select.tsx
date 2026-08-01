import { ChevronsUpDown } from 'lucide-react';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

export type MultiSelectOption = {
    value: string;
    label: string;
};

type MultiSelectProps = {
    options: MultiSelectOption[];
    value: string[];
    onChange: (next: string[]) => void;
    placeholder?: string;
    className?: string;
    emptyLabel?: string;
};

export function MultiSelect({
    options,
    value,
    onChange,
    placeholder,
    className,
    emptyLabel,
}: MultiSelectProps) {
    const { t } = useI18n();
    const resolvedPlaceholder = placeholder ?? t('common.select_ellipsis');
    const resolvedEmptyLabel = emptyLabel ?? t('console.multi_select.any');

    const selectedLabels = useMemo(() => {
        if (value.length === 0) {
            return resolvedEmptyLabel;
        }
        const labels = options.filter((o) => value.includes(o.value)).map((o) => o.label);
        if (labels.length <= 2) {
            return labels.join(', ');
        }
        return t('common.n_selected', { count: labels.length });
    }, [resolvedEmptyLabel, options, value, t]);

    const toggle = (optionValue: string, checked: boolean) => {
        if (checked) {
            onChange([...value, optionValue]);
            return;
        }
        onChange(value.filter((v) => v !== optionValue));
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className={cn('h-10 w-full justify-between font-normal', className)}
                >
                    <span className="truncate">{selectedLabels || resolvedPlaceholder}</span>
                    <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-60" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-2">
                <div className="max-h-56 space-y-1 overflow-y-auto">
                    {options.map((option) => {
                        const checked = value.includes(option.value);
                        return (
                            <div
                                key={option.value}
                                className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-fog/70"
                                onClick={() => toggle(option.value, !checked)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' || event.key === ' ') {
                                        event.preventDefault();
                                        toggle(option.value, !checked);
                                    }
                                }}
                                role="option"
                                aria-selected={checked}
                                tabIndex={0}
                            >
                                <Checkbox
                                    checked={checked}
                                    onCheckedChange={(state) =>
                                        toggle(option.value, state === true)
                                    }
                                    onClick={(event) => event.stopPropagation()}
                                />
                                <span>{option.label}</span>
                            </div>
                        );
                    })}
                </div>
                {value.length > 0 ? (
                    <button
                        type="button"
                        className="mt-2 w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-teal hover:bg-fog/60"
                        onClick={() => onChange([])}
                    >
                        {t('common.clear_selection')}
                    </button>
                ) : null}
            </PopoverContent>
        </Popover>
    );
}
