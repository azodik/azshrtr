import { Link2 } from 'lucide-react';
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
import { absoluteShortUrl, copyText, formatWhen } from '@/lib/format';
import { buildListQuery } from '@/lib/listQuery';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
} from '@/lib/pagination';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type LinkRow = {
    id: string;
    code: string;
    destination_url: string;
    title: string | null;
    click_count: number;
    short_url?: string;
    created_at: string;
};

export function LinksPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'desc', 25);
    const [links, setLinks] = useState<LinkRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta());
    const [loading, setLoading] = useState(true);
    const [url, setUrl] = useState('');
    const [title, setTitle] = useState('');
    const [pending, setPending] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [deleteIds, setDeleteIds] = useState<string[] | null>(null);
    const [downloading, setDownloading] = useState(false);
    const rowIds = useMemo(() => (links ?? []).map((link) => link.id), [links]);
    const selection = useSelection(rowIds);

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        void apiGet<Paginated<LinkRow>>(
            `/api/v1/organizations/${orgId}/links${buildListQuery(list.applied)}`,
        )
            .then((data) => {
                if (cancelled) return;
                setLinks(data.data ?? []);
                setMeta(metaFromPaginated(data));
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
        if (!orgId) return;
        setPending(true);
        try {
            await apiPost(`/api/v1/organizations/${orgId}/links`, {
                destination_url: url,
                title: title.trim() === '' ? null : title.trim(),
            });
            setUrl('');
            setTitle('');
            setShowForm(false);
            list.setPage(1);
            list.refresh();
            toast.success(t('console.links.create_success_title'), {
                description: t('console.links.create_success_description'),
            });
        } catch (err) {
            toast.error(t('console.links.create_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.links.create_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const onCopy = async (link: LinkRow) => {
        const ok = await copyText(link.short_url ?? absoluteShortUrl(link.code));
        if (ok) {
            toast.success(t('common.copied'), { description: t('common.copied_description') });
        } else {
            toast.error(t('console.links.copy_failed'), {
                description: t('console.links.copy_failed_description'),
            });
        }
    };

    const onDownload = async (format: 'csv' | 'json') => {
        if (!orgId) return;
        setDownloading(true);
        try {
            const qs = buildListQuery({ ...list.applied, page: 1 }, { format });
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/links/export${qs}`,
                `links.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    if (!org || !orgId) return null;

    const hasRows = links !== null && links.length > 0;
    const isEmpty =
        links !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '';
    const noResults = links !== null && links.length === 0 && !isEmpty;

    const createForm = (
        <form onSubmit={onCreate} className="console-panel space-y-4 p-5">
            <div className="space-y-1.5">
                <Label htmlFor="link-url">{t('console.links.destination_url')}</Label>
                <Input
                    id="link-url"
                    type="url"
                    required
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                    placeholder={t('console.links.destination_placeholder')}
                />
            </div>
            <div className="space-y-1.5">
                <Label htmlFor="link-title">
                    {t('console.links.title_label')}{' '}
                    <span className="font-normal text-ink-soft">{t('common.optional')}</span>
                </Label>
                <Input
                    id="link-title"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder={t('console.links.title_placeholder')}
                />
            </div>
            <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={pending}>
                    {pending ? t('console.links.creating') : t('console.links.create')}
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
                title={t('console.links.title')}
                description={t('console.links.description')}
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
                        {!isEmpty && !showForm ? (
                            <Button type="button" onClick={() => setShowForm(true)}>
                                {t('console.links.new')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            {links === null && loading ? <ListSkeleton /> : null}

            {isEmpty ? (
                <>
                    <EmptyState
                        icon={Link2}
                        title={t('console.links.empty_title')}
                        description={t('console.links.empty_description')}
                    />
                    {createForm}
                </>
            ) : null}

            {!isEmpty && showForm ? createForm : null}

            {!isEmpty && links !== null ? (
                <>
                    <ListControls
                        state={list.draft}
                        onQueryChange={(q) => list.patchDraft({ q })}
                        onSortChange={(sort) => list.patchDraft({ sort })}
                        onDirectionChange={(direction) => list.patchDraft({ direction })}
                        onDateChange={(next) => list.patchDraft(next)}
                        onApply={list.applyFilters}
                        onClear={list.clearFilters}
                        searchPlaceholder={t('console.links.search_placeholder')}
                        sortOptions={[
                            { value: 'created_at', label: t('common.sort.created_at') },
                            { value: 'code', label: t('common.sort.code') },
                            { value: 'title', label: t('common.sort.title') },
                            { value: 'click_count', label: t('common.sort.click_count') },
                            { value: 'destination_url', label: t('common.sort.destination_url') },
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
                            icon={Link2}
                            title={t('console.links.no_results_title')}
                            description={t('console.links.no_results_description')}
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
                                    {links.map((link) => {
                                        const short = link.short_url ?? absoluteShortUrl(link.code);
                                        return (
                                            <SelectableListRow
                                                key={link.id}
                                                to={`/${orgId}/links/${link.id}`}
                                                withCheckbox
                                                selected={selection.isSelected(link.id)}
                                                onToggleSelect={() => selection.toggle(link.id)}
                                                actions={
                                                    <RowActions
                                                        actions={[
                                                            {
                                                                key: 'view',
                                                                label: t('common.view'),
                                                                to: `/${orgId}/links/${link.id}`,
                                                            },
                                                            {
                                                                key: 'copy',
                                                                label: t('console.links.copy'),
                                                                onSelect: () => void onCopy(link),
                                                            },
                                                            {
                                                                key: 'qr',
                                                                label: t('console.links.qr'),
                                                                to: `/${orgId}/qr?link=${link.id}`,
                                                            },
                                                            {
                                                                key: 'delete',
                                                                label: t('console.links.delete'),
                                                                destructive: true,
                                                                onSelect: () =>
                                                                    setDeleteIds([link.id]),
                                                            },
                                                        ]}
                                                    />
                                                }
                                            >
                                                <div className="space-y-1">
                                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                        <a
                                                            href={short}
                                                            className="font-medium text-teal hover:underline"
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            onClick={(e) => e.stopPropagation()}
                                                        >
                                                            /{link.code}
                                                        </a>
                                                        <span className="rounded-md bg-fog px-1.5 py-0.5 text-xs tabular-nums text-ink-soft">
                                                            {t(
                                                                link.click_count === 1
                                                                    ? 'console.links.clicks_one'
                                                                    : 'console.links.clicks_other',
                                                                { count: link.click_count },
                                                            )}
                                                        </span>
                                                    </div>
                                                    {link.title ? (
                                                        <p className="text-sm font-medium text-ink">
                                                            {link.title}
                                                        </p>
                                                    ) : null}
                                                    <p className="truncate text-sm text-ink-soft">
                                                        {link.destination_url}
                                                    </p>
                                                    <p className="text-xs text-ink-soft/55">
                                                        {t('console.links.created', {
                                                            when: formatWhen(link.created_at),
                                                        })}
                                                    </p>
                                                </div>
                                            </SelectableListRow>
                                        );
                                    })}
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
                            await apiDelete(`/api/v1/organizations/${orgId}/links/${deleteIds[0]}`);
                        } else {
                            await apiPost(`/api/v1/organizations/${orgId}/links/bulk-delete`, {
                                ids: deleteIds,
                            });
                        }
                        toast.success(t('console.links.delete_success_title'), {
                            description: t('console.links.delete_success_description'),
                        });
                        selection.clear();
                        list.refresh();
                    } catch (err) {
                        toast.error(t('console.links.delete_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.links.delete_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
