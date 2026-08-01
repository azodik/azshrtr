import { ScrollText } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { ListControls } from '@/components/ListControls';
import { TableSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { Pagination } from '@/components/Pagination';
import { RowActions } from '@/components/RowActions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { MultiSelect, type MultiSelectOption } from '@/components/ui/multi-select';
import { useListState } from '@/hooks/useListState';
import { useI18n } from '@/i18n/useI18n';
import { apiGet } from '@/lib/api';
import { cn } from '@/lib/cn';
import { downloadFromApi } from '@/lib/download';
import { formatWhen } from '@/lib/format';
import { buildListQuery } from '@/lib/listQuery';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
} from '@/lib/pagination';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type LogRow = {
    id: number;
    method: string;
    path: string;
    status: number;
    latency_ms: number;
    created_at: string;
    api_key?: { id: string; name: string; prefix: string; last_four: string } | null;
};

const METHOD_OPTIONS = [
    { value: 'GET', label: 'GET' },
    { value: 'POST', label: 'POST' },
    { value: 'PATCH', label: 'PATCH' },
    { value: 'PUT', label: 'PUT' },
    { value: 'DELETE', label: 'DELETE' },
];

const STATUS_VALUES = [
    '200',
    '201',
    '204',
    '400',
    '401',
    '403',
    '404',
    '422',
    '429',
    '500',
] as const;

export function ApiLogsPage() {
    const { t } = useI18n();
    const navigate = useNavigate();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'desc', 50);
    const [rows, setRows] = useState<LogRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta(50));
    const [loading, setLoading] = useState(true);
    const [methodsDraft, setMethodsDraft] = useState<string[]>([]);
    const [statusesDraft, setStatusesDraft] = useState<string[]>([]);
    const [methodsApplied, setMethodsApplied] = useState<string[]>([]);
    const [statusesApplied, setStatusesApplied] = useState<string[]>([]);
    const [downloading, setDownloading] = useState(false);

    const statusOptions = useMemo<MultiSelectOption[]>(
        () =>
            STATUS_VALUES.map((value) => ({
                value,
                label: t(`console.api_logs.status.${value}`),
            })),
        [t],
    );

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        const qs = buildListQuery(list.applied, {
            methods: methodsApplied.length > 0 ? methodsApplied : undefined,
            statuses: statusesApplied.length > 0 ? statusesApplied : undefined,
        });
        void apiGet<Paginated<LogRow>>(`/api/v1/organizations/${orgId}/api-request-logs${qs}`)
            .then((d) => {
                if (cancelled) return;
                setRows(d.data ?? []);
                setMeta(metaFromPaginated(d));
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });
        return () => {
            cancelled = true;
        };
    }, [orgId, list.applied, list.reloadToken, methodsApplied, statusesApplied]);

    if (!org || !orgId) return null;

    const applyFilters = () => {
        list.applyFilters();
        setMethodsApplied(methodsDraft);
        setStatusesApplied(statusesDraft);
    };

    const clearFilters = () => {
        list.clearFilters();
        setMethodsDraft([]);
        setStatusesDraft([]);
        setMethodsApplied([]);
        setStatusesApplied([]);
    };

    const onDownload = async (format: 'csv' | 'json') => {
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/api-request-logs/export${buildListQuery(
                    { ...list.applied, page: 1 },
                    {
                        format,
                        methods: methodsApplied.length > 0 ? methodsApplied : undefined,
                        statuses: statusesApplied.length > 0 ? statusesApplied : undefined,
                    },
                )}`,
                `api-request-logs.${format}`,
            );
        } finally {
            setDownloading(false);
        }
    };

    const hasRows = rows !== null && rows.length > 0;
    const isEmpty =
        rows !== null &&
        meta.total === 0 &&
        list.applied.q === '' &&
        list.applied.from === '' &&
        list.applied.to === '' &&
        methodsApplied.length === 0 &&
        statusesApplied.length === 0;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.api_logs.title')}
                description={t('console.api_logs.description')}
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
                    </>
                }
            />

            <ListControls
                state={list.draft}
                onQueryChange={(q) => list.patchDraft({ q })}
                onSortChange={(sort) => list.patchDraft({ sort })}
                onDirectionChange={(direction) => list.patchDraft({ direction })}
                onDateChange={(next) => list.patchDraft(next)}
                onApply={applyFilters}
                onClear={clearFilters}
                searchPlaceholder={t('console.api_logs.path_placeholder')}
                sortOptions={[
                    { value: 'created_at', label: t('common.sort.created_at') },
                    { value: 'method', label: t('common.sort.method') },
                    { value: 'path', label: t('common.sort.path') },
                    { value: 'status', label: t('common.sort.status') },
                    { value: 'latency_ms', label: t('common.sort.latency_ms') },
                ]}
            >
                <div className="space-y-1.5">
                    <Label>{t('console.api_logs.methods')}</Label>
                    <MultiSelect
                        options={METHOD_OPTIONS}
                        value={methodsDraft}
                        onChange={setMethodsDraft}
                        emptyLabel={t('console.api_logs.any_method')}
                    />
                </div>
                <div className="space-y-1.5">
                    <Label>{t('console.api_logs.status')}</Label>
                    <MultiSelect
                        options={statusOptions}
                        value={statusesDraft}
                        onChange={setStatusesDraft}
                        emptyLabel={t('console.api_logs.any_status')}
                    />
                </div>
            </ListControls>

            {rows === null && loading ? <TableSkeleton columns={7} /> : null}

            {rows !== null ? (
                <>
                    {loading ? <TableSkeleton columns={7} /> : null}

                    {!loading && rows.length === 0 ? (
                        <EmptyState
                            icon={ScrollText}
                            title={t('console.api_logs.empty_title')}
                            description={t('console.api_logs.empty_description')}
                            action={
                                !isEmpty ? (
                                    <Button type="button" variant="outline" onClick={clearFilters}>
                                        {t('common.clear_filters')}
                                    </Button>
                                ) : undefined
                            }
                        />
                    ) : null}

                    {!loading && hasRows ? (
                        <>
                            <div className="overflow-x-auto console-panel">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="border-b border-mist/60 text-xs text-ink-soft">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.when')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.method')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.path')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.status')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.latency')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                {t('console.api_logs.col.key')}
                                            </th>
                                            <th className="px-3 py-2 font-medium">
                                                <span className="sr-only">
                                                    {t('common.actions')}
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((r) => (
                                            <tr
                                                key={r.id}
                                                role="link"
                                                tabIndex={0}
                                                className={cn(
                                                    'cursor-pointer border-t border-mist/50 transition-colors hover:bg-fog/70 focus-visible:bg-fog/70',
                                                )}
                                                onClick={() =>
                                                    void navigate(`/${orgId}/api-logs/${r.id}`)
                                                }
                                                onKeyDown={(event) => {
                                                    if (
                                                        event.key === 'Enter' ||
                                                        event.key === ' '
                                                    ) {
                                                        event.preventDefault();
                                                        void navigate(`/${orgId}/api-logs/${r.id}`);
                                                    }
                                                }}
                                            >
                                                <td className="whitespace-nowrap px-3 py-2 text-xs text-ink-soft">
                                                    {formatWhen(r.created_at)}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge variant="secondary">{r.method}</Badge>
                                                </td>
                                                <td className="max-w-[240px] truncate px-3 py-2 font-mono text-xs">
                                                    {r.path}
                                                </td>
                                                <td className="px-3 py-2">{r.status}</td>
                                                <td className="px-3 py-2">
                                                    {t('console.api_logs.latency_ms', {
                                                        ms: r.latency_ms,
                                                    })}
                                                </td>
                                                <td className="px-3 py-2 text-xs text-ink-soft">
                                                    {r.api_key
                                                        ? t('console.api_logs.key_label', {
                                                              name: r.api_key.name,
                                                              prefix: r.api_key.prefix,
                                                              last_four: r.api_key.last_four,
                                                          })
                                                        : t('common.em_dash')}
                                                </td>
                                                <td
                                                    className="px-3 py-2"
                                                    onClick={(event) => event.stopPropagation()}
                                                >
                                                    <RowActions
                                                        actions={[
                                                            {
                                                                key: 'view',
                                                                label: t('common.view'),
                                                                to: `/${orgId}/api-logs/${r.id}`,
                                                            },
                                                        ]}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
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
        </section>
    );
}
