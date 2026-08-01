import { createContext, type ReactNode, useCallback, useContext, useEffect, useState } from 'react';
import { bootstrapI18n } from '@/i18n/useI18n';
import { ApiError, apiGet, apiPost, syncCsrfToken } from '@/lib/api';
import { isLocaleCode, readLocale } from '@/lib/locale';

export type AuthOrganization = {
    id: string;
    name: string;
    slug: string;
    role: string;
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    theme_preference: string | null;
    preferred_locale?: string | null;
    mfa_enabled?: boolean;
    organizations: AuthOrganization[];
};

type AuthResponse = {
    user: AuthUser;
    csrf_token?: string;
};

type AuthContextValue = {
    user: AuthUser | null;
    loading: boolean;
    setUser: (user: AuthUser | null) => void;
    login: (email: string, password: string) => Promise<AuthUser>;
    register: (
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
        acceptedTerms: boolean,
    ) => Promise<AuthUser>;
    logout: () => Promise<void>;
    refresh: () => Promise<AuthUser | null>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

function syncLocaleFromUser(user: AuthUser | null): void {
    if (user?.preferred_locale && isLocaleCode(user.preferred_locale)) {
        bootstrapI18n(user.preferred_locale);
    }
}

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUserState] = useState<AuthUser | null>(null);
    const [loading, setLoading] = useState(true);

    const setUser = useCallback((next: AuthUser | null) => {
        setUserState(next);
        syncLocaleFromUser(next);
    }, []);

    const refresh = async (): Promise<AuthUser | null> => {
        try {
            const data = await apiGet<AuthResponse>('/api/v1/auth/me');
            syncCsrfToken(data.csrf_token);
            setUser(data.user);
            return data.user;
        } catch (error) {
            if (error instanceof ApiError && [401, 419, 431].includes(error.status)) {
                setUser(null);
            } else {
                setUser(null);
            }
            return null;
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        let cancelled = false;
        void (async () => {
            try {
                const data = await apiGet<AuthResponse>('/api/v1/auth/me');
                if (!cancelled) {
                    syncCsrfToken(data.csrf_token);
                    setUser(data.user);
                }
            } catch {
                if (!cancelled) {
                    setUser(null);
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [setUser]);

    const login = async (email: string, password: string): Promise<AuthUser> => {
        const data = await apiPost<AuthResponse>('/api/v1/auth/login', {
            email,
            password,
            preferred_locale: readLocale(),
        });
        syncCsrfToken(data.csrf_token);
        setUser(data.user);
        setLoading(false);
        return data.user;
    };

    const register = async (
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
        acceptedTerms: boolean,
    ): Promise<AuthUser> => {
        const data = await apiPost<AuthResponse>('/api/v1/auth/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
            accepted_terms: acceptedTerms,
            preferred_locale: readLocale(),
        });
        syncCsrfToken(data.csrf_token);
        setUser(data.user);
        setLoading(false);
        return data.user;
    };

    const logout = async () => {
        try {
            await apiPost('/api/v1/auth/logout');
        } catch {
            // ignore
        }
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, loading, setUser, login, register, logout, refresh }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth(): AuthContextValue {
    const ctx = useContext(AuthContext);
    if (!ctx) {
        throw new Error('useAuth must be used within AuthProvider');
    }
    return ctx;
}
