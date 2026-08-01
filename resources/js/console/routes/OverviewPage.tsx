import { Activity, Sparkles } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router';
import { useAuth } from '@/auth/AuthContext';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiGet } from '@/lib/api';
import { auditActionKey, isAuditActionValue } from '@/lib/auditActions';
import { formatWhen } from '@/lib/format';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type Overview = {
    plan: {
        slug: string;
        name: string;
        links_per_month: number | null;
        qr_per_month: number | null;
    };
    usage: {
        period: string;
        links_created: number;
        qr_generated: number;
        api_calls: number;
        scope?: 'free_pool' | 'organization';
    };
    free_pool: {
        links_created: number;
        qr_generated: number;
        api_keys: number;
        links_per_month: number | null;
        qr_per_month: number | null;
        api_keys_limit: number;
        organization_count: number;
    } | null;
    clicks: { last_7_days: number; last_30_days: number };
    top_countries: Array<{ country: string; total: number }>;
    recent_audit: Array<{ id: string; action: string; created_at: string }>;
    billing_enabled: boolean;
};

export function OverviewPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const navigate = useNavigate();
    const { setUser } = useAuth();
    const [data, setData] = useState<Overview | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!orgId) {
            return;
        }
        setError(null);
        void apiGet<Overview>(`/api/v1/organizations/${orgId}/overview`)
            .then(setData)
            .catch((err: unknown) => {
                if (err instanceof ApiError && err.status === 401) {
                    setUser(null);
                    navigate('/login', { replace: true });
                    return;
                }
                setError(err instanceof ApiError ? err.message : t('console.overview.load_error'));
            });
    }, [orgId, navigate, setUser, t]);

    if (!org || !orgId) return null;

    const isFresh =
        data !== null &&
        data.usage.links_created === 0 &&
        data.usage.qr_generated === 0 &&
        data.clicks.last_30_days === 0;

    const actionLabel = (action: string): string =>
        isAuditActionValue(action)
            ? t(auditActionKey(action))
            : action.replaceAll('.', ' · ').replaceAll('_', ' ');

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.overview.title')}
                description={t('console.overview.description', {
                    org: org.name,
                    plan: data?.plan.name ?? '…',
                })}
                badge={data ? <Badge variant="secondary">{data.plan.name}</Badge> : null}
                action={
                    <Button asChild>
                        <Link to={`/${orgId}/links`}>{t('console.overview.new_link')}</Link>
                    </Button>
                }
            />

            {error ? (
                <p className="text-sm text-danger" role="alert">
                    {error}
                </p>
            ) : null}

            {data ? (
                <>
                    {isFresh ? (
                        <EmptyState
                            icon={Sparkles}
                            title={t('console.overview.welcome_title')}
                            description={t('console.overview.welcome_description')}
                            action={
                                <div className="flex flex-wrap justify-center gap-2">
                                    <Button asChild>
                                        <Link to={`/${orgId}/links`}>
                                            {t('console.overview.create_link')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link to={`/${orgId}/qr`}>
                                            {t('console.overview.qr_codes')}
                                        </Link>
                                    </Button>
                                </div>
                            }
                        />
                    ) : null}

                    {data.free_pool ? (
                        <p className="rounded-md border border-mist/70 bg-paper-elevated px-4 py-3 text-sm text-ink-soft">
                            {t(
                                data.free_pool.organization_count === 1
                                    ? 'console.overview.free_pool_notice_one'
                                    : 'console.overview.free_pool_notice_other',
                                { count: data.free_pool.organization_count },
                            )}
                        </p>
                    ) : null}

                    <div className="grid gap-3 sm:grid-cols-3">
                        <Meter
                            label={t(
                                data.free_pool
                                    ? 'console.overview.meter.links_month_pool'
                                    : 'console.overview.meter.links_month',
                            )}
                            value={
                                data.free_pool
                                    ? data.free_pool.links_created
                                    : data.usage.links_created
                            }
                            limit={
                                data.free_pool
                                    ? data.free_pool.links_per_month
                                    : data.plan.links_per_month
                            }
                        />
                        <Meter
                            label={t(
                                data.free_pool
                                    ? 'console.overview.meter.qr_month_pool'
                                    : 'console.overview.meter.qr_month',
                            )}
                            value={
                                data.free_pool
                                    ? data.free_pool.qr_generated
                                    : data.usage.qr_generated
                            }
                            limit={
                                data.free_pool
                                    ? data.free_pool.qr_per_month
                                    : data.plan.qr_per_month
                            }
                        />
                        <Meter
                            label={t('console.overview.meter.api_calls')}
                            value={data.usage.api_calls}
                            limit={null}
                        />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Stat
                            label={t('console.overview.stat.clicks_7d')}
                            value={data.clicks.last_7_days}
                        />
                        <Stat
                            label={t('console.overview.stat.clicks_30d')}
                            value={data.clicks.last_30_days}
                        />
                    </div>

                    <div className="console-panel p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="font-display text-lg font-semibold">
                                    {t('console.overview.top_countries.title')}
                                </h2>
                                <p className="mt-1 text-sm text-ink-soft">
                                    {t('console.overview.top_countries.description')}
                                </p>
                            </div>
                            <Link
                                to={`/${orgId}/analytics`}
                                className="text-sm font-medium text-teal hover:underline"
                            >
                                {t('console.overview.full_analytics')}
                            </Link>
                        </div>
                        {data.top_countries.length === 0 ? (
                            <p className="mt-3 text-sm text-ink-soft">
                                {t('console.overview.top_countries.empty')}
                            </p>
                        ) : (
                            <ul className="mt-3 space-y-2">
                                {data.top_countries.map((row) => (
                                    <li
                                        key={row.country}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span className="font-medium">{row.country}</span>
                                        <span className="text-ink-soft">
                                            {row.total.toLocaleString()}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="console-panel p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="font-display text-lg font-semibold">
                                    {t('console.overview.plan_title', { plan: data.plan.name })}
                                </h2>
                                <p className="mt-1 text-sm text-ink-soft">
                                    {data.billing_enabled
                                        ? t('console.overview.billing_enabled')
                                        : t('console.overview.billing_self_host')}
                                </p>
                            </div>
                            <Link
                                to={`/${orgId}/billing`}
                                className="text-sm font-medium text-teal hover:underline"
                            >
                                {t('console.overview.billing_link')}
                            </Link>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <h2 className="font-display text-lg font-semibold">
                                {t('console.overview.recent_activity')}
                            </h2>
                            <Link
                                to={`/${orgId}/audit`}
                                className="text-sm font-medium text-teal hover:underline"
                            >
                                {t('console.overview.full_log')}
                            </Link>
                        </div>
                        {data.recent_audit.length === 0 ? (
                            <EmptyState
                                icon={Activity}
                                title={t('console.overview.no_activity_title')}
                                description={t('console.overview.no_activity_description')}
                            />
                        ) : (
                            <ul className="divide-y divide-mist/60 overflow-hidden console-panel">
                                {data.recent_audit.map((row) => (
                                    <li
                                        key={row.id}
                                        className="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <span className="text-sm font-medium">
                                            {actionLabel(row.action)}
                                        </span>
                                        <span className="text-xs text-ink-soft">
                                            {formatWhen(row.created_at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </>
            ) : error ? null : (
                <p className="text-sm text-ink-soft">{t('console.overview.loading')}</p>
            )}
        </section>
    );
}

function Meter({ label, value, limit }: { label: string; value: number; limit: number | null }) {
    const pct = limit ? Math.min(100, Math.round((value / limit) * 100)) : 0;
    return (
        <div className="console-panel p-4 sm:p-5">
            <p className="text-xs font-medium tracking-wide text-ink-soft uppercase">{label}</p>
            <p className="mt-2 font-display text-2xl font-semibold tracking-tight tabular-nums">
                {value}
                {limit !== null ? (
                    <span className="text-base font-normal text-ink-soft"> / {limit}</span>
                ) : null}
            </p>
            {limit !== null ? (
                <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-fog">
                    <div className="h-full rounded-full bg-teal" style={{ width: `${pct}%` }} />
                </div>
            ) : null}
        </div>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="console-panel p-4 sm:p-5">
            <p className="text-xs font-medium tracking-wide text-ink-soft uppercase">{label}</p>
            <p className="mt-2 font-display text-2xl font-semibold tracking-tight tabular-nums">
                {value}
            </p>
        </div>
    );
}
