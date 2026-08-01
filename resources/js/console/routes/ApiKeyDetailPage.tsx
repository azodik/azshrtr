import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type ApiKeyDetail = {
    id: string;
    name: string;
    prefix: string;
    last_four: string;
    scopes: string[];
    last_used_at: string | null;
    revoked_at: string | null;
    created_at: string;
};

export function ApiKeyDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { apiKeyId } = useParams<{ apiKeyId: string }>();
    const navigate = useNavigate();
    const [key, setKey] = useState<ApiKeyDetail | null>(null);
    const [confirmRevoke, setConfirmRevoke] = useState(false);

    useEffect(() => {
        if (!orgId || !apiKeyId) return;
        void apiGet<{ api_key: ApiKeyDetail }>(
            `/api/v1/organizations/${orgId}/api-keys/${apiKeyId}`,
        ).then((data) => setKey(data.api_key));
    }, [orgId, apiKeyId]);

    if (!org || !orgId || !apiKeyId) return null;

    if (!key) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    return (
        <section className="space-y-6">
            <PageHeader
                title={key.name}
                description={`${key.prefix}…${key.last_four}`}
                action={
                    <>
                        <Button asChild variant="outline">
                            <Link to={`/${orgId}/api-keys`}>{t('common.back')}</Link>
                        </Button>
                        {!key.revoked_at && (org.role === 'owner' || org.role === 'admin') ? (
                            <Button
                                type="button"
                                variant="danger"
                                onClick={() => setConfirmRevoke(true)}
                            >
                                {t('console.api_keys.revoke')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            <div className="console-panel space-y-2 p-5 text-sm">
                <p className="font-mono text-ink-soft">
                    {key.prefix}…{key.last_four}
                    {key.revoked_at ? (
                        <span className="ml-2 text-danger">{t('console.api_keys.revoked')}</span>
                    ) : null}
                </p>
                {key.scopes.length > 0 ? (
                    <p className="text-ink-soft">{key.scopes.join(', ')}</p>
                ) : null}
                <p className="text-ink-soft">
                    {t('common.detail.created')} {formatWhen(key.created_at)}
                    {key.last_used_at
                        ? ` · ${t('common.sort.last_used_at')} ${formatWhen(key.last_used_at)}`
                        : null}
                </p>
            </div>

            <ConfirmDelete
                open={confirmRevoke}
                onOpenChange={setConfirmRevoke}
                confirmLabel={t('console.api_keys.revoke')}
                onConfirm={async () => {
                    try {
                        await apiDelete(`/api/v1/organizations/${orgId}/api-keys/${apiKeyId}`);
                        toast.success(t('console.api_keys.revoke_success_title'), {
                            description: t('console.api_keys.revoke_success_description'),
                        });
                        navigate(`/${orgId}/api-keys`);
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
