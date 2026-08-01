import { Monitor, Moon, Sun } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import { useAuth } from '@/auth/AuthContext';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { PageHeader } from '@/components/PageHeader';
import { PasswordInput } from '@/components/PasswordInput';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiDelete, apiGet, apiPatch, apiPost, apiPut } from '@/lib/api';
import type { ThemePreference } from '@/lib/theme';
import { toast } from '@/lib/toast';
import { creationOptionsFromServer, credentialToJson, toPublicKeyCredential } from '@/lib/webauthn';
import { useTheme } from '@/theme/ThemeProvider';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type MfaStatus = {
    mfa_enabled: boolean;
    recovery_codes_remaining: number;
};

type PasskeyRow = {
    id: string;
    name: string | null;
    last_used_at: string | null;
    created_at: string;
};

const THEME_OPTIONS: Array<{
    value: ThemePreference;
    labelKey: string;
    icon: typeof Sun;
}> = [
    { value: 'light', labelKey: 'console.theme.light', icon: Sun },
    { value: 'dark', labelKey: 'console.theme.dark', icon: Moon },
    { value: 'system', labelKey: 'console.theme.system', icon: Monitor },
];

export function SettingsPage() {
    const { t } = useI18n();
    const { user, setUser, refresh } = useAuth();
    const { preference, setPreference } = useTheme();
    const org = useActiveOrg();
    const [name, setName] = useState(user?.name ?? '');
    const [profilePending, setProfilePending] = useState(false);

    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [passwordPending, setPasswordPending] = useState(false);

    const [mfa, setMfa] = useState<MfaStatus | null>(null);
    const [setupSecret, setSetupSecret] = useState<string | null>(null);
    const [setupSvg, setSetupSvg] = useState<string | null>(null);
    const [mfaCode, setMfaCode] = useState('');
    const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
    const [mfaSetupPending, setMfaSetupPending] = useState(false);
    const [mfaConfirmPending, setMfaConfirmPending] = useState(false);
    const [mfaDisablePending, setMfaDisablePending] = useState(false);
    const [codesCopied, setCodesCopied] = useState(false);

    const [passkeys, setPasskeys] = useState<PasskeyRow[]>([]);
    const [passkeyDeleteId, setPasskeyDeleteId] = useState<string | null>(null);
    const [passkeyPending, setPasskeyPending] = useState(false);
    const [themePending, setThemePending] = useState(false);
    const [passkeyName, setPasskeyName] = useState(() =>
        t('console.settings.passkeys.default_name'),
    );

    useEffect(() => {
        if (user) {
            setName(user.name);
        }
    }, [user]);

    useEffect(() => {
        void apiGet<MfaStatus>('/api/v1/auth/mfa').then(setMfa);
        void apiGet<{ passkeys: PasskeyRow[] }>('/api/v1/auth/passkeys').then((d) =>
            setPasskeys(d.passkeys),
        );
    }, []);

    if (!user || !org) {
        return null;
    }

    const saveProfile = async (event: FormEvent) => {
        event.preventDefault();
        setProfilePending(true);
        try {
            const data = await apiPatch<{ user: typeof user }>('/api/v1/auth/profile', { name });
            setUser(data.user);
            toast.success(t('console.settings.profile.updated'), {
                description: t('console.settings.profile.updated_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.profile.error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.profile.error_description'),
            });
        } finally {
            setProfilePending(false);
        }
    };

    const savePassword = async (event: FormEvent) => {
        event.preventDefault();
        setPasswordPending(true);
        try {
            await apiPut('/api/v1/auth/password', {
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            });
            setCurrentPassword('');
            setPassword('');
            setPasswordConfirmation('');
            toast.success(t('console.settings.password.updated'), {
                description: t('console.settings.password.updated_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.password.error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.password.error_description'),
            });
        } finally {
            setPasswordPending(false);
        }
    };

    const beginMfa = async () => {
        setMfaSetupPending(true);
        try {
            const data = await apiPost<{ secret: string; qr_svg: string }>(
                '/api/v1/auth/mfa/setup',
            );
            setSetupSecret(data.secret);
            setSetupSvg(data.qr_svg);
            setRecoveryCodes(null);
        } catch (err) {
            toast.error(t('console.settings.mfa.setup_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.mfa.setup_error_description'),
            });
        } finally {
            setMfaSetupPending(false);
        }
    };

    const confirmMfa = async (event: FormEvent) => {
        event.preventDefault();
        setMfaConfirmPending(true);
        try {
            const data = await apiPost<{ recovery_codes: string[] }>('/api/v1/auth/mfa/confirm', {
                code: mfaCode,
            });
            setRecoveryCodes(data.recovery_codes);
            setSetupSecret(null);
            setSetupSvg(null);
            setMfaCode('');
            setCodesCopied(false);
            setMfa(await apiGet<MfaStatus>('/api/v1/auth/mfa'));
            void refresh();
            toast.success(t('console.settings.mfa.enabled_message'), {
                description: t('console.settings.mfa.enabled_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.mfa.confirm_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.mfa.confirm_error_description'),
            });
        } finally {
            setMfaConfirmPending(false);
        }
    };

    const disableMfa = async (event: FormEvent) => {
        event.preventDefault();
        setMfaDisablePending(true);
        try {
            await apiPost('/api/v1/auth/mfa/disable', { code: mfaCode });
            setMfaCode('');
            setRecoveryCodes(null);
            setMfa(await apiGet<MfaStatus>('/api/v1/auth/mfa'));
            void refresh();
            toast.success(t('console.settings.mfa.disabled_message'), {
                description: t('console.settings.mfa.disabled_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.mfa.disable_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.mfa.disable_error_description'),
            });
        } finally {
            setMfaDisablePending(false);
        }
    };

    const downloadRecoveryCodes = () => {
        if (!recoveryCodes?.length) {
            return;
        }
        const body = [
            `${t('console.settings.mfa.recovery_codes')}`,
            '',
            ...recoveryCodes,
            '',
            t('console.settings.mfa.codes_file_footer', { app: 'Azshrtr' }),
        ].join('\n');
        const blob = new Blob([body], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'azshrtr-recovery-codes.txt';
        anchor.click();
        URL.revokeObjectURL(url);
    };

    const copyRecoveryCodes = async () => {
        if (!recoveryCodes?.length) {
            return;
        }
        try {
            await navigator.clipboard.writeText(recoveryCodes.join('\n'));
            setCodesCopied(true);
            toast.success(t('common.copied'), { description: t('common.copied_description') });
        } catch {
            setCodesCopied(false);
            toast.error(t('common.copy_failed'), {
                description: t('common.copy_failed_description'),
            });
        }
    };

    const registerPasskey = async () => {
        if (!window.PublicKeyCredential) {
            toast.error(t('console.settings.passkeys.unsupported'), {
                description: t('console.settings.passkeys.unsupported_description'),
            });
            return;
        }
        setPasskeyPending(true);
        try {
            const options = await apiPost<Parameters<typeof creationOptionsFromServer>[0]>(
                '/api/v1/auth/passkeys/register/options',
            );
            const credential = toPublicKeyCredential(
                await navigator.credentials.create(creationOptionsFromServer(options)),
            );
            if (!credential) {
                toast.error(t('console.settings.passkeys.cancelled'), {
                    description: t('console.settings.passkeys.cancelled_description'),
                });
                return;
            }
            await apiPost('/api/v1/auth/passkeys/register', {
                name: passkeyName,
                credential: credentialToJson(credential),
            });
            const list = await apiGet<{ passkeys: PasskeyRow[] }>('/api/v1/auth/passkeys');
            setPasskeys(list.passkeys);
            toast.success(t('console.settings.passkeys.added_title'), {
                description: t('console.settings.passkeys.added_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.passkeys.error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.passkeys.error_description'),
            });
        } finally {
            setPasskeyPending(false);
        }
    };

    const saveTheme = async (value: ThemePreference) => {
        setPreference(value);
        setThemePending(true);
        try {
            const data = await apiPatch<{ user: typeof user }>('/api/v1/auth/profile', {
                theme_preference: value,
            });
            setUser(data.user);
            toast.success(t('console.settings.theme.updated'), {
                description: t('console.settings.theme.updated_description'),
            });
        } catch (err) {
            toast.error(t('console.settings.profile.error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.settings.profile.error_description'),
            });
        } finally {
            setThemePending(false);
        }
    };

    return (
        <section className="space-y-8">
            <PageHeader
                title={t('console.settings.title')}
                description={t('console.settings.description')}
            />

            <div className="console-panel space-y-3 p-5">
                <div>
                    <h2 className="font-display text-lg font-semibold">
                        {t('console.settings.theme.title')}
                    </h2>
                    <p className="mt-1 text-sm text-ink-soft">
                        {t('console.settings.theme.description')}
                    </p>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    {THEME_OPTIONS.map((option) => {
                        const Icon = option.icon;
                        const selected = preference === option.value;
                        return (
                            <Button
                                key={option.value}
                                type="button"
                                variant={selected ? 'default' : 'outline'}
                                className="justify-start sm:min-w-[8.5rem]"
                                loading={themePending && selected}
                                disabled={themePending}
                                onClick={() => {
                                    void saveTheme(option.value);
                                }}
                            >
                                <Icon className="size-3.5" />
                                {themePending && selected ? t('common.saving') : t(option.labelKey)}
                            </Button>
                        );
                    })}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <form onSubmit={saveProfile} className="space-y-3 console-panel p-5">
                    <h2 className="font-display text-lg font-semibold">
                        {t('console.settings.profile.title')}
                    </h2>
                    <label className="block text-sm font-medium">
                        {t('console.settings.profile.name')}
                        <input
                            required
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            className="mt-1 w-full rounded-[var(--radius-control)] border border-mist bg-paper-elevated px-3 py-2 text-sm outline-none focus:border-teal focus:ring-2 focus:ring-teal/25"
                        />
                    </label>
                    <p className="text-sm text-ink-soft">
                        {t('console.settings.profile.email', { email: user.email })}
                    </p>
                    <Button type="submit" loading={profilePending}>
                        {profilePending ? t('common.saving') : t('console.settings.profile.save')}
                    </Button>
                </form>

                <form onSubmit={savePassword} className="space-y-3 console-panel p-5">
                    <h2 className="font-display text-lg font-semibold">
                        {t('console.settings.password.title')}
                    </h2>
                    <PasswordInput
                        label={t('console.settings.password.current')}
                        required
                        autoComplete="current-password"
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                    />
                    <PasswordInput
                        label={t('console.settings.password.new')}
                        required
                        autoComplete="new-password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                    />
                    <PasswordInput
                        label={t('console.settings.password.confirm')}
                        required
                        autoComplete="new-password"
                        value={passwordConfirmation}
                        onChange={(e) => setPasswordConfirmation(e.target.value)}
                    />
                    <Button type="submit" loading={passwordPending}>
                        {passwordPending
                            ? t('common.saving')
                            : t('console.settings.password.update')}
                    </Button>
                </form>
            </div>

            <div className="space-y-3 console-panel p-5">
                <h2 className="font-display text-lg font-semibold">
                    {t('console.settings.mfa.title')}
                </h2>
                <p className="text-sm text-ink-soft">
                    {mfa?.mfa_enabled
                        ? t('console.settings.mfa.enabled', {
                              count: mfa.recovery_codes_remaining,
                          })
                        : t('console.settings.mfa.disabled_hint')}
                </p>
                {!mfa?.mfa_enabled && !setupSecret ? (
                    <Button type="button" loading={mfaSetupPending} onClick={() => void beginMfa()}>
                        {mfaSetupPending
                            ? t('console.settings.mfa.setup_pending')
                            : t('console.settings.mfa.setup')}
                    </Button>
                ) : null}

                {setupSecret && setupSvg ? (
                    <form onSubmit={confirmMfa} className="space-y-3">
                        <img
                            src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(setupSvg)}`}
                            alt={t('console.settings.mfa.qr_alt')}
                            className="h-48 w-48 rounded-md bg-paper p-2"
                        />
                        <p className="break-all font-mono text-xs text-ink-soft">{setupSecret}</p>
                        <input
                            required
                            value={mfaCode}
                            onChange={(e) => setMfaCode(e.target.value)}
                            placeholder={t('console.settings.mfa.code_placeholder')}
                            disabled={mfaConfirmPending}
                            className="w-full max-w-xs rounded-md border border-mist bg-paper px-3 py-2 disabled:opacity-60"
                        />
                        <Button type="submit" loading={mfaConfirmPending}>
                            {mfaConfirmPending
                                ? t('console.settings.mfa.confirm_pending')
                                : t('console.settings.mfa.confirm')}
                        </Button>
                    </form>
                ) : null}

                {mfa?.mfa_enabled ? (
                    <form onSubmit={disableMfa} className="space-y-2">
                        <p className="text-xs text-ink-soft">
                            {t('console.settings.mfa.disable_hint')}
                        </p>
                        <div className="flex flex-wrap items-end gap-2">
                            <input
                                required
                                value={mfaCode}
                                onChange={(e) => setMfaCode(e.target.value)}
                                placeholder={t('console.settings.mfa.disable_placeholder')}
                                disabled={mfaDisablePending}
                                autoComplete="one-time-code"
                                className="min-w-[16rem] flex-1 rounded-md border border-mist bg-paper px-3 py-2 disabled:opacity-60 sm:max-w-sm"
                            />
                            <Button type="submit" variant="danger" loading={mfaDisablePending}>
                                {mfaDisablePending
                                    ? t('console.settings.mfa.disable_pending')
                                    : t('console.settings.mfa.disable')}
                            </Button>
                        </div>
                    </form>
                ) : null}

                {recoveryCodes ? (
                    <div className="rounded-md border border-teal/30 bg-teal/5 p-3">
                        <p className="mb-1 text-sm font-medium">
                            {t('console.settings.mfa.recovery_codes')}
                        </p>
                        <p className="mb-3 text-xs text-ink-soft">
                            {t('console.settings.mfa.codes_hint')}
                        </p>
                        <ul className="mb-3 grid gap-1 font-mono text-sm sm:grid-cols-2">
                            {recoveryCodes.map((code) => (
                                <li key={code}>{code}</li>
                            ))}
                        </ul>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={downloadRecoveryCodes}
                            >
                                {t('console.settings.mfa.download_codes')}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => void copyRecoveryCodes()}
                            >
                                {codesCopied
                                    ? t('common.copied')
                                    : t('console.settings.mfa.copy_codes')}
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            <div className="space-y-3 console-panel p-5">
                <h2 className="font-display text-lg font-semibold">
                    {t('console.settings.email_codes.title')}
                </h2>
                <p className="text-sm text-ink-soft">
                    {t('console.settings.email_codes.description')}
                </p>
            </div>

            <div className="space-y-3 console-panel p-5">
                <h2 className="font-display text-lg font-semibold">
                    {t('console.settings.passkeys.title')}
                </h2>
                <p className="text-sm text-ink-soft">
                    {t('console.settings.passkeys.description')}
                </p>
                <div className="flex flex-wrap gap-2">
                    <input
                        value={passkeyName}
                        onChange={(e) => setPasskeyName(e.target.value)}
                        disabled={passkeyPending}
                        className="rounded-md border border-mist bg-paper px-3 py-2 text-sm disabled:opacity-60"
                        placeholder={t('console.settings.passkeys.device_name_placeholder')}
                    />
                    <Button
                        type="button"
                        loading={passkeyPending}
                        onClick={() => void registerPasskey()}
                    >
                        {passkeyPending
                            ? t('console.settings.passkeys.adding')
                            : t('console.settings.passkeys.add')}
                    </Button>
                </div>
                <ul className="divide-y divide-mist/60 rounded-md border border-mist/60">
                    {passkeys.map((p) => (
                        <li
                            key={p.id}
                            className="flex items-center justify-between px-3 py-2 text-sm"
                        >
                            <span>{p.name ?? t('console.settings.passkeys.unnamed')}</span>
                            <button
                                type="button"
                                className="text-danger"
                                onClick={() => setPasskeyDeleteId(p.id)}
                            >
                                {t('console.settings.passkeys.remove')}
                            </button>
                        </li>
                    ))}
                    {passkeys.length === 0 ? (
                        <li className="px-3 py-3 text-sm text-ink-soft">
                            {t('console.settings.passkeys.empty')}
                        </li>
                    ) : null}
                </ul>
            </div>

            <div className="console-panel p-5 text-sm">
                <h2 className="font-display text-lg font-semibold">
                    {t('console.settings.workspace.title')}
                </h2>
                <p className="mt-2 text-ink-soft">
                    {t('console.settings.workspace.meta', {
                        name: org.name,
                        slug: org.slug,
                        role: org.role,
                    })}
                </p>
            </div>

            <ConfirmDelete
                open={passkeyDeleteId !== null}
                onOpenChange={(open) => {
                    if (!open) setPasskeyDeleteId(null);
                }}
                onConfirm={async () => {
                    if (!passkeyDeleteId) return;
                    try {
                        await apiDelete(`/api/v1/auth/passkeys/${passkeyDeleteId}`);
                        setPasskeys((prev) => prev.filter((p) => p.id !== passkeyDeleteId));
                        toast.success(t('console.settings.passkeys.removed_title'), {
                            description: t('console.settings.passkeys.removed_description'),
                        });
                    } catch (err) {
                        toast.error(t('console.settings.passkeys.remove_error'), {
                            description:
                                err instanceof ApiError
                                    ? err.message
                                    : t('console.settings.passkeys.remove_error_description'),
                        });
                        throw err;
                    }
                }}
            />
        </section>
    );
}
