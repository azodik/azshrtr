import * as DropdownMenuPrimitive from '@radix-ui/react-dropdown-menu';
import { Check } from 'lucide-react';
import type { ComponentPropsWithoutRef } from 'react';
import { cn } from '@/lib/cn';

export const DropdownMenu = DropdownMenuPrimitive.Root;
export const DropdownMenuTrigger = DropdownMenuPrimitive.Trigger;
export const DropdownMenuGroup = DropdownMenuPrimitive.Group;
export const DropdownMenuPortal = DropdownMenuPrimitive.Portal;

export function DropdownMenuContent({
    className,
    sideOffset = 6,
    ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Content>) {
    return (
        <DropdownMenuPrimitive.Portal>
            <DropdownMenuPrimitive.Content
                sideOffset={sideOffset}
                className={cn(
                    'z-50 min-w-[12rem] overflow-hidden rounded-md border border-mist bg-paper-elevated p-1 text-ink shadow-md',
                    className,
                )}
                {...props}
            />
        </DropdownMenuPrimitive.Portal>
    );
}

export function DropdownMenuLabel({
    className,
    ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Label>) {
    return (
        <DropdownMenuPrimitive.Label
            className={cn('px-2 py-1.5 text-xs font-medium text-ink-soft', className)}
            {...props}
        />
    );
}

export function DropdownMenuItem({
    className,
    inset,
    asChild = false,
    ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Item> & {
    inset?: boolean;
    asChild?: boolean;
}) {
    return (
        <DropdownMenuPrimitive.Item
            asChild={asChild}
            className={cn(
                'relative flex cursor-pointer items-center rounded-sm px-2 py-1.5 text-sm outline-none select-none focus:bg-fog data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                inset && 'pl-8',
                className,
            )}
            {...props}
        />
    );
}

export function DropdownMenuCheckboxItem({
    className,
    children,
    checked,
    ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.CheckboxItem>) {
    return (
        <DropdownMenuPrimitive.CheckboxItem
            className={cn(
                'relative flex cursor-pointer items-center rounded-sm py-1.5 pr-2 pl-8 text-sm outline-none select-none focus:bg-fog data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                className,
            )}
            checked={checked}
            {...props}
        >
            <span className="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                <DropdownMenuPrimitive.ItemIndicator>
                    <Check className="h-4 w-4" />
                </DropdownMenuPrimitive.ItemIndicator>
            </span>
            {children}
        </DropdownMenuPrimitive.CheckboxItem>
    );
}

export function DropdownMenuSeparator({
    className,
    ...props
}: ComponentPropsWithoutRef<typeof DropdownMenuPrimitive.Separator>) {
    return (
        <DropdownMenuPrimitive.Separator
            className={cn('-mx-1 my-1 h-px bg-mist/70', className)}
            {...props}
        />
    );
}
