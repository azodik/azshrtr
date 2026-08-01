import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/cn';

function initialsFrom(name: string, email: string): string {
    const trimmed = name.trim();
    if (trimmed.length > 0) {
        const parts = trimmed.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return trimmed.slice(0, 2).toUpperCase();
    }
    const local = email.split('@')[0] ?? email;
    return local.slice(0, 2).toUpperCase();
}

type AvatarProps = HTMLAttributes<HTMLSpanElement> & {
    name: string;
    email: string;
    size?: 'sm' | 'md';
};

export function Avatar({ name, email, size = 'md', className, ...props }: AvatarProps) {
    const initials = initialsFrom(name, email);

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-full bg-teal/15 font-display font-semibold tracking-tight text-teal-deep',
                size === 'sm' ? 'h-7 w-7 text-[0.65rem]' : 'h-8 w-8 text-xs',
                className,
            )}
            aria-hidden={props['aria-label'] ? undefined : true}
            {...props}
        >
            {initials}
        </span>
    );
}
