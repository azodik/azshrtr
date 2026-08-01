import { type FormEvent, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPatch } from '@/lib/api';
import { absoluteShortUrl, copyText, formatWhen } from '@/lib/format';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type LinkDetail = {
    id: string;
    code: string;
    destination_url: string;
    title: string | null;
    click_count: number;
    is_disabled?: boolean;
    created_at: string;
    updated_at?: string;
    short_url?: string;
};

export function LinkDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { linkId } = useParams<{ linkId: string }>();
    const navigate = useNavigate();
    const [link, setLink] = useState<LinkDetail | null>(null);
    const [title, setTitle] = useState('');
    const [destination, setDestination] = useState('');
    const [pending, setPending] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);

    useEffect(() => {
        if (!orgId || !linkId) return;
        void apiGet<{ link: LinkDetail }>(`/api/v1/organizations/${orgId}/links/${linkId}`)
            .then((data) => {
                setLink(data.link);
                setTitle(data.link.title ?? '');
                setDestination(data.link.destination_url);
            })
            .catch(() => setLink(null));
    }, [orgId, linkId]);

    if (!org || !orgId || !linkId) return null;

    if (link === null) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    const short = link.short_url ?? absoluteShortUrl(link.code);

    const onSave = async (event: FormEvent) => {
        event.preventDefault();
        setPending(true);
        try {
            const data = await apiPatch<{ link: LinkDetail }>(
                `/api/v1/organizations/${orgId}/links/${linkId}`,
                {
                    title: title.trim() === '' ? null : title.trim(),
                    destination_url: destination,
                },
            );
            setLink(data.link);
            toast.success(t('console.links.save_success_title'), {
                description: t('console.links.save_success_description'),
            });
        } catch (err) {
            toast.error(t('console.links.save_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.links.save_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const onCopy = async () => {
        const ok = await copyText(short);
        if (ok) {
            toast.success(t('common.copied'), { description: t('common.copied_description') });
        } else {
            toast.error(t('console.links.copy_failed'), {
                description: t('console.links.copy_failed_description'),
            });
        }
    };

    return (
        <section className="space-y-6">
            <PageHeader
                title={`/${link.code}`}
                description={link.title ?? link.destination_url}
                action={
                    <>
                        <Button asChild variant="outline">
                            <Link to={`/${orgId}/links`}>{t('common.back')}</Link>
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

            <div className="console-panel space-y-3 p-5 text-sm">
                <p>
                    <span className="text-ink-soft">{t('console.links.destination_url')}: </span>
                    <a
                        href={link.destination_url}
                        className="text-teal hover:underline"
                        target="_blank"
                        rel="noreferrer"
                    >
                        {link.destination_url}
                    </a>
                </p>
                <p>
                    <span className="text-ink-soft">{t('console.links.copy')}: </span>
                    <button
                        type="button"
                        className="text-teal hover:underline"
                        onClick={() => void onCopy()}
                    >
                        {short}
                    </button>
                </p>
                <p className="text-ink-soft">
                    {t(
                        link.click_count === 1
                            ? 'console.links.clicks_one'
                            : 'console.links.clicks_other',
                        { count: link.click_count },
                    )}{' '}
                    · {t('common.detail.created')} {formatWhen(link.created_at)}
                </p>
            </div>

            <form onSubmit={onSave} className="console-panel space-y-4 p-5">
                <div className="space-y-1.5">
                    <Label htmlFor="edit-title">{t('console.links.title_label')}</Label>
                    <Input
                        id="edit-title"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                    />
                </div>
                <div className="space-y-1.5">
                    <Label htmlFor="edit-url">{t('console.links.destination_url')}</Label>
                    <Input
                        id="edit-url"
                        type="url"
                        required
                        value={destination}
                        onChange={(e) => setDestination(e.target.value)}
                    />
                </div>
                <Button type="submit" disabled={pending}>
                    {pending ? t('common.saving') : t('common.save')}
                </Button>
            </form>

            <ConfirmDelete
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                onConfirm={async () => {
                    try {
                        await apiDelete(`/api/v1/organizations/${orgId}/links/${linkId}`);
                        toast.success(t('console.links.delete_success_title'), {
                            description: t('console.links.delete_success_description'),
                        });
                        navigate(`/${orgId}/links`);
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
