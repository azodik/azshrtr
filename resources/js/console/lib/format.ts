export function shortPath(code: string): string {
    return `/${code}`;
}

export function absoluteShortUrl(code: string): string {
    return `${window.location.origin}/${code}`;
}

export function formatWhen(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export async function copyText(value: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(value);
        return true;
    } catch {
        return false;
    }
}
