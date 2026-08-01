import { Navigate, Outlet, useLocation } from 'react-router';
import { useAuth } from '@/auth/AuthContext';
import { BootSplash } from '@/components/BootSplash';
import { useI18n } from '@/i18n/useI18n';

export function RequireAuth() {
    const { t } = useI18n();
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <BootSplash label={t('common.loading')} />;
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    if (user.email_verified_at == null) {
        return <Navigate to="/verify-email" replace />;
    }

    return <Outlet />;
}
