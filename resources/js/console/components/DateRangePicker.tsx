import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import type { DateRange } from 'react-day-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';
import { parseDateInput, toDateInputValue } from '@/lib/listQuery';

type DateRangePickerProps = {
    from: string;
    to: string;
    onChange: (next: { from: string; to: string }) => void;
    className?: string;
};

export function DateRangePicker({ from, to, onChange, className }: DateRangePickerProps) {
    const { t } = useI18n();
    const selected: DateRange | undefined =
        from || to
            ? {
                  from: parseDateInput(from),
                  to: parseDateInput(to),
              }
            : undefined;

    const label =
        selected?.from && selected.to
            ? `${format(selected.from, 'MMM d, yyyy')} – ${format(selected.to, 'MMM d, yyyy')}`
            : selected?.from
              ? format(selected.from, 'MMM d, yyyy')
              : t('common.date_range.placeholder');

    return (
        <div className={cn('min-w-0', className)}>
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="secondary"
                        className={cn(
                            'h-10 w-full justify-start truncate border border-mist bg-paper text-left font-normal text-ink',
                            !from && !to && 'text-ink-soft',
                        )}
                    >
                        <CalendarIcon className="size-4 shrink-0 text-ink-soft" />
                        <span className="truncate">{label}</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="p-0" align="start" side="bottom" sideOffset={6}>
                    <Calendar
                        mode="range"
                        numberOfMonths={1}
                        selected={selected}
                        onSelect={(range) => {
                            onChange({
                                from: toDateInputValue(range?.from),
                                to: toDateInputValue(range?.to),
                            });
                        }}
                        defaultMonth={selected?.from}
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
