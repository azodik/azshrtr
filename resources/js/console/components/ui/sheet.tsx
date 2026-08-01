import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import type { ComponentPropsWithoutRef } from 'react';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

export const Sheet = DialogPrimitive.Root;
export const SheetTrigger = DialogPrimitive.Trigger;
export const SheetClose = DialogPrimitive.Close;

export function SheetContent({
    className,
    children,
    side = 'left',
    ...props
}: ComponentPropsWithoutRef<typeof DialogPrimitive.Content> & {
    side?: 'left' | 'right';
}) {
    const { t } = useI18n();

    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-ink/40" />
            <DialogPrimitive.Content
                className={cn(
                    'fixed z-50 flex h-full w-[min(100%,20rem)] flex-col border-mist bg-paper-elevated p-0 shadow-lg',
                    side === 'left' ? 'top-0 left-0 border-r' : 'top-0 right-0 border-l',
                    className,
                )}
                {...props}
            >
                {children}
                <DialogPrimitive.Close className="absolute top-4 right-4 rounded-md p-1 text-ink-soft hover:bg-fog">
                    <X className="h-4 w-4" />
                    <span className="sr-only">{t('console.ui.close')}</span>
                </DialogPrimitive.Close>
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}
