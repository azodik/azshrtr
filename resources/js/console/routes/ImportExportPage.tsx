import { FileKey2 } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiPost } from '@/lib/api';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type Format = 'json' | 'csv';

const samples: Record<Format, string> = {
    json: `[\n  {"destination_url": "https://azshrtr.com"}\n]`,
    csv: `destination_url,title\nhttps://azshrtr.com,Example`,
};

const FORMAT_LABEL_KEYS: Record<Format, string> = {
    json: 'console.import_export.format_json',
    csv: 'console.import_export.format_csv',
};

export function ImportExportPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const [format, setFormat] = useState<Format>('json');
    const [payload, setPayload] = useState(samples.json);
    const [exporting, setExporting] = useState(false);
    const [importing, setImporting] = useState(false);

    const formatLabel = t(FORMAT_LABEL_KEYS[format]);

    const switchFormat = (next: Format) => {
        setFormat(next);
        setPayload(samples[next]);
    };

    const exportLinks = async () => {
        if (!org) return;
        setExporting(true);
        try {
            const data = await apiPost<{ download_url: string }>(
                `/api/v1/organizations/${org.id}/export`,
                { format },
            );
            window.location.href = data.download_url;
            toast.success(t('console.import_export.export_started'), {
                description: t('console.import_export.export_started_description', {
                    format: formatLabel,
                }),
            });
        } catch (err) {
            toast.error(t('console.import_export.export_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.import_export.export_error_description'),
            });
        } finally {
            setExporting(false);
        }
    };

    const importLinks = async () => {
        if (!org) return;
        setImporting(true);
        try {
            const data = await apiPost<{ import: { success_rows: number; error_rows: number } }>(
                `/api/v1/organizations/${org.id}/import`,
                { format, payload },
            );
            toast.success(t('console.import_export.import_result'), {
                description: t('console.import_export.import_result_description', {
                    success: data.import.success_rows,
                    errors: data.import.error_rows,
                }),
            });
        } catch (err) {
            toast.error(t('console.import_export.import_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.import_export.import_error_description'),
            });
        } finally {
            setImporting(false);
        }
    };

    if (!org) return null;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.import_export.title')}
                description={t('console.import_export.description')}
            />

            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => switchFormat('json')}
                    className={`rounded-md px-3 py-1.5 text-sm font-semibold ${
                        format === 'json'
                            ? 'bg-teal text-paper-elevated'
                            : 'border border-mist text-ink-soft'
                    }`}
                >
                    {t('console.import_export.format_json')}
                </button>
                <button
                    type="button"
                    onClick={() => switchFormat('csv')}
                    className={`rounded-md px-3 py-1.5 text-sm font-semibold ${
                        format === 'csv'
                            ? 'bg-teal text-paper-elevated'
                            : 'border border-mist text-ink-soft'
                    }`}
                >
                    {t('console.import_export.format_csv')}
                </button>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <div className="space-y-3 console-panel p-5">
                    <h2 className="font-display text-lg font-semibold">
                        {t('console.import_export.export_title')}
                    </h2>
                    <p className="text-sm text-ink-soft">
                        {t('console.import_export.export_description', { format: formatLabel })}
                    </p>
                    <button
                        type="button"
                        onClick={() => void exportLinks()}
                        disabled={exporting}
                        className="inline-flex items-center justify-center rounded-[var(--radius-control)] bg-teal px-4 py-2 text-sm font-semibold text-paper-elevated hover:bg-teal-bright disabled:opacity-60"
                    >
                        {exporting
                            ? t('console.import_export.exporting')
                            : t('console.import_export.export_button', { format: formatLabel })}
                    </button>
                </div>

                <div className="space-y-3 console-panel p-5">
                    <h2 className="font-display text-lg font-semibold">
                        {t('console.import_export.import_title')}
                    </h2>
                    <p className="text-sm text-ink-soft">
                        {t(
                            format === 'json'
                                ? 'console.import_export.import_hint_json'
                                : 'console.import_export.import_hint_csv',
                        )}
                    </p>
                    <textarea
                        value={payload}
                        onChange={(e) => setPayload(e.target.value)}
                        rows={8}
                        className="w-full rounded-md border border-mist bg-paper px-3 py-2 font-mono text-sm"
                    />
                    <button
                        type="button"
                        onClick={() => void importLinks()}
                        disabled={importing}
                        className="rounded-md border border-mist px-4 py-2 text-sm font-semibold hover:bg-fog disabled:opacity-60"
                    >
                        {importing
                            ? t('console.import_export.importing')
                            : t('console.import_export.import_button', { format: formatLabel })}
                    </button>
                </div>
            </div>

            <EmptyState
                icon={FileKey2}
                title={t('console.import_export.tip_title')}
                description={t(
                    format === 'json'
                        ? 'console.import_export.tip_json'
                        : 'console.import_export.tip_csv',
                )}
            />
        </section>
    );
}
