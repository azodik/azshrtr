import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { apiGet } from '@/lib/api';
import { auditActionKey, isAuditActionValue } from '@/lib/auditActions';
import { formatWhen } from '@/lib/format';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type AuditLogDetail = {
    id: string;
    action: string;
    created_at: string;
    resource_type?: string | null;
    resource_id?: string | null;
    ip_address?: string | null;
    metadata?: Record<string, unknown> | null;
    actor?: { id: number; name: string; email: string } | null;
};

export function AuditDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { logId } = useParams<{ logId: string }>();
    const [log, setLog] = useState<AuditLogDetail | null>(null);

    useEffect(() => {
        if (!orgId || !logId) return;
        void apiGet<{ log: AuditLogDetail }>(
            `/api/v1/organizations/${orgId}/audit-logs/${logId}`,
        ).then((data) => setLog(data.log));
    }, [orgId, logId]);

    if (!org || !orgId || !logId) return null;

    if (!log) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    const actionLabel = isAuditActionValue(log.action)
        ? t(auditActionKey(log.action))
        : log.action.replaceAll('.', ' · ').replaceAll('_', ' ');

    return (
        <section className="space-y-6">
            <PageHeader
                title={actionLabel}
                description={formatWhen(log.created_at)}
                action={
                    <Button asChild variant="outline">
                        <Link to={`/${orgId}/audit`}>{t('common.back')}</Link>
                    </Button>
                }
            />

            <div className="console-panel space-y-3 p-5 text-sm">
                <p>
                    <span className="text-ink-soft">{t('common.sort.action')}: </span>
                    {actionLabel}
                </p>
                {log.actor ? (
                    <p>
                        <span className="text-ink-soft">{t('common.email')}: </span>
                        {log.actor.name} ({log.actor.email})
                    </p>
                ) : null}
                {log.resource_type ? (
                    <p>
                        <span className="text-ink-soft">{t('common.sort.resource_type')}: </span>
                        {log.resource_type}
                        {log.resource_id ? ` · ${log.resource_id}` : null}
                    </p>
                ) : null}
                {log.ip_address ? (
                    <p>
                        <span className="text-ink-soft">{t('common.ip')}: </span>
                        {log.ip_address}
                    </p>
                ) : null}
                <p className="text-ink-soft">
                    {t('common.detail.created')} {formatWhen(log.created_at)}
                </p>
                {log.metadata && Object.keys(log.metadata).length > 0 ? (
                    <pre className="overflow-x-auto rounded-md bg-fog p-3 text-xs">
                        {JSON.stringify(log.metadata, null, 2)}
                    </pre>
                ) : null}
            </div>
        </section>
    );
}
