import type { KeyboardEvent, MouseEvent, ReactNode } from 'react';
import { useNavigate } from 'react-router';
import { Checkbox } from '@/components/ui/checkbox';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

type SelectableListRowProps = {
    to: string;
    selected?: boolean;
    onToggleSelect?: () => void;
    /**
     * When true, always reserve the checkbox column so row content stays aligned.
     * Use `checkboxDisabled` for rows that cannot be selected (e.g. revoked keys).
     */
    withCheckbox?: boolean;
    checkboxDisabled?: boolean;
    children: ReactNode;
    actions?: ReactNode;
    className?: string;
};

function stopRowNav(event: MouseEvent | KeyboardEvent) {
    event.stopPropagation();
}

export function SelectableListRow({
    to,
    selected = false,
    onToggleSelect,
    withCheckbox = false,
    checkboxDisabled = false,
    children,
    actions,
    className,
}: SelectableListRowProps) {
    const { t } = useI18n();
    const navigate = useNavigate();

    const go = () => {
        void navigate(to);
    };

    return (
        <li
            role="link"
            tabIndex={0}
            onClick={go}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    go();
                }
            }}
            className={cn(
                'flex cursor-pointer flex-col gap-3 px-4 py-4 outline-none transition-colors hover:bg-fog/70 focus-visible:bg-fog/70 sm:flex-row sm:items-center sm:justify-between',
                selected && 'bg-teal/5',
                className,
            )}
        >
            <div className="flex min-w-0 flex-1 items-start gap-3">
                {withCheckbox ? (
                    <div
                        className="flex size-4 shrink-0 items-center justify-center pt-0.5"
                        onClick={stopRowNav}
                        onKeyDown={stopRowNav}
                    >
                        <Checkbox
                            checked={selected}
                            disabled={checkboxDisabled}
                            onCheckedChange={() => {
                                if (!checkboxDisabled) {
                                    onToggleSelect?.();
                                }
                            }}
                            aria-label={t('common.select_row')}
                            onClick={stopRowNav}
                        />
                    </div>
                ) : null}
                <div className="min-w-0 flex-1">{children}</div>
            </div>
            {actions ? (
                <div
                    className="flex shrink-0 items-center gap-2 self-end sm:self-center"
                    onClick={stopRowNav}
                >
                    {actions}
                </div>
            ) : null}
        </li>
    );
}

type SelectAllHeaderProps = {
    allSelected: boolean;
    someSelected: boolean;
    onToggleAll: () => void;
    className?: string;
};

export function SelectAllHeader({
    allSelected,
    someSelected,
    onToggleAll,
    className,
}: SelectAllHeaderProps) {
    const { t } = useI18n();

    return (
        <div
            className={cn(
                'flex items-center gap-3 border-b border-mist/60 px-4 py-2.5 text-sm text-ink-soft',
                className,
            )}
        >
            <div className="flex size-4 shrink-0 items-center justify-center">
                <Checkbox
                    checked={allSelected ? true : someSelected ? 'indeterminate' : false}
                    onCheckedChange={() => onToggleAll()}
                    aria-label={t('common.select_all')}
                />
            </div>
            <span>{t('common.select_all')}</span>
        </div>
    );
}
