import { useCallback, useEffect, useMemo, useSyncExternalStore } from 'react';
import { LOCALES, type LocaleCode, readLocale, writeLocale } from '../lib/locale';
import de from '../locales/de.json';
import en from '../locales/en.json';
import es from '../locales/es.json';
import fr from '../locales/fr.json';
import hi from '../locales/hi.json';

type Messages = Record<string, string>;

function messagesFromCatalog(catalog: Record<string, string>): Messages {
    const messages: Messages = {};
    for (const [key, value] of Object.entries(catalog)) {
        if (typeof value === 'string') {
            messages[key] = value;
        }
    }
    return messages;
}

const catalogs: Record<LocaleCode, Messages> = {
    en: messagesFromCatalog(en),
    es: messagesFromCatalog(es),
    fr: messagesFromCatalog(fr),
    de: messagesFromCatalog(de),
    hi: messagesFromCatalog(hi),
};

type I18nSnapshot = {
    locale: LocaleCode;
    messages: Messages;
    english: Messages;
    version: number;
};

let snapshot: I18nSnapshot = {
    locale: 'en',
    messages: catalogs.en,
    english: catalogs.en,
    version: 0,
};

const listeners = new Set<() => void>();

function emit(): void {
    snapshot = { ...snapshot, version: snapshot.version + 1 };
    for (const listener of listeners) {
        listener();
    }
}

function lookup(messages: Messages, key: string): string | undefined {
    const value = messages[key];
    return value !== undefined && value !== '' ? value : undefined;
}

function applyLocale(locale: LocaleCode): void {
    writeLocale(locale);
    snapshot = {
        locale,
        messages: catalogs[locale] ?? catalogs.en,
        english: catalogs.en,
        version: snapshot.version,
    };
    emit();
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);
    return () => {
        listeners.delete(listener);
    };
}

function getSnapshot(): I18nSnapshot {
    return snapshot;
}

let bootstrapped = false;

export function bootstrapI18n(locale?: LocaleCode): void {
    const target = locale ?? readLocale();
    if (!bootstrapped) {
        bootstrapped = true;
        applyLocale(target);
        return;
    }
    if (snapshot.locale !== target) {
        applyLocale(target);
    }
}

export function setLocale(locale: LocaleCode): void {
    applyLocale(locale);
}

export function translate(key: string, replacements: Record<string, string | number> = {}): string {
    let value = lookup(snapshot.messages, key) ?? lookup(snapshot.english, key) ?? key;

    // Longer keys first so `:to` does not clobber `:total`.
    const names = Object.keys(replacements).sort((a, b) => b.length - a.length);
    for (const name of names) {
        const replacement = String(replacements[name]);
        value = value.replaceAll(`:${name}`, replacement);
        value = value.replaceAll(`{${name}}`, replacement);
    }

    return value;
}

export function useI18n() {
    useEffect(() => {
        bootstrapI18n();
    }, []);

    const current = useSyncExternalStore(subscribe, getSnapshot, getSnapshot);

    const t = useCallback(
        (key: string, replacements: Record<string, string | number> = {}): string =>
            translate(key, replacements),
        [current.messages, current.english, current.version],
    );

    return useMemo(
        () => ({
            t,
            locale: current.locale,
            locales: LOCALES,
            setLocale,
        }),
        [t, current.locale],
    );
}
