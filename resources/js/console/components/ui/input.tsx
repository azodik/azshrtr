import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

export function Input({
    className,
    type = 'text',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            type={type}
            className={cn(
                'flex h-10 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm text-ink outline-none transition-[border-color,box-shadow] placeholder:text-ink-soft/40 focus-visible:border-teal focus-visible:ring-2 focus-visible:ring-teal/25 disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}
