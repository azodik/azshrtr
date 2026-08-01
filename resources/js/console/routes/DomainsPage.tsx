import { Share2 } from 'lucide-react';
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
import { buildListQuery } from '@/lib/listQuery';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
} from '@/lib/pagination';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type DomainRow = {
    id: string;
    hostname: string;
    status: string;
    verified_at: string | null;
    dns_records: Array<{ type: string; name: string; value: string }> | null;
};

type DomainsResponse = Paginated<DomainRow> & { cname_target?: string };

export function DomainsPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'desc', 25);
    const [domains, setDomains] = useState<DomainRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta());
    const [loading, setLoading] = useState(true);
    const [hostname, setHostname] = useState('');
    const [pending, setPending] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [deleteIds, setDeleteIds] = useState<string[] | null>(null);
    const [downloading, setDownloading] = useState(false);
    const rowIds = useMemo(() => (domains ?? []).map((d) => d.id), [domains]);
    const selection = useSelection(rowIds);

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        void apiGet<DomainsResponse>(
            `/api/v1/organizations/${orgId}/domains${buildListQuery(list.applied)}`,
        )
            .then((data) => {
                if (cancelled) return;
                setDomains(data.data ?? []);
                setMeta(metaFromPaginated(data));
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [orgId, list.applied, list.reloadToken]);

    const onAdd = async (event: FormEvent) => {
        event.preventDefault();
        if (!orgId) return;
        setPending(true);
        try {
            await apiPost(`/api/v1/organizations/${orgId}/domains`, { hostname });
            setHostname('');
            setShowForm(false);
            list.setPage(1);
            list.refresh();
            toast.success(t('console.domains.add_success_title'), {
                description: t('console.domains.add_success_description'),
            });
        } catch (err) {
            toast.error(t('console.domains.add_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.domains.add_error_description'),
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
                `/api/v1/organizations/${orgId}/domains/export${buildListQuery({ ...list.applied, page: 1 }, { format })}`,
                `domains.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    if (!org || !orgId) return null;

    const hasRows = domains !== null && domains.length > 0;
    const isEmpty =
        domains !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '';
    const noResults = domains !== null && domains.length === 0 && !isEmpty;

    const form = (
        <form onSubmit={onAdd} className="console-panel space-y-3 p-5">
            <div className="space-y-1.5">
                <Label htmlFor="domain-hostname">{t('console.domains.hostname')}</Label>
                <Input
                    id="domain-hostname"
                    required
                    value={hostname}
                    onChange={(e) => setHostname(e.target.value)}
                    placeholder={t('console.domains.hostname_placeholder')}
                />
            </div>
            <p className="text-xs text-ink-soft">{t('console.domains.dns_hint')}</p>
            <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={pending}>
                    {pending ? t('console.domains.adding') : t('console.domains.add')}
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
                title={t('console.domains.title')}
                description={t('console.domains.description')}
                action={
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownload('csv')}
                        >
                            {t('common.download_csv')}
                        </Button>
                        {!isEmpty && !showForm ? (
                            <Button type="button" onClick={() => setShowForm(true)}>
                                {t('console.domains.add')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            {domains === null && loading ? <ListSkeleton /> : null}

            {isEmpty ? (
                <>
                    <EmptyState
                        icon={Share2}
                        title={t('console.domains.empty_title')}
                        description={t('console.domains.empty_description')}
                    />
                    {form}
                </>
            ) : null}

            {!isEmpty && showForm ? form : null}

            {!isEmpty && domains !== null ? (
                <>
                    <ListControls
                        state={list.draft}
                        onQueryChange={(q) => list.patchDraft({ q })}
                        onSortChange={(sort) => list.patchDraft({ sort })}
                        onDirectionChange={(direction) => list.patchDraft({ direction })}
                        onDateChange={(next) => list.patchDraft(next)}
                        onApply={list.applyFilters}
                        onClear={list.clearFilters}
                        searchPlaceholder={t('console.domains.search_placeholder')}
                        sortOptions={[
                            { value: 'created_at', label: t('common.sort.created_at') },
                            { value: 'hostname', label: t('common.sort.hostname') },
                            { value: 'status', label: t('common.sort.status') },
                            { value: 'verified_at', label: t('common.sort.verified_at') },
                        ]}
                    />

                    <BulkActionBar
                        count={selection.selectedCount}
                        onClear={selection.clear}
                        onDelete={() => setDeleteIds(selection.selectedIds)}
                    />

                    {loading ? <ListSkeleton /> : null}

                    {!loading && noResults ? (
                        <EmptyState
                            icon={Share2}
                            title={t('console.domains.empty_title')}
                            description={t('console.domains.empty_description')}
                            action={
                                <Button type="button" variant="outline" onClick={list.clearFilters}>
                                    {t('common.clear_filters')}
                                </Button>
                            }
                        />
                    ) : null}

                    {!loading && hasRows ? (
                        <>
                            <div className="overflow-hidden console-panel">
                                <SelectAllHeader
                                    allSelected={selection.allSelected}
                                    someSelected={selection.someSelected}
                                    onToggleAll={selection.toggleAll}
                                />
                                <ul className="divide-y divide-mist/60">
                                    {domains.map((d) => (
                                        <SelectableListRow
                                            key={d.id}
                                            to={`/${orgId}/domains/${d.id}`}
                                            withCheckbox
                                            selected={selection.isSelected(d.id)}
                                            onToggleSelect={() => selection.toggle(d.id)}
                                            actions={
                                                <RowActions
                                                    actions={[
                                                        {
                                                            key: 'view',
                                                            label: t('common.view'),
                                                            to: `/${orgId}/domains/${d.id}`,
                                                        },
                                                        ...(!d.verified_at
                                                            ? [
                                                                  {
                                                                      key: 'verify',
                                                                      label: t(
                                                                          'console.domains.verify',
                                                                      ),
                                                                      onSelect: () =>
                                                                          void apiPost(
                                                                              `/api/v1/organizations/${orgId}/domains/${d.id}/verify`,
                                                                          )
                                                                              .then(() => {
                                                                                  toast.success(
                                                                                      t(
                                                                                          'console.domains.verify_success_title',
                                                                                      ),
                                                                                      {
                                                                                          description:
                                                                                              t(
                                                                                                  'console.domains.verify_success_description',
                                                                                              ),
                                                                                      },
                                                                                  );
                                                                                  list.refresh();
                                                                              })
                                                                              .catch(
                                                                                  (err: unknown) =>
                                                                                      toast.error(
                                                                                          t(
                                                                                              'console.domains.verify_error',
                                                                                          ),
                                                                                          {
                                                                                              description:
                                                                                                  err instanceof
                                                                                                  ApiError
                                                                                                      ? err.message
                                                                                                      : t(
                                                                                                            'console.domains.verify_error_description',
                                                                                                        ),
                                                                                          },
                                                                                      ),
                                                                              ),
                                                                  },
                                                              ]
                                                            : []),
                                                        {
                                                            key: 'delete',
                                                            label: t('console.domains.remove'),
                                                            destructive: true,
                                                            onSelect: () => setDeleteIds([d.id]),
                                                        },
                                                    ]}
                                                />
                                            }
                                        >
                                            <div className="space-y-1">
                                                <p className="font-medium">{d.hostname}</p>
                                                <p className="text-sm text-ink-soft">
                                                    {d.verified_at
                                                        ? t('console.domains.verified')
                                                        : d.status}
                                                    {d.verified_at
                                                        ? t('console.domains.ready_to_use')
                                                        : t('console.domains.awaiting_dns')}
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
                open={deleteIds !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteIds(null);
                }}
                count={deleteIds?.length}
                onConfirm={async () => {
                    if (!deleteIds?.length || !orgId) return;
                    try {
                        if (deleteIds.length === 1) {
                            await apiDelete(
                                `/api/v1/organizations/${orgId}/domains/${deleteIds[0]}`,
                            );
                        } else {
                            await apiPost(`/api/v1/organizations/${orgId}/domains/bulk-delete`, {
                                ids: deleteIds,
                            });
                        }
                        toast.success(t('console.domains.remove_success_title'), {
                            description: t('console.domains.remove_success_description'),
                        });
                        selection.clear();
                        list.refresh();
                    } catch (err) {
                        toast.error(t('console.domains.remove_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.domains.remove_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
