import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

type PageHeaderProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    badge?: ReactNode;
    className?: string;
};

export function PageHeader({ title, description, action, badge, className }: PageHeaderProps) {
    return (
        <div
            className={cn(
                'flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between',
                className,
            )}
        >
            <div className="min-w-0 space-y-1">
                <div className="flex flex-wrap items-center gap-2.5">
                    <h1 className="font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                        {title}
                    </h1>
                    {badge}
                </div>
                {description ? (
                    <p className="max-w-2xl text-sm leading-relaxed text-ink-soft">{description}</p>
                ) : null}
            </div>
            {action ? (
                <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:flex-wrap sm:items-center [&_button]:w-full sm:[&_button]:w-auto">
                    {action}
                </div>
            ) : null}
        </div>
    );
}
