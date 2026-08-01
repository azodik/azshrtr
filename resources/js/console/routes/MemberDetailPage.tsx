import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPatch } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type MemberDetail = {
    id: string;
    role: string;
    status: string;
    joined_at: string | null;
    created_at: string | null;
    user: { id: number; name: string; email: string } | null;
};

export function MemberDetailPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const { memberId } = useParams<{ memberId: string }>();
    const navigate = useNavigate();
    const [member, setMember] = useState<MemberDetail | null>(null);
    const [canManage, setCanManage] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);

    const load = useCallback(() => {
        if (!orgId || !memberId) return;
        void apiGet<{ member: MemberDetail; can_manage: boolean }>(
            `/api/v1/organizations/${orgId}/members/${memberId}`,
        ).then((data) => {
            setMember(data.member);
            setCanManage(data.can_manage);
        });
    }, [orgId, memberId]);

    useEffect(() => {
        load();
    }, [load]);

    if (!org || !orgId || !memberId) return null;

    if (!member) {
        return <p className="text-sm text-ink-soft">{t('common.detail.loading')}</p>;
    }

    const roleLabel = (value: string): string =>
        value === 'owner' || value === 'admin' || value === 'member'
            ? t(`common.role_${value}`)
            : value;

    const changeRole = async (nextRole: string) => {
        try {
            await apiPatch(`/api/v1/organizations/${orgId}/members/${memberId}`, {
                role: nextRole,
            });
            toast.success(t('console.members.role_updated_title'), {
                description: t('console.members.role_updated_description'),
            });
            load();
        } catch (err) {
            toast.error(t('console.members.role_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.members.role_error_description'),
            });
        }
    };

    return (
        <section className="space-y-6">
            <PageHeader
                title={member.user?.name ?? t('common.unknown')}
                description={member.user?.email ?? undefined}
                action={
                    <>
                        <Button asChild variant="outline">
                            <Link to={`/${orgId}/members`}>{t('common.back')}</Link>
                        </Button>
                        {canManage && member.role !== 'owner' ? (
                            <Button
                                type="button"
                                variant="danger"
                                onClick={() => setConfirmDelete(true)}
                            >
                                {t('console.members.remove')}
                            </Button>
                        ) : null}
                    </>
                }
            />

            <div className="console-panel space-y-3 p-5 text-sm">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-ink-soft">{t('common.role')}:</span>
                    {member.role === 'owner' || !canManage ? (
                        <Badge variant="secondary">{roleLabel(member.role)}</Badge>
                    ) : (
                        <Select
                            value={member.role}
                            onValueChange={(value) => void changeRole(value)}
                        >
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="admin">{t('common.role_admin')}</SelectItem>
                                <SelectItem value="member">{t('common.role_member')}</SelectItem>
                            </SelectContent>
                        </Select>
                    )}
                </div>
                <p>
                    <span className="text-ink-soft">{t('common.sort.status')}: </span>
                    {member.status}
                </p>
                <p className="text-ink-soft">
                    {[
                        member.joined_at
                            ? `${t('common.sort.joined_at')} ${formatWhen(member.joined_at)}`
                            : null,
                        member.created_at
                            ? `${t('common.detail.created')} ${formatWhen(member.created_at)}`
                            : null,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </div>

            <ConfirmDelete
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                description={t('console.members.remove_confirm')}
                confirmLabel={t('console.members.remove')}
                onConfirm={async () => {
                    try {
                        await apiDelete(`/api/v1/organizations/${orgId}/members/${memberId}`);
                        toast.success(t('console.members.remove_success_title'), {
                            description: t('console.members.remove_success_description'),
                        });
                        navigate(`/${orgId}/members`);
                    } catch (err) {
                        toast.error(t('console.members.remove_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.members.remove_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
