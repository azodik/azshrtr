import { translate } from '@/i18n/useI18n';
import { ApiError } from '@/lib/api';
import { parseJsonText, readStringField } from '@/lib/json';
import { readLocale } from '@/lib/locale';

function filenameFromDisposition(header: string | null, fallback: string): string {
    if (!header) {
        return fallback;
    }
    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
        return decodeURIComponent(utfMatch[1]);
    }
    const plainMatch = header.match(/filename="?([^"]+)"?/i);
    if (plainMatch?.[1]) {
        return plainMatch[1];
    }
    return fallback;
}

/** Fetch a server export endpoint and trigger a browser file download. */
export async function downloadFromApi(path: string, fallbackFilename: string): Promise<void> {
    const response = await fetch(path, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: '*/*',
            'Accept-Language': readLocale(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        let message = translate('common.download_failed', { status: response.status });
        try {
            const data = parseJsonText(await response.text());
            const apiMessage = readStringField(data, 'message');
            if (apiMessage !== undefined) {
                message = apiMessage;
            }
        } catch {
            // ignore
        }
        throw new ApiError(message, response.status, null);
    }

    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = filenameFromDisposition(
        response.headers.get('Content-Disposition'),
        fallbackFilename,
    );
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(objectUrl);
}
