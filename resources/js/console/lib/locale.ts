export const LOCALES = [
    { value: 'en', label: 'English', short: 'EN' },
    { value: 'es', label: 'Español', short: 'ES' },
    { value: 'fr', label: 'Français', short: 'FR' },
    { value: 'de', label: 'Deutsch', short: 'DE' },
    { value: 'hi', label: 'हिन्दी', short: 'HI' },
] as const;

export type LocaleCode = (typeof LOCALES)[number]['value'];

const STORAGE_KEY = 'azshrtr_preferred_locale';

export function isLocaleCode(value: string): value is LocaleCode {
    return LOCALES.some((locale) => locale.value === value);
}

export function readLocale(): LocaleCode {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && isLocaleCode(stored)) {
            return stored;
        }
    } catch {
        // ignore
    }

    const browser = (typeof navigator !== 'undefined' ? navigator.language : 'en')
        .slice(0, 2)
        .toLowerCase();

    return isLocaleCode(browser) ? browser : 'en';
}

export function writeLocale(locale: LocaleCode): void {
    try {
        localStorage.setItem(STORAGE_KEY, locale);
    } catch {
        // ignore
    }
    document.documentElement.lang = locale;
}

export function localeNativeLabel(code: LocaleCode): string {
    return LOCALES.find((locale) => locale.value === code)?.label ?? code;
}
