import { Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';

type BulkActionBarProps = {
    count: number;
    onClear: () => void;
    onDelete: () => void;
};

export function BulkActionBar({ count, onClear, onDelete }: BulkActionBarProps) {
    const { t } = useI18n();

    if (count === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border border-teal/30 bg-teal/10 px-4 py-3">
            <p className="text-sm font-medium text-ink">{t('common.n_selected', { count })}</p>
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="ghost" size="sm" onClick={onClear}>
                    <X className="size-3.5" />
                    {t('common.clear_selection')}
                </Button>
                <Button type="button" variant="danger" size="sm" onClick={onDelete}>
                    <Trash2 className="size-3.5" />
                    {t('common.bulk.delete')}
                </Button>
            </div>
        </div>
    );
}
