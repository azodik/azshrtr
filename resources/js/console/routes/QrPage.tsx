import { QrCode } from 'lucide-react';
import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { Link as RouterLink, useSearchParams } from 'react-router';
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
    short_url?: string;
};

type QrRow = {
    id: string;
    link_id: string | null;
    content: string;
    size: number;
    format: string;
    created_at: string;
    link?: { id: string; code: string; title: string | null; destination_url: string } | null;
};

type QrResult = {
    qr: { id: string; size: number; format: string; content: string; link_id: string | null };
    svg: string;
};

type Mode = 'link' | 'url';

export function QrPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const [searchParams, setSearchParams] = useSearchParams();
    const [links, setLinks] = useState<LinkRow[] | null>(null);
    const [mode, setMode] = useState<Mode>(searchParams.get('link') ? 'link' : 'link');
    const [linkId, setLinkId] = useState(searchParams.get('link') ?? '');
    const [content, setContent] = useState('');
    const [size, setSize] = useState(256);
    const [result, setResult] = useState<QrResult | null>(null);
    const [pending, setPending] = useState(false);
    const [copied, setCopied] = useState(false);

    const list = useListState('created_at', 'desc', 25);
    const [history, setHistory] = useState<QrRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta());
    const [historyLoading, setHistoryLoading] = useState(true);
    const [deleteIds, setDeleteIds] = useState<string[] | null>(null);
    const [downloading, setDownloading] = useState(false);
    const rowIds = useMemo(() => (history ?? []).map((row) => row.id), [history]);
    const selection = useSelection(rowIds);

    useEffect(() => {
        if (!orgId) {
            return;
        }
        void apiGet<Paginated<LinkRow>>(
            `/api/v1/organizations/${orgId}/links${buildListQuery({
                page: 1,
                perPage: 100,
                q: '',
                sort: 'created_at',
                direction: 'desc',
                from: '',
                to: '',
            })}`,
        ).then((data) => {
            setLinks(data.data ?? []);
        });
    }, [orgId]);

    useEffect(() => {
        if (!orgId) {
            return;
        }
        let cancelled = false;
        setHistoryLoading(true);
        void apiGet<Paginated<QrRow>>(
            `/api/v1/organizations/${orgId}/qr${buildListQuery(list.applied)}`,
        )
            .then((data) => {
                if (cancelled) return;
                setHistory(data.data ?? []);
                setMeta(metaFromPaginated(data));
            })
            .finally(() => {
                if (!cancelled) setHistoryLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [orgId, list.applied, list.reloadToken]);

    useEffect(() => {
        const fromQuery = searchParams.get('link');
        if (fromQuery) {
            setMode('link');
            setLinkId(fromQuery);
        }
    }, [searchParams]);

    const selected = useMemo(
        () => links?.find((link) => link.id === linkId) ?? null,
        [links, linkId],
    );

    if (!org || !orgId) {
        return null;
    }

    const onGenerate = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        setResult(null);
        try {
            const payload =
                mode === 'link' ? { link_id: linkId, size } : { content: content.trim(), size };
            if (mode === 'link' && !linkId) {
                toast.error(t('console.qr.choose_link_error'), {
                    description: t('console.qr.choose_link_error_description'),
                });
                setPending(false);
                return;
            }
            if (mode === 'url' && content.trim() === '') {
                toast.error(t('console.qr.enter_content_error'), {
                    description: t('console.qr.enter_content_error_description'),
                });
                setPending(false);
                return;
            }
            const data = await apiPost<QrResult>(`/api/v1/organizations/${orgId}/qr`, payload);
            setResult(data);
            if (mode === 'link') {
                setSearchParams({ link: linkId }, { replace: true });
            }
            list.setPage(1);
            list.refresh();
            toast.success(t('console.qr.generate_success_title'), {
                description: t('console.qr.generate_success_description'),
            });
        } catch (err) {
            toast.error(t('console.qr.generate_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.qr.generate_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const downloadSvg = async (qrId: string) => {
        await downloadFromApi(
            `/api/v1/organizations/${orgId}/qr/${qrId}/download`,
            `qr-${qrId}.svg`,
        );
    };

    const onDownloadList = async (format: 'csv' | 'json') => {
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/qr/export${buildListQuery(
                    { ...list.applied, page: 1 },
                    { format },
                )}`,
                `qr.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    const onCopy = async () => {
        const value =
            mode === 'link' && selected
                ? (selected.short_url ?? absoluteShortUrl(selected.code))
                : (result?.qr.content ?? content.trim());
        if (!value) {
            return;
        }
        const ok = await copyText(value);
        if (ok) {
            setCopied(true);
            toast.success(t('common.copied'), { description: t('common.copied_description') });
            window.setTimeout(() => setCopied(false), 1600);
        } else {
            toast.error(t('common.copy_failed'), {
                description: t('common.copy_failed_description'),
            });
        }
    };

    const hasRows = history !== null && history.length > 0;
    const historyEmpty =
        history !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '';
    const historyNoResults = history !== null && history.length === 0 && !historyEmpty;

    return (
        <section className="space-y-8">
            <PageHeader
                title={t('console.qr.title')}
                description={t('console.qr.description')}
                action={
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownloadList('csv')}
                        >
                            {downloading ? t('common.downloading') : t('common.download_csv')}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownloadList('json')}
                        >
                            {t('common.download_json')}
                        </Button>
                    </>
                }
            />

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => {
                        setMode('link');
                        setResult(null);
                    }}
                    className={`rounded-md px-3 py-1.5 text-sm font-semibold ${
                        mode === 'link'
                            ? 'bg-teal text-paper-elevated'
                            : 'border border-mist text-ink-soft hover:text-ink'
                    }`}
                >
                    {t('console.qr.mode_short_link')}
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setMode('url');
                        setResult(null);
                    }}
                    className={`rounded-md px-3 py-1.5 text-sm font-semibold ${
                        mode === 'url'
                            ? 'bg-teal text-paper-elevated'
                            : 'border border-mist text-ink-soft hover:text-ink'
                    }`}
                >
                    {t('console.qr.mode_custom_url')}
                </button>
            </div>

            {mode === 'link' && links !== null && links.length === 0 ? (
                <EmptyState
                    icon={QrCode}
                    title={t('console.qr.empty_links_title')}
                    description={t('console.qr.empty_links_description')}
                    action={
                        <div className="flex flex-wrap gap-2">
                            <RouterLink
                                to={`/${orgId}/links`}
                                className="inline-flex items-center justify-center rounded-[var(--radius-control)] bg-teal px-4 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright"
                            >
                                {t('console.qr.go_to_links')}
                            </RouterLink>
                            <button
                                type="button"
                                onClick={() => setMode('url')}
                                className="inline-flex rounded-md border border-mist px-4 py-2.5 text-sm font-semibold"
                            >
                                {t('console.qr.use_custom_url')}
                            </button>
                        </div>
                    }
                />
            ) : (
                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
                    <form onSubmit={onGenerate} className="space-y-4 console-panel p-5">
                        {mode === 'link' ? (
                            <label className="block text-sm font-medium">
                                {t('console.qr.short_link_label')}
                                <select
                                    required
                                    value={linkId}
                                    onChange={(e) => {
                                        setLinkId(e.target.value);
                                        setResult(null);
                                    }}
                                    className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                                >
                                    <option value="">{t('console.qr.select_link')}</option>
                                    {(links ?? []).map((link) => (
                                        <option key={link.id} value={link.id}>
                                            /{link.code}
                                            {link.title ? ` — ${link.title}` : ''} —{' '}
                                            {link.destination_url}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        ) : (
                            <label className="block text-sm font-medium">
                                {t('console.qr.url_or_text')}
                                <input
                                    required
                                    value={content}
                                    onChange={(e) => setContent(e.target.value)}
                                    placeholder={t('console.qr.url_placeholder')}
                                    className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                                />
                            </label>
                        )}

                        <label className="block text-sm font-medium">
                            {t('console.qr.size_label')}
                            <input
                                type="number"
                                min={64}
                                max={1024}
                                step={32}
                                value={size}
                                onChange={(e) => setSize(Number(e.target.value))}
                                className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                            />
                        </label>

                        <button
                            type="submit"
                            disabled={pending}
                            className="inline-flex w-full items-center justify-center rounded-[var(--radius-control)] bg-teal px-4 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright disabled:opacity-60 sm:w-auto"
                        >
                            {pending ? t('console.qr.generating') : t('console.qr.generate')}
                        </button>
                    </form>

                    <div className="console-panel p-5">
                        {result ? (
                            <div className="space-y-4">
                                <div className="mx-auto flex aspect-square max-w-[280px] items-center justify-center rounded-md bg-paper p-4">
                                    <img
                                        src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(result.svg)}`}
                                        alt={t('console.qr.preview_alt')}
                                        className="h-full w-full object-contain"
                                    />
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => void downloadSvg(result.qr.id)}
                                        className="rounded-md bg-teal px-3 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright"
                                    >
                                        {t('console.qr.download_svg')}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => void onCopy()}
                                        className="rounded-md border border-mist px-3 py-2 text-sm font-semibold text-ink hover:bg-fog"
                                    >
                                        {copied
                                            ? t('console.qr.copied')
                                            : t('console.qr.copy_content')}
                                    </button>
                                </div>
                                <p className="break-all text-xs text-ink-soft">
                                    {t('console.qr.meta', {
                                        size: result.qr.size,
                                        format: result.qr.format.toUpperCase(),
                                        content: result.qr.content,
                                    })}
                                </p>
                            </div>
                        ) : (
                            <EmptyState
                                className="border-0 bg-transparent px-0 py-6"
                                icon={QrCode}
                                title={t('console.qr.empty_title')}
                                description={t('console.qr.empty_description')}
                            />
                        )}
                    </div>
                </div>
            )}

            <div className="space-y-4">
                <div>
                    <h2 className="text-lg font-semibold text-ink">
                        {t('console.qr.history_title')}
                    </h2>
                    <p className="text-sm text-ink-soft">{t('console.qr.history_description')}</p>
                </div>

                {history === null && historyLoading ? <ListSkeleton /> : null}

                {historyEmpty ? (
                    <EmptyState
                        icon={QrCode}
                        title={t('console.qr.history_empty_title')}
                        description={t('console.qr.history_empty_description')}
                    />
                ) : null}

                {!historyEmpty && history !== null ? (
                    <>
                        <ListControls
                            state={list.draft}
                            onQueryChange={(q) => list.patchDraft({ q })}
                            onSortChange={(sort) => list.patchDraft({ sort })}
                            onDirectionChange={(direction) => list.patchDraft({ direction })}
                            onDateChange={(next) => list.patchDraft(next)}
                            onApply={list.applyFilters}
                            onClear={list.clearFilters}
                            searchPlaceholder={t('console.qr.history_search_placeholder')}
                            sortOptions={[
                                { value: 'created_at', label: t('common.sort.created_at') },
                                { value: 'size', label: t('common.sort.size') },
                                { value: 'content', label: t('common.sort.content') },
                                { value: 'format', label: t('common.sort.format') },
                            ]}
                        />

                        {historyLoading ? <ListSkeleton /> : null}

                        {!historyLoading && historyNoResults ? (
                            <EmptyState
                                icon={QrCode}
                                title={t('console.qr.history_no_results_title')}
                                description={t('console.qr.history_no_results_description')}
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

                        <BulkActionBar
                            count={selection.selectedCount}
                            onClear={selection.clear}
                            onDelete={() => setDeleteIds(selection.selectedIds)}
                        />

                        {!historyLoading && hasRows ? (
                            <>
                                <div className="overflow-hidden console-panel">
                                    <SelectAllHeader
                                        allSelected={selection.allSelected}
                                        someSelected={selection.someSelected}
                                        onToggleAll={selection.toggleAll}
                                    />
                                    <ul className="divide-y divide-mist/60">
                                        {history.map((row) => (
                                            <SelectableListRow
                                                key={row.id}
                                                to={`/${orgId}/qr/${row.id}`}
                                                withCheckbox
                                                selected={selection.isSelected(row.id)}
                                                onToggleSelect={() => selection.toggle(row.id)}
                                                actions={
                                                    <RowActions
                                                        actions={[
                                                            {
                                                                key: 'view',
                                                                label: t('common.view'),
                                                                to: `/${orgId}/qr/${row.id}`,
                                                            },
                                                            {
                                                                key: 'download',
                                                                label: t(
                                                                    'console.qr.history_download',
                                                                ),
                                                                onSelect: () =>
                                                                    void downloadSvg(row.id),
                                                            },
                                                            {
                                                                key: 'delete',
                                                                label: t('common.delete'),
                                                                destructive: true,
                                                                onSelect: () =>
                                                                    setDeleteIds([row.id]),
                                                            },
                                                        ]}
                                                    />
                                                }
                                            >
                                                <div className="space-y-1">
                                                    <p className="text-sm font-medium text-ink">
                                                        {row.link
                                                            ? t('console.qr.history_link', {
                                                                  code: row.link.code,
                                                              })
                                                            : t('console.qr.history_custom')}
                                                    </p>
                                                    <p className="truncate text-sm text-ink-soft">
                                                        {row.content}
                                                    </p>
                                                    <p className="text-xs text-ink-soft/55">
                                                        {row.size} px · {row.format.toUpperCase()} ·{' '}
                                                        {t('console.qr.history_created', {
                                                            when: formatWhen(row.created_at),
                                                        })}
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
            </div>

            <ConfirmDelete
                open={deleteIds !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteIds(null);
                }}
                count={deleteIds?.length}
                onConfirm={async () => {
                    if (!deleteIds?.length) return;
                    try {
                        if (deleteIds.length === 1) {
                            await apiDelete(`/api/v1/organizations/${orgId}/qr/${deleteIds[0]}`);
                        } else {
                            await apiPost(`/api/v1/organizations/${orgId}/qr/bulk-delete`, {
                                ids: deleteIds,
                            });
                        }
                        if (result && deleteIds.includes(result.qr.id)) {
                            setResult(null);
                        }
                        toast.success(t('console.qr.delete_success_title'), {
                            description: t('console.qr.delete_success_description'),
                        });
                        selection.clear();
                        list.refresh();
                    } catch (err) {
                        toast.error(t('console.qr.delete_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.qr.delete_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
