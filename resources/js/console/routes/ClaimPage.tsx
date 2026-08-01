import { useEffect, useState } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router';
import { useAuth } from '@/auth/AuthContext';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost } from '@/lib/api';

export function ClaimPage() {
    const { t } = useI18n();
    const { token } = useParams();
    const { user, loading } = useAuth();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (loading || !user || !token) {
            return;
        }
        const org = user.organizations[0];
        if (!org) {
            setError(t('auth.claim.no_organization'));
            return;
        }
        void (async () => {
            try {
                await apiPost('/api/v1/links/claim', {
                    token,
                    organization_id: org.id,
                });
                navigate(`/${org.id}/links`);
            } catch (err) {
                setError(err instanceof ApiError ? err.message : t('auth.claim.error'));
            }
        })();
    }, [loading, user, token, navigate, t]);

    if (!loading && !user) {
        return <Navigate to="/login" replace state={{ from: `/claim/${token}` }} />;
    }

    return (
        <div className="flex min-h-screen items-center justify-center text-ink-soft">
            {error ?? t('auth.claim.claiming')}
        </div>
    );
}
