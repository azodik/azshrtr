import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';
import { type PaginationMeta, PER_PAGE_OPTIONS } from '@/lib/pagination';

type PaginationProps = {
    meta: PaginationMeta;
    onPageChange: (page: number) => void;
    onPerPageChange?: (perPage: number) => void;
    className?: string;
};

export function Pagination({ meta, onPageChange, onPerPageChange, className }: PaginationProps) {
    const { t } = useI18n();
    const { current_page: page, last_page: lastPage, total, from, to, per_page: perPage } = meta;

    if (total === 0) {
        return null;
    }

    const canPrev = page > 1;
    const canNext = page < lastPage;

    return (
        <div
            className={cn(
                'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between',
                className,
            )}
        >
            <p className="text-sm text-ink-soft">
                {t('common.pagination.showing', {
                    from: from ?? 0,
                    to: to ?? 0,
                    total,
                })}
            </p>

            <div className="flex flex-wrap items-center gap-3">
                {onPerPageChange ? (
                    <label className="flex items-center gap-2 text-sm text-ink-soft">
                        <span>{t('common.pagination.per_page')}</span>
                        <select
                            value={perPage}
                            onChange={(e) => onPerPageChange(Number(e.target.value))}
                            className="rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-2 py-1.5 text-sm text-ink outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                        >
                            {PER_PAGE_OPTIONS.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>
                    </label>
                ) : null}

                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!canPrev}
                        onClick={() => onPageChange(page - 1)}
                        aria-label={t('common.pagination.previous')}
                    >
                        <ChevronLeft className="size-4" />
                        <span className="hidden sm:inline">{t('common.pagination.previous')}</span>
                    </Button>
                    <span className="min-w-[5.5rem] px-2 text-center text-sm tabular-nums text-ink-soft">
                        {t('common.pagination.page_of', { page, pages: lastPage })}
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={!canNext}
                        onClick={() => onPageChange(page + 1)}
                        aria-label={t('common.pagination.next')}
                    >
                        <span className="hidden sm:inline">{t('common.pagination.next')}</span>
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
