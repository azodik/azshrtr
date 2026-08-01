import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 rounded-[var(--radius-control)] text-sm font-semibold tracking-wide transition-[background-color,color,transform] duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal/40 focus-visible:ring-offset-2 focus-visible:ring-offset-paper active:translate-y-px disabled:pointer-events-none disabled:opacity-55',
    {
        variants: {
            variant: {
                default: 'bg-teal text-paper-elevated hover:bg-teal-bright',
                secondary: 'border border-mist bg-paper-elevated text-ink hover:bg-fog',
                outline: 'border border-mist bg-transparent text-ink hover:bg-fog',
                ghost: 'text-ink-soft hover:bg-fog hover:text-ink',
                danger: 'bg-danger text-paper-elevated hover:opacity-90',
                link: 'font-medium text-teal underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-10 px-4',
                sm: 'h-8 px-3 text-xs',
                lg: 'h-11 px-6',
                icon: 'h-9 w-9',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants> & {
        asChild?: boolean;
        /** Disables the control and marks it busy for assistive tech. */
        loading?: boolean;
    };

export function Button({
    className,
    variant,
    size,
    asChild = false,
    loading = false,
    disabled,
    ...props
}: ButtonProps) {
    const Comp = asChild ? Slot : 'button';
    return (
        <Comp
            className={cn(buttonVariants({ variant, size, className }))}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            {...props}
        />
    );
}
