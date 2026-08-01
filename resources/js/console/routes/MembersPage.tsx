import { Users } from 'lucide-react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { BulkActionBar } from '@/components/BulkActionBar';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { EmptyState } from '@/components/EmptyState';
import { ListControls } from '@/components/ListControls';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { Pagination } from '@/components/Pagination';
import { RowActions } from '@/components/RowActions';
import { SelectAllHeader, SelectableListRow } from '@/components/SelectableListRow';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useListState } from '@/hooks/useListState';
import { useSelection } from '@/hooks/useSelection';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPost } from '@/lib/api';
import { downloadFromApi } from '@/lib/download';
import { formatWhen } from '@/lib/format';
import { buildListQuery } from '@/lib/listQuery';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
} from '@/lib/pagination';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type MemberRow = {
    id: string;
    role: string;
    status: string;
    joined_at: string | null;
    created_at: string | null;
    user: { id: number; name: string; email: string } | null;
};

type InvitationRow = {
    id: string;
    email: string;
    role: string;
    expires_at: string | null;
    created_at: string | null;
    invite_url?: string;
};

type MembersResponse = Paginated<MemberRow> & {
    invitations: InvitationRow[];
    can_manage: boolean;
};

export function MembersPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'asc', 25);
    const [members, setMembers] = useState<MemberRow[] | null>(null);
    const [invitations, setInvitations] = useState<InvitationRow[]>([]);
    const [canManage, setCanManage] = useState(false);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta());
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [inviteOpen, setInviteOpen] = useState(false);
    const [email, setEmail] = useState('');
    const [role, setRole] = useState('member');
    const [pending, setPending] = useState(false);
    const [inviteUrl, setInviteUrl] = useState<string | null>(null);
    const [deleteIds, setDeleteIds] = useState<string[] | null>(null);
    const [revokeInviteId, setRevokeInviteId] = useState<string | null>(null);
    const [resendingId, setResendingId] = useState<string | null>(null);
    const [downloading, setDownloading] = useState(false);
    const removableIds = useMemo(
        () => (canManage ? (members ?? []).filter((m) => m.role !== 'owner').map((m) => m.id) : []),
        [canManage, members],
    );
    const selection = useSelection(removableIds);

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        void apiGet<MembersResponse>(
            `/api/v1/organizations/${orgId}/members${buildListQuery(list.applied)}`,
        )
            .then((res) => {
                if (cancelled) return;
                setMembers(res.data ?? []);
                setInvitations(res.invitations ?? []);
                setCanManage(res.can_manage);
                setMeta(metaFromPaginated(res));
                setError(null);
            })
            .catch((err: unknown) => {
                if (cancelled) return;
                const message =
                    err instanceof ApiError ? err.message : t('console.members.load_error');
                setError(message);
                toast.error(t('console.members.load_error'), { description: message });
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [orgId, t, list.applied, list.reloadToken]);

    if (!org || !orgId) return null;

    const onInvite = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        const inviteEmail = email.trim();
        try {
            const res = await apiPost<{ invitation: InvitationRow }>(
                `/api/v1/organizations/${orgId}/members/invite`,
                { email: inviteEmail, role },
            );
            setInviteUrl(res.invitation.invite_url ?? null);
            setEmail('');
            setRole('member');
            toast.success(t('console.members.invite_sent_title'), {
                description: t('console.members.invite_sent_description', { email: inviteEmail }),
            });
            list.refresh();
        } catch (err) {
            toast.error(t('console.members.invite_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.members.invite_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const onResendInvite = async (invite: InvitationRow) => {
        if (!orgId) return;
        setResendingId(invite.id);
        try {
            await apiPost<{ invitation: InvitationRow }>(
                `/api/v1/organizations/${orgId}/members/invite`,
                { email: invite.email, role: invite.role },
            );
            toast.success(t('console.members.invite_resent_title'), {
                description: t('console.members.invite_resent_description', {
                    email: invite.email,
                }),
            });
            list.refresh();
        } catch (err) {
            const message =
                err instanceof ApiError ? err.message : t('console.members.resend_error');
            toast.error(t('console.members.resend_error'), { description: message });
        } finally {
            setResendingId(null);
        }
    };

    const onDownload = async (format: 'csv' | 'json') => {
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/members/export${buildListQuery(
                    { ...list.applied, page: 1 },
                    { format },
                )}`,
                `members.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    const copy = async (url: string) => {
        try {
            await navigator.clipboard.writeText(url);
            toast.success(t('console.members.link_copied_title'), {
                description: t('console.members.link_copied_description'),
            });
        } catch {
            toast.error(t('console.members.link_copy_failed'), {
                description: t('console.members.link_copy_failed_description'),
            });
        }
    };

    const roleLabel = (value: string): string =>
        value === 'owner' || value === 'admin' || value === 'member'
            ? t(`common.role_${value}`)
            : value;

    const hasRows = members !== null && members.length > 0;
    const isEmpty =
        members !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '';
    const noResults = members !== null && members.length === 0 && !isEmpty;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.members.title')}
                description={t('console.members.description')}
                action={
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownload('csv')}
                        >
                            {downloading ? t('common.downloading') : t('common.download_csv')}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownload('json')}
                        >
                            {t('common.download_json')}
                        </Button>
                        {canManage ? (
                            <Button type="button" onClick={() => setInviteOpen(true)}>
                                {t('console.members.invite')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            {error ? (
                <p className="text-sm text-danger" role="alert">
                    {error}
                </p>
            ) : null}

            {members === null && loading ? <ListSkeleton /> : null}

            {members !== null ? (
                <>
                    <ListControls
                        state={list.draft}
                        onQueryChange={(q) => list.patchDraft({ q })}
                        onSortChange={(sort) => list.patchDraft({ sort })}
                        onDirectionChange={(direction) => list.patchDraft({ direction })}
                        onDateChange={(next) => list.patchDraft(next)}
                        onApply={list.applyFilters}
                        onClear={list.clearFilters}
                        searchPlaceholder={t('common.search')}
                        sortOptions={[
                            { value: 'created_at', label: t('common.sort.created_at') },
                            { value: 'joined_at', label: t('common.sort.joined_at') },
                            { value: 'role', label: t('common.sort.role') },
                            { value: 'status', label: t('common.sort.status') },
                        ]}
                    />

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('console.members.team')}</CardTitle>
                            <CardDescription>
                                {t(
                                    meta.total === 1
                                        ? 'console.members.count_one'
                                        : 'console.members.count_other',
                                    { count: meta.total },
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {loading ? <ListSkeleton /> : null}

                            {!loading && noResults ? (
                                <EmptyState
                                    icon={Users}
                                    className="border-0 bg-transparent px-0 py-6"
                                    title={t('console.members.team')}
                                    description={t('console.members.load_error')}
                                    action={
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={list.clearFilters}
                                        >
                                            {t('common.clear_filters')}
                                        </Button>
                                    }
                                />
                            ) : null}

                            {!loading && isEmpty ? (
                                <EmptyState
                                    icon={Users}
                                    className="border-0 bg-transparent px-0 py-6"
                                    title={t('console.members.team')}
                                    description={t('console.members.description')}
                                />
                            ) : null}

                            {canManage ? (
                                <BulkActionBar
                                    count={selection.selectedCount}
                                    onClear={selection.clear}
                                    onDelete={() => setDeleteIds(selection.selectedIds)}
                                />
                            ) : null}

                            {!loading && hasRows ? (
                                <div className="-mx-1 overflow-hidden rounded-[var(--radius-control)]">
                                    {canManage ? (
                                        <SelectAllHeader
                                            allSelected={selection.allSelected}
                                            someSelected={selection.someSelected}
                                            onToggleAll={selection.toggleAll}
                                            className="border-mist/60"
                                        />
                                    ) : null}
                                    <ul className="divide-y divide-mist/60">
                                        {members.map((member) => (
                                            <SelectableListRow
                                                key={member.id}
                                                to={`/${orgId}/members/${member.id}`}
                                                withCheckbox={canManage}
                                                checkboxDisabled={member.role === 'owner'}
                                                selected={selection.isSelected(member.id)}
                                                onToggleSelect={() => selection.toggle(member.id)}
                                                className="px-2 py-3"
                                                actions={
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge variant="secondary">
                                                            {roleLabel(member.role)}
                                                        </Badge>
                                                        <RowActions
                                                            actions={[
                                                                {
                                                                    key: 'view',
                                                                    label: t('common.view'),
                                                                    to: `/${orgId}/members/${member.id}`,
                                                                },
                                                                ...(canManage &&
                                                                member.role !== 'owner'
                                                                    ? [
                                                                          {
                                                                              key: 'remove',
                                                                              label: t(
                                                                                  'console.members.remove',
                                                                              ),
                                                                              destructive: true,
                                                                              onSelect: () =>
                                                                                  setDeleteIds([
                                                                                      member.id,
                                                                                  ]),
                                                                          },
                                                                      ]
                                                                    : []),
                                                            ]}
                                                        />
                                                    </div>
                                                }
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {member.user?.name ?? t('common.unknown')}
                                                    </p>
                                                    <p className="truncate text-xs text-ink-soft">
                                                        {member.user?.email}
                                                    </p>
                                                </div>
                                            </SelectableListRow>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}

                            {!loading && hasRows ? (
                                <Pagination
                                    meta={meta}
                                    onPageChange={list.setPage}
                                    onPerPageChange={list.setPerPage}
                                />
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('console.members.pending_title')}</CardTitle>
                            <CardDescription>
                                {t('console.members.pending_description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {invitations.length === 0 ? (
                                <EmptyState
                                    icon={Users}
                                    className="border-0 bg-transparent px-0 py-6"
                                    title={t('console.members.no_invites_title')}
                                    description={t(
                                        canManage
                                            ? 'console.members.no_invites_can_manage'
                                            : 'console.members.no_invites_cannot_manage',
                                    )}
                                />
                            ) : (
                                <ul className="space-y-3">
                                    {invitations.map((invite) => (
                                        <li
                                            key={invite.id}
                                            className="flex flex-col gap-2 rounded-md border border-mist/60 p-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {invite.email}
                                                </p>
                                                <p className="text-xs text-ink-soft">
                                                    {t('console.members.invite_meta', {
                                                        role: roleLabel(invite.role),
                                                        when: invite.expires_at
                                                            ? formatWhen(invite.expires_at)
                                                            : t('common.em_dash'),
                                                    })}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {canManage ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={resendingId === invite.id}
                                                        onClick={() => void onResendInvite(invite)}
                                                    >
                                                        {resendingId === invite.id
                                                            ? t('console.members.resending')
                                                            : t('console.members.resend')}
                                                    </Button>
                                                ) : null}
                                                {canManage && invite.invite_url ? (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => {
                                                            if (invite.invite_url) {
                                                                void copy(invite.invite_url);
                                                            }
                                                        }}
                                                    >
                                                        {t('console.members.copy_link')}
                                                    </Button>
                                                ) : null}
                                                {canManage ? (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setRevokeInviteId(invite.id)}
                                                    >
                                                        {t('console.members.revoke')}
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </>
            ) : null}

            <Dialog
                open={inviteOpen}
                onOpenChange={(open) => {
                    setInviteOpen(open);
                    if (!open) {
                        setInviteUrl(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('console.members.invite_dialog_title')}</DialogTitle>
                        <DialogDescription>
                            {t('console.members.invite_dialog_description')}
                        </DialogDescription>
                    </DialogHeader>
                    {inviteUrl ? (
                        <div className="space-y-3">
                            <p className="text-sm text-success">
                                {t('console.members.invite_sent')}
                            </p>
                            <p className="text-xs text-ink-soft">
                                {t('console.members.invite_link_optional')}
                            </p>
                            <Input readOnly value={inviteUrl} />
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => void copy(inviteUrl)}
                                >
                                    {t('console.members.copy_link')}
                                </Button>
                                <Button type="button" onClick={() => setInviteOpen(false)}>
                                    {t('common.done')}
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <form onSubmit={(e) => void onInvite(e)} className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="invite-email">
                                    {t('console.members.invite_email')}
                                </Label>
                                <Input
                                    id="invite-email"
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder={t('console.members.invite_email_placeholder')}
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label>{t('console.members.invite_role')}</Label>
                                <Select value={role} onValueChange={setRole}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">
                                            {t('common.role_admin')}
                                        </SelectItem>
                                        <SelectItem value="member">
                                            {t('common.role_member')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setInviteOpen(false)}
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button type="submit" disabled={pending}>
                                    {pending
                                        ? t('console.members.sending')
                                        : t('console.members.send_invite')}
                                </Button>
                            </div>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDelete
                open={deleteIds !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteIds(null);
                }}
                count={deleteIds?.length}
                description={t('console.members.remove_confirm')}
                confirmLabel={t('console.members.remove')}
                onConfirm={async () => {
                    if (!deleteIds?.length || !orgId) return;
                    try {
                        if (deleteIds.length === 1) {
                            await apiDelete(
                                `/api/v1/organizations/${orgId}/members/${deleteIds[0]}`,
                            );
                        } else {
                            await apiPost(`/api/v1/organizations/${orgId}/members/bulk-delete`, {
                                ids: deleteIds,
                            });
                        }
                        toast.success(t('console.members.remove_success_title'), {
                            description: t('console.members.remove_success_description'),
                        });
                        selection.clear();
                        list.refresh();
                    } catch (err) {
                        const message =
                            err instanceof ApiError
                                ? err.message
                                : t('console.members.remove_error');
                        toast.error(t('console.members.remove_error'), {
                            description: message,
                        });
                        throw err;
                    }
                }}
            />

            <ConfirmDelete
                open={revokeInviteId !== null}
                onOpenChange={(open) => {
                    if (!open) setRevokeInviteId(null);
                }}
                confirmLabel={t('console.members.revoke')}
                onConfirm={async () => {
                    if (!revokeInviteId) return;
                    try {
                        await apiDelete(
                            `/api/v1/organizations/${orgId}/invitations/${revokeInviteId}`,
                        );
                        toast.success(t('console.members.revoke_success_title'), {
                            description: t('console.members.revoke_success_description'),
                        });
                        list.refresh();
                    } catch (err) {
                        const message =
                            err instanceof ApiError
                                ? err.message
                                : t('console.members.revoke_error');
                        toast.error(t('console.members.revoke_error'), {
                            description: message,
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
