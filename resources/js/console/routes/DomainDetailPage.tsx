import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPost } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type DomainDetail = {
    id: string;
    hostname: string;
    status: string;
    verified_at: string | null;
    created_at: string;
    dns_records?: Array<{ type: string; name: string; value: string }> | null;
};

export function DomainDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { domainId } = useParams<{ domainId: string }>();
    const navigate = useNavigate();
    const [domain, setDomain] = useState<DomainDetail | null>(null);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [verifying, setVerifying] = useState(false);

    const load = useCallback(() => {
        if (!orgId || !domainId) return;
        void apiGet<{ domain: DomainDetail }>(
            `/api/v1/organizations/${orgId}/domains/${domainId}`,
        ).then((data) => setDomain(data.domain));
    }, [orgId, domainId]);

    useEffect(() => {
        load();
    }, [load]);

    if (!org || !orgId || !domainId) return null;
    if (!domain) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    const onVerify = () => {
        setVerifying(true);
        void apiPost(`/api/v1/organizations/${orgId}/domains/${domainId}/verify`)
            .then(() => {
                toast.success(t('console.domains.verify_success_title'), {
                    description: t('console.domains.verify_success_description'),
                });
                load();
            })
            .catch((err: unknown) =>
                toast.error(t('console.domains.verify_error'), {
                    description:
                        err instanceof ApiError
                            ? err.message
                            : t('console.domains.verify_error_description'),
                }),
            )
            .finally(() => setVerifying(false));
    };

    return (
        <section className="space-y-6">
            <PageHeader
                title={domain.hostname}
                description={domain.status}
                action={
                    <>
                        <Button asChild variant="outline">
                            <Link to={`/${orgId}/domains`}>{t('common.back')}</Link>
                        </Button>
                        {!domain.verified_at ? (
                            <Button
                                type="button"
                                variant="outline"
                                loading={verifying}
                                onClick={onVerify}
                            >
                                {verifying ? t('common.verifying') : t('console.domains.verify')}
                            </Button>
                        ) : null}
                        <Button
                            type="button"
                            variant="danger"
                            onClick={() => setConfirmDelete(true)}
                        >
                            {t('common.delete')}
                        </Button>
                    </>
                }
            />

            <div className="console-panel space-y-2 p-5 text-sm">
                <p>
                    {domain.verified_at
                        ? `${t('console.domains.verified')} ${formatWhen(domain.verified_at)}`
                        : t('console.domains.awaiting_dns')}
                </p>
                <p className="text-ink-soft">
                    {t('common.detail.created')} {formatWhen(domain.created_at)}
                </p>
            </div>

            {domain.dns_records && domain.dns_records.length > 0 ? (
                <div className="overflow-x-auto console-panel">
                    <table className="min-w-full text-left text-sm">
                        <thead className="border-b border-mist/60 text-xs text-ink-soft">
                            <tr>
                                <th className="px-3 py-2">{t('console.domains.col.type')}</th>
                                <th className="px-3 py-2">{t('console.domains.col.name')}</th>
                                <th className="px-3 py-2">{t('console.domains.col.value')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {domain.dns_records.map((record) => (
                                <tr
                                    key={`${record.type}-${record.name}-${record.value}`}
                                    className="border-t border-mist/50"
                                >
                                    <td className="px-3 py-2 font-mono text-xs">{record.type}</td>
                                    <td className="px-3 py-2 font-mono text-xs">{record.name}</td>
                                    <td className="break-all px-3 py-2 font-mono text-xs">
                                        {record.value}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : null}

            <ConfirmDelete
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                onConfirm={async () => {
                    try {
                        await apiDelete(`/api/v1/organizations/${orgId}/domains/${domainId}`);
                        toast.success(t('console.domains.remove_success_title'), {
                            description: t('console.domains.remove_success_description'),
                        });
                        navigate(`/${orgId}/domains`);
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
