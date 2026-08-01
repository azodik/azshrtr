export const THEME_STORAGE_KEY = 'azshrtr-theme';

export const THEME_PREFERENCES = ['light', 'dark', 'system'] as const;

export type ThemePreference = (typeof THEME_PREFERENCES)[number];

export type ResolvedTheme = 'light' | 'dark';

export function isThemePreference(value: unknown): value is ThemePreference {
    return value === 'light' || value === 'dark' || value === 'system';
}

export function readThemePreference(): ThemePreference {
    try {
        const stored = localStorage.getItem(THEME_STORAGE_KEY);
        if (isThemePreference(stored)) {
            return stored;
        }
    } catch {
        // ignore
    }
    return 'system';
}

export function writeThemePreference(preference: ThemePreference): void {
    try {
        localStorage.setItem(THEME_STORAGE_KEY, preference);
    } catch {
        // ignore
    }
}

export function systemPrefersDark(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function resolveTheme(preference: ThemePreference): ResolvedTheme {
    if (preference === 'system') {
        return systemPrefersDark() ? 'dark' : 'light';
    }
    return preference;
}

export function applyResolvedTheme(resolved: ResolvedTheme): void {
    const root = document.documentElement;
    root.classList.toggle('dark', resolved === 'dark');
    root.style.colorScheme = resolved;

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', resolved === 'dark' ? '#0c1413' : '#0B6E6E');
    }
}

export function cssColor(variable: string, fallback: string): string {
    const value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
    return value === '' ? fallback : value;
}
