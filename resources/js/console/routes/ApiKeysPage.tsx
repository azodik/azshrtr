import { KeyRound } from 'lucide-react';
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useListState } from '@/hooks/useListState';
import { useSelection } from '@/hooks/useSelection';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPost } from '@/lib/api';
import { downloadFromApi } from '@/lib/download';
import { copyText, formatWhen } from '@/lib/format';
import { buildListQuery } from '@/lib/listQuery';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
} from '@/lib/pagination';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type KeyRow = {
    id: string;
    name: string;
    prefix: string;
    last_four: string;
    last_used_at: string | null;
    revoked_at: string | null;
    created_at: string;
};

export function ApiKeysPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'desc', 25);
    const [keys, setKeys] = useState<KeyRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta());
    const [loading, setLoading] = useState(true);
    const [name, setName] = useState('');
    const [plain, setPlain] = useState<string | null>(null);
    const [pending, setPending] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [copied, setCopied] = useState(false);
    const [revokeIds, setRevokeIds] = useState<string[] | null>(null);
    const [downloading, setDownloading] = useState(false);
    const [canManage, setCanManage] = useState(false);
    const rowIds = useMemo(
        () => (canManage ? (keys ?? []).filter((k) => !k.revoked_at).map((k) => k.id) : []),
        [canManage, keys],
    );
    const selection = useSelection(rowIds);

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        void apiGet<Paginated<KeyRow> & { can_manage?: boolean }>(
            `/api/v1/organizations/${orgId}/api-keys${buildListQuery(list.applied)}`,
        )
            .then((data) => {
                if (cancelled) return;
                setKeys(data.data ?? []);
                setMeta(metaFromPaginated(data));
                setCanManage(data.can_manage === true);
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [orgId, list.applied, list.reloadToken]);

    const onCreate = async (event: FormEvent) => {
        event.preventDefault();
        if (!orgId || !canManage) return;
        setPending(true);
        try {
            const data = await apiPost<{ plain_text: string }>(
                `/api/v1/organizations/${orgId}/api-keys`,
                { name },
            );
            setPlain(data.plain_text);
            setName('');
            setShowForm(false);
            list.setPage(1);
            list.refresh();
            toast.success(t('console.api_keys.create_success_title'), {
                description: t('console.api_keys.create_success_description'),
            });
        } catch (err) {
            toast.error(t('console.api_keys.create_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.api_keys.create_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const onDownload = async (format: 'csv' | 'json') => {
        if (!orgId) return;
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/api-keys/export${buildListQuery(
                    { ...list.applied, page: 1 },
                    { format },
                )}`,
                `api-keys.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    if (!org || !orgId) return null;

    const hasRows = keys !== null && keys.length > 0;
    const isEmpty =
        keys !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '';
    const noResults = keys !== null && keys.length === 0 && !isEmpty;

    const form = (
        <form onSubmit={onCreate} className="console-panel space-y-3 p-5">
            <div className="space-y-1.5">
                <Label htmlFor="api-key-name">{t('console.api_keys.key_name')}</Label>
                <Input
                    id="api-key-name"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder={t('console.api_keys.key_name_placeholder')}
                />
            </div>
            <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={pending}>
                    {pending ? t('console.api_keys.creating') : t('console.api_keys.create')}
                </Button>
                {!isEmpty ? (
                    <Button type="button" variant="outline" onClick={() => setShowForm(false)}>
                        {t('common.cancel')}
                    </Button>
                ) : null}
            </div>
        </form>
    );

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.api_keys.title')}
                description={t('console.api_keys.description')}
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
                        {canManage && !isEmpty && !showForm ? (
                            <Button type="button" onClick={() => setShowForm(true)}>
                                {t('console.api_keys.new')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            {plain ? (
                <div className="space-y-2 rounded-[var(--radius-control)] border border-teal/30 bg-teal/5 p-4">
                    <p className="text-sm font-medium text-ink">
                        {t('console.api_keys.secret_notice')}
                    </p>
                    <code className="block break-all rounded-md bg-paper px-3 py-2 text-sm">
                        {plain}
                    </code>
                    <button
                        type="button"
                        onClick={() =>
                            void copyText(plain).then((ok) => {
                                if (ok) {
                                    setCopied(true);
                                    toast.success(t('common.copied'), {
                                        description: t('common.copied_description'),
                                    });
                                    window.setTimeout(() => setCopied(false), 1600);
                                } else {
                                    toast.error(t('common.copy_failed'), {
                                        description: t('common.copy_failed_description'),
                                    });
                                }
                            })
                        }
                        className="text-sm font-medium text-teal hover:underline"
                    >
                        {copied
                            ? t('console.api_keys.copied')
                            : t('console.api_keys.copy_to_clipboard')}
                    </button>
                </div>
            ) : null}

            {keys === null && loading ? <ListSkeleton /> : null}

            {isEmpty ? (
                <>
                    <EmptyState
                        icon={KeyRound}
                        title={t('console.api_keys.empty_title')}
                        description={t('console.api_keys.empty_description')}
                    />
                    {canManage ? form : null}
                </>
            ) : null}

            {canManage && !isEmpty && showForm ? form : null}

            {!isEmpty && keys !== null ? (
                <>
                    <ListControls
                        state={list.draft}
                        onQueryChange={(q) => list.patchDraft({ q })}
                        onSortChange={(sort) => list.patchDraft({ sort })}
                        onDirectionChange={(direction) => list.patchDraft({ direction })}
                        onDateChange={(next) => list.patchDraft(next)}
                        onApply={list.applyFilters}
                        onClear={list.clearFilters}
                        searchPlaceholder={t('console.api_keys.search_placeholder')}
                        sortOptions={[
                            { value: 'created_at', label: t('common.sort.created_at') },
                            { value: 'name', label: t('common.sort.name') },
                            { value: 'last_used_at', label: t('common.sort.last_used_at') },
                            { value: 'revoked_at', label: t('common.sort.revoked_at') },
                        ]}
                    />

                    {loading ? <ListSkeleton /> : null}

                    {!loading && noResults ? (
                        <EmptyState
                            icon={KeyRound}
                            title={t('console.api_keys.empty_title')}
                            description={t('console.api_keys.empty_description')}
                            action={
                                <Button type="button" variant="outline" onClick={list.clearFilters}>
                                    {t('common.clear_filters')}
                                </Button>
                            }
                        />
                    ) : null}

                    {canManage ? (
                        <BulkActionBar
                            count={selection.selectedCount}
                            onClear={selection.clear}
                            onDelete={() => setRevokeIds(selection.selectedIds)}
                        />
                    ) : null}

                    {!loading && hasRows ? (
                        <>
                            <div className="overflow-hidden console-panel">
                                {canManage ? (
                                    <SelectAllHeader
                                        allSelected={selection.allSelected}
                                        someSelected={selection.someSelected}
                                        onToggleAll={selection.toggleAll}
                                    />
                                ) : null}
                                <ul className="divide-y divide-mist/60">
                                    {keys.map((k) => (
                                        <SelectableListRow
                                            key={k.id}
                                            to={`/${orgId}/api-keys/${k.id}`}
                                            withCheckbox={canManage}
                                            checkboxDisabled={!!k.revoked_at}
                                            selected={selection.isSelected(k.id)}
                                            onToggleSelect={() => selection.toggle(k.id)}
                                            actions={
                                                <RowActions
                                                    actions={[
                                                        {
                                                            key: 'view',
                                                            label: t('common.view'),
                                                            to: `/${orgId}/api-keys/${k.id}`,
                                                        },
                                                        ...(canManage && !k.revoked_at
                                                            ? [
                                                                  {
                                                                      key: 'revoke',
                                                                      label: t(
                                                                          'console.api_keys.revoke',
                                                                      ),
                                                                      destructive: true,
                                                                      onSelect: () =>
                                                                          setRevokeIds([k.id]),
                                                                  },
                                                              ]
                                                            : []),
                                                    ]}
                                                />
                                            }
                                        >
                                            <div className="space-y-1">
                                                <p className="font-medium">{k.name}</p>
                                                <p className="font-mono text-sm text-ink-soft">
                                                    {k.prefix}…{k.last_four}
                                                    {k.revoked_at ? (
                                                        <span className="ml-2 text-danger">
                                                            {t('console.api_keys.revoked')}
                                                        </span>
                                                    ) : null}
                                                </p>
                                                <p className="text-xs text-ink-soft/55">
                                                    {t('common.detail.created')}{' '}
                                                    {formatWhen(k.created_at)}
                                                </p>
                                            </div>
                                        </SelectableListRow>
                                    ))}
                                </ul>
                            </div>
                            <Pagination
                                meta={meta}
                                onPageChange={list.setPage}
                                onPerPageChange={list.setPerPage}
                            />
                        </>
                    ) : null}
                </>
            ) : null}

            <ConfirmDelete
                open={revokeIds !== null}
                onOpenChange={(open) => {
                    if (!open) setRevokeIds(null);
                }}
                count={revokeIds?.length}
                confirmLabel={t('console.api_keys.revoke')}
                onConfirm={async () => {
                    if (!revokeIds?.length || !orgId) return;
                    try {
                        if (revokeIds.length === 1) {
                            await apiDelete(
                                `/api/v1/organizations/${orgId}/api-keys/${revokeIds[0]}`,
                            );
                        } else {
                            await apiPost(`/api/v1/organizations/${orgId}/api-keys/bulk-delete`, {
                                ids: revokeIds,
                            });
                        }
                        toast.success(t('console.api_keys.revoke_success_title'), {
                            description: t('console.api_keys.revoke_success_description'),
                        });
                        selection.clear();
                        list.refresh();
                    } catch (err) {
                        toast.error(t('console.api_keys.revoke_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.api_keys.revoke_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
