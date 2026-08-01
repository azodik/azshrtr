import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { Check } from 'lucide-react';
import type { ComponentPropsWithoutRef } from 'react';
import { cn } from '@/lib/cn';

export function Checkbox({
    className,
    ...props
}: ComponentPropsWithoutRef<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            className={cn(
                'peer h-4 w-4 shrink-0 rounded border border-mist bg-paper data-[state=checked]:border-teal data-[state=checked]:bg-teal data-[state=checked]:text-paper-elevated focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator className="flex items-center justify-center text-current">
                <Check className="h-3 w-3" />
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}
