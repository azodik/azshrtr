import { FileText } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { ListControls } from '@/components/ListControls';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { Pagination } from '@/components/Pagination';
import { RowActions } from '@/components/RowActions';
import { SelectableListRow } from '@/components/SelectableListRow';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { MultiSelect, type MultiSelectOption } from '@/components/ui/multi-select';
import { useListState } from '@/hooks/useListState';
import { useI18n } from '@/i18n/useI18n';
import { apiGet } from '@/lib/api';
import { AUDIT_ACTION_VALUES, auditActionKey, isAuditActionValue } from '@/lib/auditActions';
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

type AuditRow = {
    id: string;
    action: string;
    created_at: string;
    resource_type?: string | null;
    actor?: { id: number; name: string; email: string } | null;
};

export function AuditPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const list = useListState('created_at', 'desc', 50);
    const [rows, setRows] = useState<AuditRow[] | null>(null);
    const [meta, setMeta] = useState<PaginationMeta>(emptyPaginationMeta(50));
    const [loading, setLoading] = useState(true);
    const [actionsDraft, setActionsDraft] = useState<string[]>([]);
    const [actionsApplied, setActionsApplied] = useState<string[]>([]);
    const [downloading, setDownloading] = useState(false);

    const actionOptions = useMemo<MultiSelectOption[]>(
        () =>
            AUDIT_ACTION_VALUES.map((value) => ({
                value,
                label: t(auditActionKey(value)),
            })),
        [t],
    );

    useEffect(() => {
        if (!orgId) return;
        let cancelled = false;
        setLoading(true);
        const qs = buildListQuery(list.applied, {
            actions: actionsApplied.length > 0 ? actionsApplied : undefined,
        });
        void apiGet<Paginated<AuditRow>>(`/api/v1/organizations/${orgId}/audit-logs${qs}`)
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
    }, [orgId, list.applied, list.reloadToken, actionsApplied]);

    if (!org || !orgId) return null;

    const actionLabel = (action: string): string =>
        isAuditActionValue(action)
            ? t(auditActionKey(action))
            : action.replaceAll('.', ' · ').replaceAll('_', ' ');

    const applyFilters = () => {
        list.applyFilters();
        setActionsApplied(actionsDraft);
    };

    const clearFilters = () => {
        list.clearFilters();
        setActionsDraft([]);
        setActionsApplied([]);
    };

    const onDownload = async (format: 'csv' | 'json') => {
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/audit-logs/export${buildListQuery(
                    { ...list.applied, page: 1 },
                    {
                        format,
                        actions: actionsApplied.length > 0 ? actionsApplied : undefined,
                    },
                )}`,
                `audit-logs.${format}`,
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
        actionsApplied.length === 0;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.audit.title')}
                description={t('console.audit.description')}
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
                searchPlaceholder={t('console.audit.search_placeholder')}
                sortOptions={[
                    { value: 'created_at', label: t('common.sort.created_at') },
                    { value: 'action', label: t('common.sort.action') },
                    { value: 'resource_type', label: t('common.sort.resource_type') },
                ]}
            >
                <div className="space-y-1.5 sm:col-span-2">
                    <Label>{t('console.audit.actions')}</Label>
                    <MultiSelect
                        options={actionOptions}
                        value={actionsDraft}
                        onChange={setActionsDraft}
                        emptyLabel={t('console.audit.any_action')}
                    />
                </div>
            </ListControls>

            {rows === null && loading ? <ListSkeleton withCheckbox={false} /> : null}

            {rows !== null ? (
                <>
                    {loading ? <ListSkeleton withCheckbox={false} /> : null}

                    {!loading && rows.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title={t('console.audit.empty_title')}
                            description={t('console.audit.empty_description')}
                            action={
                                isEmpty ? (
                                    <Button asChild>
                                        <Link to={`/${orgId}/links`}>
                                            {t('console.audit.create_link')}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button type="button" variant="outline" onClick={clearFilters}>
                                        {t('common.clear_filters')}
                                    </Button>
                                )
                            }
                        />
                    ) : null}

                    {!loading && hasRows ? (
                        <>
                            <ul className="divide-y divide-mist/60 overflow-hidden console-panel">
                                {rows.map((r) => (
                                    <SelectableListRow
                                        key={r.id}
                                        to={`/${orgId}/audit/${r.id}`}
                                        actions={
                                            <RowActions
                                                actions={[
                                                    {
                                                        key: 'view',
                                                        label: t('common.view'),
                                                        to: `/${orgId}/audit/${r.id}`,
                                                    },
                                                ]}
                                            />
                                        }
                                    >
                                        <div className="min-w-0">
                                            <span className="text-sm font-medium">
                                                {actionLabel(r.action)}
                                            </span>
                                            {r.actor ? (
                                                <p className="text-xs text-ink-soft">
                                                    {r.actor.email}
                                                </p>
                                            ) : null}
                                            <p className="text-xs text-ink-soft/55">
                                                {formatWhen(r.created_at)}
                                            </p>
                                        </div>
                                    </SelectableListRow>
                                ))}
                            </ul>
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
