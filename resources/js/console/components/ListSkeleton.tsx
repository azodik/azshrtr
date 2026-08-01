import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/cn';

type ListSkeletonProps = {
    rows?: number;
    withCheckbox?: boolean;
    className?: string;
};

export function ListSkeleton({ rows = 6, withCheckbox = true, className }: ListSkeletonProps) {
    return (
        <div className={cn('overflow-hidden console-panel', className)} aria-hidden>
            {withCheckbox ? (
                <div className="flex items-center gap-3 border-b border-mist/60 px-4 py-2.5">
                    <Skeleton className="size-4 shrink-0" />
                    <Skeleton className="h-3 w-20" />
                </div>
            ) : null}
            <ul className="divide-y divide-mist/60">
                {Array.from({ length: rows }, (_, index) => (
                    <li
                        key={index}
                        className="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div className="flex min-w-0 flex-1 items-start gap-3">
                            {withCheckbox ? <Skeleton className="mt-0.5 size-4 shrink-0" /> : null}
                            <div className="min-w-0 flex-1 space-y-2">
                                <Skeleton className="h-4 w-36 max-w-full" />
                                <Skeleton className="h-3 w-64 max-w-full" />
                                <Skeleton className="h-3 w-24 max-w-[40%]" />
                            </div>
                        </div>
                        <Skeleton className="size-9 shrink-0 self-end sm:self-center" />
                    </li>
                ))}
            </ul>
        </div>
    );
}

type TableSkeletonProps = {
    rows?: number;
    columns?: number;
    className?: string;
};

export function TableSkeleton({ rows = 8, columns = 6, className }: TableSkeletonProps) {
    return (
        <div className={cn('overflow-hidden console-panel', className)} aria-hidden>
            <div className="border-b border-mist/60 px-3 py-2">
                <div className="flex gap-4">
                    {Array.from({ length: columns }, (_, index) => (
                        <Skeleton key={index} className="h-3 w-16" />
                    ))}
                </div>
            </div>
            <ul className="divide-y divide-mist/50">
                {Array.from({ length: rows }, (_, row) => (
                    <li key={row} className="flex gap-4 px-3 py-3">
                        {Array.from({ length: columns }, (_, col) => (
                            <Skeleton
                                key={col}
                                className={cn('h-3', col === 2 ? 'w-40 flex-1' : 'w-14')}
                            />
                        ))}
                    </li>
                ))}
            </ul>
        </div>
    );
}
