import { LogOut, Settings } from 'lucide-react';
import { Link } from 'react-router';
import type { AuthUser } from '@/auth/AuthContext';
import { Avatar } from '@/components/ui/avatar';
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

type UserMenuProps = {
    user: AuthUser;
    orgId: string;
    onSignOut: () => void;
};

export function UserMenu({ user, orgId, onSignOut }: UserMenuProps) {
    const { t } = useI18n();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="rounded-full p-0 hover:bg-fog"
                    aria-label={t('console.user_menu.aria_label')}
                >
                    <Avatar name={user.name} email={user.email} />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="font-normal">
                    <div className="flex flex-col gap-0.5">
                        <span className="truncate text-sm font-medium text-ink">{user.name}</span>
                        <span className="truncate text-xs text-ink-soft">{user.email}</span>
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link to={`/${orgId}/settings`} className="gap-2">
                        <Settings className="h-4 w-4 shrink-0 opacity-70" />
                        {t('console.user_menu.settings')}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    className="gap-2 text-danger focus:bg-danger/5 focus:text-danger"
                    onSelect={() => {
                        onSignOut();
                    }}
                >
                    <LogOut className="h-4 w-4 shrink-0 opacity-70" />
                    {t('console.shell.sign_out')}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
