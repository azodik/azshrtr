import { BarChart3 } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    DailyClicksChart,
    HorizontalTotalsChart,
    ShareDonutChart,
} from '@/components/analytics/Charts';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/i18n/useI18n';
import { apiGet } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type Analytics = {
    days: number;
    total_clicks: number;
    by_country: Array<{ country: string; total: number }>;
    by_city: Array<{ city: string; country: string | null; total: number }>;
    by_browser: Array<{ browser: string; total: number }>;
    by_device: Array<{ device: string; total: number }>;
    daily: Array<{ day: string; total: number }>;
    recent: Array<{
        id: string;
        clicked_at: string | null;
        country: string | null;
        region: string | null;
        city: string | null;
        browser: string | null;
        device_bucket: string | null;
        referrer: string | null;
        link: {
            id: string;
            code: string;
            title: string | null;
            destination_url: string;
        } | null;
    }>;
    source: string;
};

export function AnalyticsPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const [days, setDays] = useState('30');
    const [data, setData] = useState<Analytics | null>(null);

    useEffect(() => {
        if (!orgId) return;
        void apiGet<Analytics>(`/api/v1/organizations/${orgId}/analytics?days=${days}`).then(
            setData,
        );
    }, [orgId, days]);

    if (!org || !orgId) return null;

    const empty =
        data !== null &&
        data.total_clicks === 0 &&
        data.by_country.length === 0 &&
        data.recent.length === 0;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.analytics.title')}
                description={t('console.analytics.description')}
                action={
                    <div className="w-36 space-y-1.5">
                        <Label className="sr-only">{t('console.analytics.range_label')}</Label>
                        <Select value={days} onValueChange={setDays}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7">{t('console.analytics.range_7')}</SelectItem>
                                <SelectItem value="30">
                                    {t('console.analytics.range_30')}
                                </SelectItem>
                                <SelectItem value="90">
                                    {t('console.analytics.range_90')}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                }
            />

            {data === null ? (
                <p className="text-sm text-ink-soft">{t('console.analytics.loading')}</p>
            ) : null}

            {empty ? (
                <EmptyState
                    icon={BarChart3}
                    title={t('console.analytics.empty_title')}
                    description={t('console.analytics.empty_description')}
                />
            ) : null}

            {data && !empty ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>
                                    {t('console.analytics.total_clicks')}
                                </CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {data.total_clicks.toLocaleString()}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>
                                    {t('console.analytics.countries')}
                                </CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {data.by_country.length}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>{t('console.analytics.browsers')}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {data.by_browser.length}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>{t('console.analytics.devices')}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {data.by_device.length}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('console.analytics.daily_title')}</CardTitle>
                            <CardDescription>
                                {t('console.analytics.daily_description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <DailyClicksChart data={data.daily} />
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('console.analytics.top_countries')}</CardTitle>
                                <CardDescription>
                                    {t('console.analytics.top_countries_description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {data.by_country.length === 0 ? (
                                    <p className="text-sm text-ink-soft">
                                        {t('console.analytics.no_country_data')}
                                    </p>
                                ) : (
                                    <HorizontalTotalsChart
                                        data={data.by_country.map((row) => ({
                                            name: row.country,
                                            total: row.total,
                                        }))}
                                    />
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('console.analytics.top_cities')}</CardTitle>
                                <CardDescription>
                                    {t('console.analytics.top_cities_description')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {data.by_city.length === 0 ? (
                                    <p className="text-sm text-ink-soft">
                                        {t('console.analytics.no_city_data')}
                                    </p>
                                ) : (
                                    <HorizontalTotalsChart
                                        data={data.by_city.map((row) => ({
                                            name: row.country
                                                ? `${row.city}, ${row.country}`
                                                : row.city,
                                            total: row.total,
                                        }))}
                                    />
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('console.analytics.browsers')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {data.by_browser.length === 0 ? (
                                    <p className="text-sm text-ink-soft">
                                        {t('console.analytics.no_browser_data')}
                                    </p>
                                ) : (
                                    <ShareDonutChart
                                        data={data.by_browser.map((row) => ({
                                            name: row.browser,
                                            total: row.total,
                                        }))}
                                    />
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('console.analytics.devices')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {data.by_device.length === 0 ? (
                                    <p className="text-sm text-ink-soft">
                                        {t('console.analytics.no_device_data')}
                                    </p>
                                ) : (
                                    <ShareDonutChart
                                        data={data.by_device.map((row) => ({
                                            name: row.device,
                                            total: row.total,
                                        }))}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('console.analytics.recent_clicks')}</CardTitle>
                            <CardDescription>{data.source}</CardDescription>
                        </CardHeader>
                        <CardContent className="overflow-x-auto p-0">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-y border-mist/60 text-xs text-ink-soft">
                                    <tr>
                                        <th className="px-5 py-2 font-medium">
                                            {t('console.analytics.col.when')}
                                        </th>
                                        <th className="px-5 py-2 font-medium">
                                            {t('console.analytics.col.link')}
                                        </th>
                                        <th className="px-5 py-2 font-medium">
                                            {t('console.analytics.col.location')}
                                        </th>
                                        <th className="px-5 py-2 font-medium">
                                            {t('console.analytics.col.client')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.recent.map((click) => (
                                        <tr key={click.id} className="border-t border-mist/50">
                                            <td className="whitespace-nowrap px-5 py-2 text-xs text-ink-soft">
                                                {click.clicked_at
                                                    ? formatWhen(click.clicked_at)
                                                    : t('common.em_dash')}
                                            </td>
                                            <td className="px-5 py-2 font-mono text-xs">
                                                {click.link?.code ?? t('common.em_dash')}
                                            </td>
                                            <td className="px-5 py-2 text-xs">
                                                {[click.city, click.region, click.country]
                                                    .filter(Boolean)
                                                    .join(', ') || t('common.em_dash')}
                                            </td>
                                            <td className="px-5 py-2">
                                                <div className="flex flex-wrap gap-1">
                                                    {click.browser ? (
                                                        <Badge variant="secondary">
                                                            {click.browser}
                                                        </Badge>
                                                    ) : null}
                                                    {click.device_bucket ? (
                                                        <Badge variant="outline">
                                                            {click.device_bucket}
                                                        </Badge>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </>
            ) : null}
        </section>
    );
}
