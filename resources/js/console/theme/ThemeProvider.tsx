import {
    createContext,
    type ReactNode,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';
import {
    applyResolvedTheme,
    type ResolvedTheme,
    readThemePreference,
    resolveTheme,
    type ThemePreference,
    writeThemePreference,
} from '@/lib/theme';

type ThemeContextValue = {
    preference: ThemePreference;
    resolved: ResolvedTheme;
    setPreference: (preference: ThemePreference) => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

type ThemeProviderProps = {
    children: ReactNode;
};

export function ThemeProvider({ children }: ThemeProviderProps) {
    const [preference, setPreferenceState] = useState<ThemePreference>(() => readThemePreference());
    const [resolved, setResolved] = useState<ResolvedTheme>(() =>
        resolveTheme(readThemePreference()),
    );

    const setPreference = useCallback((next: ThemePreference) => {
        setPreferenceState(next);
        writeThemePreference(next);
        const nextResolved = resolveTheme(next);
        setResolved(nextResolved);
        applyResolvedTheme(nextResolved);
    }, []);

    useEffect(() => {
        const nextResolved = resolveTheme(preference);
        setResolved(nextResolved);
        applyResolvedTheme(nextResolved);
        writeThemePreference(preference);

        if (preference !== 'system') {
            return;
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const onChange = () => {
            const systemResolved = resolveTheme('system');
            setResolved(systemResolved);
            applyResolvedTheme(systemResolved);
        };

        media.addEventListener('change', onChange);
        return () => media.removeEventListener('change', onChange);
    }, [preference]);

    const value = useMemo(
        () => ({ preference, resolved, setPreference }),
        [preference, resolved, setPreference],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
    const ctx = useContext(ThemeContext);
    if (!ctx) {
        throw new Error('useTheme must be used within ThemeProvider');
    }
    return ctx;
}
