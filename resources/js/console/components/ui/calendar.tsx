import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ComponentProps } from 'react';
import { DayPicker } from 'react-day-picker';
import { cn } from '@/lib/cn';

import 'react-day-picker/style.css';

export type CalendarProps = ComponentProps<typeof DayPicker>;

export function Calendar({ className, showOutsideDays = true, ...props }: CalendarProps) {
    return (
        <DayPicker
            showOutsideDays={showOutsideDays}
            className={cn('az-calendar', className)}
            components={{
                Chevron: ({ orientation, className: chevronClassName, ...chevronProps }) => {
                    const Icon = orientation === 'left' ? ChevronLeft : ChevronRight;
                    return <Icon className={cn('size-4', chevronClassName)} {...chevronProps} />;
                },
            }}
            {...props}
        />
    );
}
