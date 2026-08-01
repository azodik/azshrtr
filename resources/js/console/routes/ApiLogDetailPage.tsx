import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { apiGet } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type ApiLogDetail = {
    id: number;
    method: string;
    path: string;
    status: number;
    latency_ms: number;
    created_at: string;
    ip_address?: string | null;
    user_agent?: string | null;
    api_key?: { id: string; name: string; prefix: string; last_four: string } | null;
};

export function ApiLogDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { logId } = useParams<{ logId: string }>();
    const [log, setLog] = useState<ApiLogDetail | null>(null);

    useEffect(() => {
        if (!orgId || !logId) return;
        void apiGet<{ log: ApiLogDetail }>(
            `/api/v1/organizations/${orgId}/api-request-logs/${logId}`,
        ).then((data) => setLog(data.log));
    }, [orgId, logId]);

    if (!org || !orgId || !logId) return null;

    if (!log) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    return (
        <section className="space-y-6">
            <PageHeader
                title={`${log.method} ${log.path}`}
                description={formatWhen(log.created_at)}
                action={
                    <Button asChild variant="outline">
                        <Link to={`/${orgId}/api-logs`}>{t('common.back')}</Link>
                    </Button>
                }
            />

            <div className="console-panel space-y-3 p-5 text-sm">
                <p className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{log.method}</Badge>
                    <span className="font-mono text-xs">{log.path}</span>
                </p>
                <p>
                    <span className="text-ink-soft">{t('console.api_logs.col.status')}: </span>
                    {log.status}
                </p>
                <p>
                    <span className="text-ink-soft">{t('console.api_logs.col.latency')}: </span>
                    {t('console.api_logs.latency_ms', { ms: log.latency_ms })}
                </p>
                <p>
                    <span className="text-ink-soft">{t('console.api_logs.col.key')}: </span>
                    {log.api_key
                        ? t('console.api_logs.key_label', {
                              name: log.api_key.name,
                              prefix: log.api_key.prefix,
                              last_four: log.api_key.last_four,
                          })
                        : t('common.em_dash')}
                </p>
                <p className="text-ink-soft">
                    {t('common.detail.created')} {formatWhen(log.created_at)}
                </p>
            </div>
        </section>
    );
}
