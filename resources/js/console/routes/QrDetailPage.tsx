import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet } from '@/lib/api';
import { downloadFromApi } from '@/lib/download';
import { formatWhen } from '@/lib/format';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type QrDetail = {
    id: string;
    link_id: string | null;
    content: string;
    size: number;
    format: string;
    created_at: string;
    link?: { id: string; code: string; title: string | null; destination_url: string } | null;
};

export function QrDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { qrId } = useParams<{ qrId: string }>();
    const navigate = useNavigate();
    const [qr, setQr] = useState<QrDetail | null>(null);
    const [svg, setSvg] = useState<string | null>(null);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [downloading, setDownloading] = useState(false);

    useEffect(() => {
        if (!orgId || !qrId) return;
        void apiGet<{ qr: QrDetail; svg: string }>(
            `/api/v1/organizations/${orgId}/qr/${qrId}`,
        ).then((data) => {
            setQr(data.qr);
            setSvg(data.svg);
        });
    }, [orgId, qrId]);

    if (!org || !orgId || !qrId) return null;

    if (!qr || svg === null) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    const onDownload = async () => {
        setDownloading(true);
        try {
            await downloadFromApi(
                `/api/v1/organizations/${orgId}/qr/${qrId}/download`,
                `qr-${qrId}.svg`,
            );
        } finally {
            setDownloading(false);
        }
    };

    return (
        <section className="space-y-6">
            <PageHeader
                title={
                    qr.link
                        ? t('console.qr.history_link', { code: qr.link.code })
                        : t('console.qr.history_custom')
                }
                description={qr.content}
                action={
                    <>
                        <Button asChild variant="outline">
                            <Link to={`/${orgId}/qr`}>{t('common.back')}</Link>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={downloading}
                            onClick={() => void onDownload()}
                        >
                            {downloading ? t('common.downloading') : t('console.qr.download_svg')}
                        </Button>
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

            <div className="console-panel p-5">
                <div className="mx-auto flex aspect-square max-w-[280px] items-center justify-center rounded-md bg-paper p-4">
                    <img
                        src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`}
                        alt={t('console.qr.preview_alt')}
                        className="h-full w-full object-contain"
                    />
                </div>
                <p className="mt-4 break-all text-xs text-ink-soft">
                    {t('console.qr.meta', {
                        size: qr.size,
                        format: qr.format.toUpperCase(),
                        content: qr.content,
                    })}
                </p>
                <p className="mt-2 text-xs text-ink-soft/55">
                    {t('console.qr.history_created', { when: formatWhen(qr.created_at) })}
                </p>
            </div>

            <ConfirmDelete
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                onConfirm={async () => {
                    try {
                        await apiDelete(`/api/v1/organizations/${orgId}/qr/${qrId}`);
                        toast.success(t('console.qr.delete_success_title'), {
                            description: t('console.qr.delete_success_description'),
                        });
                        navigate(`/${orgId}/qr`);
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
