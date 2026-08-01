import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import type { AuthOrganization } from '@/auth/AuthContext';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

type OrgSwitcherProps = {
    organizations: AuthOrganization[];
    active: AuthOrganization;
    onSelect: (org: AuthOrganization) => void;
    onCreate: () => void;
    className?: string;
};

export function OrgSwitcher({
    organizations,
    active,
    onSelect,
    onCreate,
    className,
}: OrgSwitcherProps) {
    const { t } = useI18n();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className={cn(
                        'h-9 max-w-[8.5rem] justify-between gap-1 px-2 font-medium sm:max-w-[14rem] sm:gap-1.5 sm:px-2.5',
                        className,
                    )}
                    aria-label={t('console.org_switcher.aria_label')}
                >
                    <span className="truncate">{active.name}</span>
                    <ChevronsUpDown className="h-3.5 w-3.5 shrink-0 opacity-60" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
                <DropdownMenuLabel>{t('console.org_switcher.label')}</DropdownMenuLabel>
                {organizations.map((org) => {
                    const selected = org.id === active.id;
                    return (
                        <DropdownMenuItem
                            key={org.id}
                            onSelect={() => {
                                if (!selected) {
                                    onSelect(org);
                                }
                            }}
                            className="gap-2"
                        >
                            <Check
                                className={cn(
                                    'h-4 w-4 shrink-0',
                                    selected ? 'opacity-100' : 'opacity-0',
                                )}
                            />
                            <span className="truncate">{org.name}</span>
                        </DropdownMenuItem>
                    );
                })}
                <DropdownMenuSeparator />
                <DropdownMenuItem onSelect={onCreate} className="gap-2 text-teal">
                    <Plus className="h-4 w-4 shrink-0" />
                    {t('console.org_switcher.new')}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
