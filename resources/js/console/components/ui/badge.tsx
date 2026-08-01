import { cva, type VariantProps } from 'class-variance-authority';
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

const badgeVariants = cva(
    'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-teal/15 text-teal-deep',
                secondary: 'border-mist bg-fog text-ink-soft',
                outline: 'border-mist text-ink-soft',
                success: 'border-transparent bg-success/15 text-success',
                danger: 'border-transparent bg-danger/15 text-danger',
            },
        },
        defaultVariants: { variant: 'default' },
    },
);

export function Badge({
    className,
    variant,
    ...props
}: HTMLAttributes<HTMLDivElement> & VariantProps<typeof badgeVariants>) {
    return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}
