import { MoreVertical } from 'lucide-react';
import { Link } from 'react-router';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

export type RowAction = {
    key: string;
    label: string;
    onSelect?: () => void;
    to?: string;
    destructive?: boolean;
    disabled?: boolean;
};

type RowActionsProps = {
    actions: RowAction[];
    label?: string;
};

export function RowActions({ actions, label }: RowActionsProps) {
    const { t } = useI18n();

    if (actions.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="shrink-0"
                    aria-label={label ?? t('common.actions')}
                >
                    <MoreVertical className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-[10rem]">
                {actions.map((action) => {
                    const className = cn(
                        action.destructive && 'text-danger focus:bg-danger/5 focus:text-danger',
                    );

                    if (action.to) {
                        return (
                            <DropdownMenuItem key={action.key} asChild disabled={action.disabled}>
                                <Link to={action.to} className={className}>
                                    {action.label}
                                </Link>
                            </DropdownMenuItem>
                        );
                    }

                    return (
                        <DropdownMenuItem
                            key={action.key}
                            className={className}
                            disabled={action.disabled}
                            onSelect={() => action.onSelect?.()}
                        >
                            {action.label}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
