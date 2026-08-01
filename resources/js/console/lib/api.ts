import { parseJsonText, readStringField } from '@/lib/json';
import { readLocale } from '@/lib/locale';

const csrfMeta = (): string =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function readCookie(name: string): string {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1] ?? '') : '';
}

/** Keep the Blade meta token in sync after session regeneration (login/register/verify). */
export function syncCsrfToken(token: string | null | undefined): void {
    if (!token) {
        return;
    }

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }
}

function applyCsrfHeaders(headers: Record<string, string>): void {
    const meta = csrfMeta();
    if (meta !== '') {
        headers['X-CSRF-TOKEN'] = meta;
        return;
    }

    const xsrf = readCookie('XSRF-TOKEN');
    if (xsrf !== '') {
        headers['X-XSRF-TOKEN'] = xsrf;
    }
}

async function ensureCsrfCookie(): Promise<void> {
    await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
}

export class ApiError extends Error {
    constructor(
        message: string,
        public status: number,
        public body: unknown,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

function extractCsrf(data: unknown): void {
    const token = readStringField(data, 'csrf_token');
    if (token !== undefined) {
        syncCsrfToken(token);
    }
}

async function apiRequest(
    method: string,
    path: string,
    body?: unknown,
    retryOnCsrf = true,
): Promise<unknown> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Accept-Language': readLocale(),
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (method !== 'GET' && method !== 'HEAD') {
        headers['Content-Type'] = 'application/json';
        applyCsrfHeaders(headers);
    }

    const response = await fetch(path, {
        method,
        credentials: 'same-origin',
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    if (response.status === 419 && retryOnCsrf) {
        await ensureCsrfCookie();
        try {
            const me = await fetch('/api/v1/auth/me', {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (me.ok) {
                extractCsrf(parseJsonText(await me.text()));
            }
        } catch {
            // ignore
        }
        return apiRequest(method, path, body, false);
    }

    const data = parseJsonText(await response.text());

    if (!response.ok) {
        const message = readStringField(data, 'message') ?? `Request failed (${response.status})`;
        throw new ApiError(message, response.status, data);
    }

    extractCsrf(data);

    return data;
}

/**
 * Decode a successful JSON API payload into the caller's response type.
 * Call sites own the OpenAPI contract for that endpoint.
 */
export function decodeApiResponse<T>(value: unknown): T {
    if (value === undefined) {
        throw new ApiError('Empty API response', 500, value);
    }

    // Boundary cast: HTTP JSON → endpoint response type (validated by API tests / OpenAPI).
    return value as T;
}

export const apiGet = async <T>(path: string): Promise<T> =>
    decodeApiResponse<T>(await apiRequest('GET', path));

export const apiPost = async <T>(path: string, body?: unknown): Promise<T> => {
    await ensureCsrfCookie();
    return decodeApiResponse<T>(await apiRequest('POST', path, body));
};

export const apiPatch = async <T>(path: string, body?: unknown): Promise<T> => {
    await ensureCsrfCookie();
    return decodeApiResponse<T>(await apiRequest('PATCH', path, body));
};

export const apiPut = async <T>(path: string, body?: unknown): Promise<T> => {
    await ensureCsrfCookie();
    return decodeApiResponse<T>(await apiRequest('PUT', path, body));
};

export const apiDelete = async <T>(path: string): Promise<T> => {
    await ensureCsrfCookie();
    return decodeApiResponse<T>(await apiRequest('DELETE', path));
};
