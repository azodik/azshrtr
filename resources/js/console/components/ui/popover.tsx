import * as PopoverPrimitive from '@radix-ui/react-popover';
import type { ComponentPropsWithoutRef } from 'react';
import { cn } from '@/lib/cn';

export const Popover = PopoverPrimitive.Root;
export const PopoverTrigger = PopoverPrimitive.Trigger;

export function PopoverContent({
    className,
    align = 'start',
    sideOffset = 4,
    ...props
}: ComponentPropsWithoutRef<typeof PopoverPrimitive.Content>) {
    return (
        <PopoverPrimitive.Portal>
            <PopoverPrimitive.Content
                align={align}
                sideOffset={sideOffset}
                className={cn(
                    'z-50 w-auto max-w-[min(100vw-2rem,22rem)] rounded-md border border-mist bg-paper-elevated p-2 text-ink shadow-md outline-none',
                    className,
                )}
                {...props}
            />
        </PopoverPrimitive.Portal>
    );
}
