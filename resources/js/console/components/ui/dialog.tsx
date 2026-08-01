import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import type { ComponentPropsWithoutRef, HTMLAttributes } from 'react';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

export const Dialog = DialogPrimitive.Root;
export const DialogTrigger = DialogPrimitive.Trigger;
export const DialogClose = DialogPrimitive.Close;

export function DialogContent({
    className,
    children,
    ...props
}: ComponentPropsWithoutRef<typeof DialogPrimitive.Content>) {
    const { t } = useI18n();

    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-ink/40 data-[state=open]:animate-in" />
            <DialogPrimitive.Content
                className={cn(
                    'fixed top-1/2 left-1/2 z-50 grid w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 rounded-lg border border-mist bg-paper-elevated p-6 shadow-lg',
                    className,
                )}
                {...props}
            >
                {children}
                <DialogPrimitive.Close className="absolute top-4 right-4 rounded-md p-1 text-ink-soft hover:bg-fog hover:text-ink">
                    <X className="h-4 w-4" />
                    <span className="sr-only">{t('console.ui.close')}</span>
                </DialogPrimitive.Close>
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}

export function DialogHeader({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('flex flex-col gap-1.5 text-left', className)} {...props} />;
}

export function DialogTitle({
    className,
    ...props
}: ComponentPropsWithoutRef<typeof DialogPrimitive.Title>) {
    return (
        <DialogPrimitive.Title
            className={cn('font-display text-lg font-semibold tracking-tight', className)}
            {...props}
        />
    );
}

export function DialogDescription({
    className,
    ...props
}: ComponentPropsWithoutRef<typeof DialogPrimitive.Description>) {
    return (
        <DialogPrimitive.Description
            className={cn('text-sm text-ink-soft/75', className)}
            {...props}
        />
    );
}
