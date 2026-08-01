import * as SelectPrimitive from '@radix-ui/react-select';
import { Check, ChevronDown } from 'lucide-react';
import type { ComponentPropsWithoutRef } from 'react';
import { cn } from '@/lib/cn';

export const Select = SelectPrimitive.Root;
export const SelectValue = SelectPrimitive.Value;

export function SelectTrigger({
    className,
    children,
    ...props
}: ComponentPropsWithoutRef<typeof SelectPrimitive.Trigger>) {
    return (
        <SelectPrimitive.Trigger
            className={cn(
                'flex h-10 w-full items-center justify-between rounded-md border border-mist bg-paper px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal disabled:opacity-50',
                className,
            )}
            {...props}
        >
            {children}
            <SelectPrimitive.Icon asChild>
                <ChevronDown className="h-4 w-4 opacity-60" />
            </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
    );
}

export function SelectContent({
    className,
    children,
    position = 'popper',
    ...props
}: ComponentPropsWithoutRef<typeof SelectPrimitive.Content>) {
    return (
        <SelectPrimitive.Portal>
            <SelectPrimitive.Content
                className={cn(
                    'relative z-50 max-h-72 min-w-[8rem] overflow-hidden rounded-md border border-mist bg-paper-elevated text-ink shadow-md',
                    className,
                )}
                position={position}
                {...props}
            >
                <SelectPrimitive.Viewport className="p-1">{children}</SelectPrimitive.Viewport>
            </SelectPrimitive.Content>
        </SelectPrimitive.Portal>
    );
}

export function SelectItem({
    className,
    children,
    ...props
}: ComponentPropsWithoutRef<typeof SelectPrimitive.Item>) {
    return (
        <SelectPrimitive.Item
            className={cn(
                'relative flex w-full cursor-pointer items-center rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none focus:bg-fog data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                className,
            )}
            {...props}
        >
            <span className="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                <SelectPrimitive.ItemIndicator>
                    <Check className="h-4 w-4" />
                </SelectPrimitive.ItemIndicator>
            </span>
            <SelectPrimitive.ItemText>{children}</SelectPrimitive.ItemText>
        </SelectPrimitive.Item>
    );
}
