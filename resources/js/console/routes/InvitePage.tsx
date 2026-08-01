import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { type AuthOrganization, useAuth } from '@/auth/AuthContext';
import { BootSplash } from '@/components/BootSplash';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useI18n } from '@/i18n/useI18n';
import { writeLastOrgId } from '@/lib/activeOrg';
import { ApiError, apiPost } from '@/lib/api';
import { toast } from '@/lib/toast';

export function InvitePage() {
    const { t } = useI18n();
    const { token } = useParams<{ token: string }>();
    const { user, setUser, loading } = useAuth();
    const navigate = useNavigate();
    const [pending, setPending] = useState(false);
    const [acceptedOrgId, setAcceptedOrgId] = useState<string | null>(null);

    useEffect(() => {
        if (!loading && !user && token) {
            const next = `/invite/${token}`;
            navigate(`/login?next=${encodeURIComponent(next)}`, { replace: true });
        }
    }, [loading, user, token, navigate]);

    if (loading) {
        return <BootSplash label={t('auth.invite.loading')} />;
    }

    if (!user || !token) {
        return null;
    }

    const accept = async () => {
        setPending(true);
        try {
            const res = await apiPost<{
                organization: { id: string; role: string };
                organizations: AuthOrganization[];
            }>('/api/v1/invitations/accept', { token });
            setUser({ ...user, organizations: res.organizations });
            writeLastOrgId(res.organization.id);
            setAcceptedOrgId(res.organization.id);
            toast.success(t('auth.invite.success_title'), {
                description: t('auth.invite.success_description'),
            });
        } catch (err) {
            toast.error(t('auth.invite.error'), {
                description:
                    err instanceof ApiError ? err.message : t('auth.invite.error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-paper px-4">
            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle>{t('auth.invite.title')}</CardTitle>
                    <CardDescription>
                        {t('auth.invite.description', { email: user.email })}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {acceptedOrgId ? (
                        <>
                            <p className="text-sm text-ink-soft">{t('auth.invite.success')}</p>
                            <Button asChild className="w-full">
                                <Link to={`/${acceptedOrgId}`}>
                                    {t('auth.invite.go_to_console')}
                                </Link>
                            </Button>
                        </>
                    ) : (
                        <Button
                            type="button"
                            className="w-full"
                            disabled={pending}
                            onClick={() => void accept()}
                        >
                            {pending ? t('auth.invite.accepting') : t('auth.invite.accept')}
                        </Button>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
