import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

type EmptyStateProps = {
    title: string;
    description: string;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({ title, description, icon: Icon, action, className }: EmptyStateProps) {
    return (
        <div
            className={cn(
                'console-panel flex flex-col items-center gap-4 border-dashed px-6 py-12 text-center',
                className,
            )}
        >
            {Icon ? (
                <div
                    className="flex h-11 w-11 items-center justify-center rounded-[var(--radius-control)] bg-teal/10 text-teal-deep"
                    aria-hidden="true"
                >
                    <Icon className="h-5 w-5" strokeWidth={1.75} />
                </div>
            ) : null}
            <div className="space-y-1.5">
                <h2 className="font-display text-lg font-semibold tracking-tight text-ink">
                    {title}
                </h2>
                <p className="mx-auto max-w-md text-sm leading-relaxed text-ink-soft">
                    {description}
                </p>
            </div>
            {action ? <div className="pt-1">{action}</div> : null}
        </div>
    );
}
