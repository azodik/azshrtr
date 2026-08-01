import {
    Activity,
    BarChart3,
    CreditCard,
    FileKey2,
    FileText,
    KeyRound,
    Link2,
    Menu,
    QrCode,
    ScrollText,
    Settings,
    Share2,
    Users,
} from 'lucide-react';
import { type FormEvent, type ReactNode, useState } from 'react';
import { Link, Navigate, NavLink, Outlet, useLocation, useNavigate } from 'react-router';
import { type AuthOrganization, useAuth } from '@/auth/AuthContext';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { OrgSwitcher } from '@/components/OrgSwitcher';
import { ThemeSwitcher } from '@/components/ThemeSwitcher';
import { UserMenu } from '@/components/UserMenu';
import { Avatar } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { useI18n } from '@/i18n/useI18n';
import { writeLastOrgId } from '@/lib/activeOrg';
import { ApiError, apiPost } from '@/lib/api';
import { cn } from '@/lib/cn';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type NavItem = {
    to: string;
    labelKey: string;
    end?: boolean;
    icon: ReactNode;
};

type NavGroup = {
    labelKey: string;
    items: NavItem[];
};

const navGroups: NavGroup[] = [
    {
        labelKey: 'console.nav.group.workspace',
        items: [
            {
                to: '',
                labelKey: 'console.nav.overview',
                end: true,
                icon: <Activity className="size-4" />,
            },
            { to: 'links', labelKey: 'console.nav.links', icon: <Link2 className="size-4" /> },
            {
                to: 'analytics',
                labelKey: 'console.nav.analytics',
                icon: <BarChart3 className="size-4" />,
            },
            { to: 'qr', labelKey: 'console.nav.qr', icon: <QrCode className="size-4" /> },
            { to: 'domains', labelKey: 'console.nav.domains', icon: <Share2 className="size-4" /> },
        ],
    },
    {
        labelKey: 'console.nav.group.developers',
        items: [
            {
                to: 'api-keys',
                labelKey: 'console.nav.api_keys',
                icon: <KeyRound className="size-4" />,
            },
            {
                to: 'api-logs',
                labelKey: 'console.nav.api_logs',
                icon: <ScrollText className="size-4" />,
            },
            { to: 'audit', labelKey: 'console.nav.audit', icon: <FileText className="size-4" /> },
            {
                to: 'import-export',
                labelKey: 'console.nav.import_export',
                icon: <FileKey2 className="size-4" />,
            },
        ],
    },
    {
        labelKey: 'console.nav.group.account',
        items: [
            { to: 'members', labelKey: 'console.nav.members', icon: <Users className="size-4" /> },
            {
                to: 'billing',
                labelKey: 'console.nav.billing',
                icon: <CreditCard className="size-4" />,
            },
            {
                to: 'settings',
                labelKey: 'console.nav.settings',
                icon: <Settings className="size-4" />,
            },
        ],
    },
];

function suffixAfterOrg(pathname: string, orgId: string): string {
    const prefix = `/${orgId}`;
    if (!pathname.startsWith(prefix)) {
        return '';
    }
    return pathname.slice(prefix.length).replace(/^\//, '');
}

function NavItems({
    orgId,
    onNavigate,
    className,
}: {
    orgId: string;
    onNavigate?: () => void;
    className?: string;
}) {
    const { t } = useI18n();

    return (
        <nav
            className={cn('flex flex-col gap-5', className)}
            aria-label={t('console.nav.aria_label')}
        >
            {navGroups.map((group) => (
                <div key={group.labelKey} className="space-y-1">
                    <p className="px-3 text-[0.65rem] font-semibold tracking-[0.14em] text-ink-soft/70 uppercase">
                        {t(group.labelKey)}
                    </p>
                    <div className="flex flex-col gap-0.5">
                        {group.items.map((item) => (
                            <NavLink
                                key={item.to}
                                to={item.to === '' ? `/${orgId}` : `/${orgId}/${item.to}`}
                                end={item.end ?? false}
                                onClick={onNavigate}
                                className={({ isActive }) =>
                                    cn('console-nav-link', isActive && 'is-active')
                                }
                            >
                                <span className="opacity-70">{item.icon}</span>
                                {t(item.labelKey)}
                            </NavLink>
                        ))}
                    </div>
                </div>
            ))}
        </nav>
    );
}

export function ConsoleShell() {
    const { t } = useI18n();
    const { user, logout, setUser, refresh } = useAuth();
    const org = useActiveOrg();
    const navigate = useNavigate();
    const location = useLocation();
    const [creating, setCreating] = useState(false);
    const [orgName, setOrgName] = useState('');
    const [pending, setPending] = useState(false);
    const [mobileNavOpen, setMobileNavOpen] = useState(false);

    if (!org || !user) {
        return <Navigate to="/login" replace />;
    }

    const switchOrg = (next: AuthOrganization) => {
        writeLastOrgId(next.id);
        const suffix = suffixAfterOrg(location.pathname, org.id);
        void navigate(suffix ? `/${next.id}/${suffix}` : `/${next.id}`);
    };

    const onCreateOrg = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        try {
            const data = await apiPost<{
                organization: AuthOrganization;
                organizations: AuthOrganization[];
            }>('/api/v1/organizations', { name: orgName.trim() });
            setUser({ ...user, organizations: data.organizations });
            writeLastOrgId(data.organization.id);
            setCreating(false);
            setOrgName('');
            toast.success(t('console.shell.create_org.success_title'), {
                description: t('console.shell.create_org.success_description'),
            });
            void navigate(`/${data.organization.id}`);
            void refresh();
        } catch (err) {
            toast.error(t('console.shell.create_org.error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.shell.create_org.error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    return (
        <div className="console-shell az-shell-motion">
            <header className="sticky top-0 z-40 border-b border-mist/60 bg-paper-elevated/95 backdrop-blur-md">
                <div className="console-frame flex h-14 items-center justify-between gap-2 sm:gap-3">
                    <div className="flex min-w-0 flex-1 items-center gap-1.5 sm:gap-3">
                        <Sheet open={mobileNavOpen} onOpenChange={setMobileNavOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="shrink-0 lg:hidden"
                                    aria-label={t('console.shell.open_navigation')}
                                >
                                    <Menu className="h-4 w-4" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="flex w-[min(100%,20rem)] flex-col pt-12"
                            >
                                <div className="flex items-center gap-3 px-4 pb-4">
                                    <Avatar name={user.name} email={user.email} size="sm" />
                                    <div className="min-w-0">
                                        <p className="truncate font-display text-sm font-semibold">
                                            {user.name}
                                        </p>
                                        <p className="truncate text-xs text-ink-soft">{org.name}</p>
                                    </div>
                                </div>
                                <Separator />
                                <div className="flex-1 overflow-y-auto p-3">
                                    <NavItems
                                        orgId={org.id}
                                        onNavigate={() => setMobileNavOpen(false)}
                                    />
                                </div>
                            </SheetContent>
                        </Sheet>

                        <Link to={`/${org.id}`} className="flex shrink-0 items-center gap-2">
                            <img
                                src="/images/mark.svg?v=2"
                                alt=""
                                width={28}
                                height={28}
                                className="h-7 w-7"
                            />
                            <span className="hidden font-display text-lg font-semibold tracking-tight md:inline">
                                {t('common.brand')}
                            </span>
                        </Link>
                        <Separator orientation="vertical" className="hidden h-6 md:block" />
                        <OrgSwitcher
                            organizations={user.organizations}
                            active={org}
                            onSelect={switchOrg}
                            onCreate={() => setCreating(true)}
                            className="min-w-0 max-w-[9rem] sm:max-w-[14rem]"
                        />
                    </div>
                    <div className="flex shrink-0 items-center gap-1 sm:gap-2">
                        <ThemeSwitcher className="px-2 sm:px-2.5" />
                        <LanguageSwitcher className="px-2 sm:px-2.5" />
                        <UserMenu
                            user={user}
                            orgId={org.id}
                            onSignOut={() => {
                                void logout();
                            }}
                        />
                    </div>
                </div>
            </header>

            <div className="console-frame grid gap-6 py-4 sm:gap-8 sm:py-6 lg:grid-cols-[220px_1fr] lg:py-8">
                <aside className="hidden lg:block">
                    <NavItems orgId={org.id} />
                </aside>

                <main className="min-w-0 overflow-x-hidden">
                    <Outlet />
                </main>
            </div>

            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('console.shell.create_org.title')}</DialogTitle>
                        <DialogDescription>
                            {t('console.shell.create_org.description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={(e) => void onCreateOrg(e)} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="org-name">
                                {t('console.shell.create_org.name_label')}
                            </Label>
                            <Input
                                id="org-name"
                                required
                                value={orgName}
                                onChange={(e) => setOrgName(e.target.value)}
                                placeholder={t('console.shell.create_org.name_placeholder')}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setCreating(false);
                                }}
                            >
                                {t('common.cancel')}
                            </Button>
                            <Button type="submit" disabled={pending}>
                                {pending
                                    ? t('console.shell.create_org.submitting')
                                    : t('console.shell.create_org.submit')}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
