import { Navigate, Route, Routes } from 'react-router';
import { AuthProvider, useAuth } from '@/auth/AuthContext';
import { RequireAuth } from '@/auth/RequireAuth';
import { AuthShell } from '@/components/AuthShell';
import { BootSplash } from '@/components/BootSplash';
import { ConsoleShell } from '@/components/ConsoleShell';
import { ThemeToaster } from '@/components/ThemeToaster';
import { useI18n } from '@/i18n/useI18n';
import { readLastOrgId } from '@/lib/activeOrg';
import { pathForSelectedPlan, readSelectedPlan } from '@/lib/planIntent';
import { AnalyticsPage } from '@/routes/AnalyticsPage';
import { ApiKeyDetailPage } from '@/routes/ApiKeyDetailPage';
import { ApiKeysPage } from '@/routes/ApiKeysPage';
import { ApiLogDetailPage } from '@/routes/ApiLogDetailPage';
import { ApiLogsPage } from '@/routes/ApiLogsPage';
import { AuditDetailPage } from '@/routes/AuditDetailPage';
import { AuditPage } from '@/routes/AuditPage';
import { BillingPage } from '@/routes/BillingPage';
import { ClaimPage } from '@/routes/ClaimPage';
import { DomainDetailPage } from '@/routes/DomainDetailPage';
import { DomainsPage } from '@/routes/DomainsPage';
import { ForgotPasswordPage } from '@/routes/ForgotPasswordPage';
import { ImportExportPage } from '@/routes/ImportExportPage';
import { InvitePage } from '@/routes/InvitePage';
import { LinkDetailPage } from '@/routes/LinkDetailPage';
import { LinksPage } from '@/routes/LinksPage';
import { LoginPage } from '@/routes/LoginPage';
import { MemberDetailPage } from '@/routes/MemberDetailPage';
import { MembersPage } from '@/routes/MembersPage';
import { NotFoundPage } from '@/routes/NotFoundPage';
import { OverviewPage } from '@/routes/OverviewPage';
import { QrDetailPage } from '@/routes/QrDetailPage';
import { QrPage } from '@/routes/QrPage';
import { RegisterPage } from '@/routes/RegisterPage';
import { ResetPasswordPage } from '@/routes/ResetPasswordPage';
import { SettingsPage } from '@/routes/SettingsPage';
import { VerifyEmailPage } from '@/routes/VerifyEmailPage';
import { ThemeProvider } from '@/theme/ThemeProvider';
import { ThemeSync } from '@/theme/ThemeSync';

function HomeRedirect() {
    const { t } = useI18n();
    const { user, loading } = useAuth();
    if (loading) {
        return <BootSplash label={t('app.loading')} />;
    }
    if (!user) {
        return <Navigate to="/login" replace />;
    }
    if (user.email_verified_at == null) {
        return <Navigate to="/verify-email" replace />;
    }
    const last = readLastOrgId();
    const org =
        (last ? user.organizations.find((o) => o.id === last) : undefined) ?? user.organizations[0];
    if (!org) {
        return <Navigate to="/login" replace />;
    }

    return <Navigate to={pathForSelectedPlan(org.id, readSelectedPlan())} replace />;
}

export function App() {
    return (
        <AuthProvider>
            <ThemeProvider>
                <ThemeSync />
                <ThemeToaster />
                <Routes>
                    <Route
                        path="/login"
                        element={
                            <AuthShell>
                                <LoginPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/register"
                        element={
                            <AuthShell>
                                <RegisterPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/forgot-password"
                        element={
                            <AuthShell>
                                <ForgotPasswordPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/reset-password"
                        element={
                            <AuthShell>
                                <ResetPasswordPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/verify-email"
                        element={
                            <AuthShell>
                                <VerifyEmailPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/claim/:token"
                        element={
                            <AuthShell>
                                <ClaimPage />
                            </AuthShell>
                        }
                    />
                    <Route
                        path="/invite/:token"
                        element={
                            <AuthShell>
                                <InvitePage />
                            </AuthShell>
                        }
                    />

                    <Route element={<RequireAuth />}>
                        <Route index element={<HomeRedirect />} />
                        <Route path=":orgId" element={<ConsoleShell />}>
                            <Route index element={<OverviewPage />} />
                            <Route path="links" element={<LinksPage />} />
                            <Route path="links/:linkId" element={<LinkDetailPage />} />
                            <Route path="analytics" element={<AnalyticsPage />} />
                            <Route path="qr" element={<QrPage />} />
                            <Route path="qr/:qrId" element={<QrDetailPage />} />
                            <Route path="domains" element={<DomainsPage />} />
                            <Route path="domains/:domainId" element={<DomainDetailPage />} />
                            <Route path="members" element={<MembersPage />} />
                            <Route path="members/:memberId" element={<MemberDetailPage />} />
                            <Route path="api-keys" element={<ApiKeysPage />} />
                            <Route path="api-keys/:apiKeyId" element={<ApiKeyDetailPage />} />
                            <Route path="api-logs" element={<ApiLogsPage />} />
                            <Route path="api-logs/:logId" element={<ApiLogDetailPage />} />
                            <Route path="audit" element={<AuditPage />} />
                            <Route path="audit/:logId" element={<AuditDetailPage />} />
                            <Route path="import-export" element={<ImportExportPage />} />
                            <Route path="billing" element={<BillingPage />} />
                            <Route path="settings" element={<SettingsPage />} />
                            <Route path="*" element={<NotFoundPage embedded />} />
                        </Route>
                    </Route>

                    <Route path="*" element={<NotFoundPage />} />
                </Routes>
            </ThemeProvider>
        </AuthProvider>
    );
}
