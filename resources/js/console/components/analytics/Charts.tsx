import { useMemo } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { useChartColors } from '@/hooks/useChartColors';
import { useI18n } from '@/i18n/useI18n';
import { cn } from '@/lib/cn';

type DailyPoint = { day: string; total: number };
type NamedTotal = { name: string; total: number };

function dayFromTooltipPayload(payload: unknown): string {
    if (!Array.isArray(payload) || payload.length === 0) {
        return '';
    }

    const first = payload[0];
    if (typeof first !== 'object' || first === null || !('payload' in first)) {
        return '';
    }

    const row = first.payload;
    if (typeof row !== 'object' || row === null || !('day' in row)) {
        return '';
    }

    return typeof row.day === 'string' ? row.day : '';
}

function ChartTooltip({
    active,
    payload,
    label,
}: {
    active?: boolean;
    payload?: Array<{ value?: number | string; name?: string }>;
    label?: string;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-[var(--radius-control)] border border-mist/70 bg-paper-elevated px-3 py-2 text-xs shadow-md">
            {label ? <p className="mb-1 font-medium text-ink">{label}</p> : null}
            {payload.map((entry) => (
                <p key={String(entry.name)} className="tabular-nums text-ink-soft">
                    {entry.name}: {Number(entry.value ?? 0).toLocaleString()}
                </p>
            ))}
        </div>
    );
}

export function DailyClicksChart({ data, className }: { data: DailyPoint[]; className?: string }) {
    const { t } = useI18n();
    const colors = useChartColors();
    const points = useMemo(
        () =>
            data.map((row) => ({
                day: row.day,
                label: formatDayLabel(row.day),
                clicks: row.total,
            })),
        [data],
    );

    if (points.length === 0) {
        return (
            <p className="py-10 text-center text-sm text-ink-soft">
                {t('console.analytics.no_daily_data')}
            </p>
        );
    }

    return (
        <div className={cn('h-64 w-full sm:h-72', className)}>
            <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={points} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                    <defs>
                        <linearGradient id="clicksFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor={colors.teal} stopOpacity={0.28} />
                            <stop offset="100%" stopColor={colors.teal} stopOpacity={0.02} />
                        </linearGradient>
                    </defs>
                    <CartesianGrid stroke={colors.mist} strokeDasharray="3 6" vertical={false} />
                    <XAxis
                        dataKey="label"
                        tick={{ fill: colors.inkSoft, fontSize: 11 }}
                        tickLine={false}
                        axisLine={{ stroke: colors.mist }}
                        minTickGap={28}
                    />
                    <YAxis
                        allowDecimals={false}
                        width={36}
                        tick={{ fill: colors.inkSoft, fontSize: 11 }}
                        tickLine={false}
                        axisLine={false}
                    />
                    <Tooltip
                        content={<ChartTooltip />}
                        labelFormatter={(_, payload) => dayFromTooltipPayload(payload)}
                    />
                    <Area
                        type="monotone"
                        dataKey="clicks"
                        name={t('console.analytics.total_clicks')}
                        stroke={colors.teal}
                        strokeWidth={2}
                        fill="url(#clicksFill)"
                        activeDot={{
                            r: 4,
                            fill: colors.teal,
                            stroke: colors.paper,
                            strokeWidth: 2,
                        }}
                    />
                </AreaChart>
            </ResponsiveContainer>
        </div>
    );
}

export function HorizontalTotalsChart({
    data,
    className,
}: {
    data: NamedTotal[];
    className?: string;
}) {
    const { t } = useI18n();
    const colors = useChartColors();
    const points = useMemo(
        () => data.slice(0, 10).map((row) => ({ name: row.name, total: row.total })),
        [data],
    );

    if (points.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-ink-soft">
                {t('console.analytics.no_chart_data')}
            </p>
        );
    }

    const height = Math.max(180, points.length * 36);

    return (
        <div className={cn('w-full', className)} style={{ height }}>
            <ResponsiveContainer width="100%" height="100%">
                <BarChart
                    data={points}
                    layout="vertical"
                    margin={{ top: 4, right: 16, left: 8, bottom: 4 }}
                >
                    <CartesianGrid stroke={colors.mist} strokeDasharray="3 6" horizontal={false} />
                    <XAxis
                        type="number"
                        allowDecimals={false}
                        tick={{ fill: colors.inkSoft, fontSize: 11 }}
                        tickLine={false}
                        axisLine={false}
                    />
                    <YAxis
                        type="category"
                        dataKey="name"
                        width={88}
                        tick={{ fill: colors.inkSoft, fontSize: 11 }}
                        tickLine={false}
                        axisLine={false}
                    />
                    <Tooltip content={<ChartTooltip />} />
                    <Bar
                        dataKey="total"
                        name={t('console.analytics.total_clicks')}
                        fill={colors.teal}
                        radius={[0, 4, 4, 0]}
                        barSize={16}
                    />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}

export function ShareDonutChart({ data, className }: { data: NamedTotal[]; className?: string }) {
    const { t } = useI18n();
    const colors = useChartColors();
    const points = useMemo(() => data.filter((row) => row.total > 0).slice(0, 7), [data]);
    const total = points.reduce((sum, row) => sum + row.total, 0);

    if (points.length === 0 || total === 0) {
        return (
            <p className="py-8 text-center text-sm text-ink-soft">
                {t('console.analytics.no_chart_data')}
            </p>
        );
    }

    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-center', className)}>
            <div className="mx-auto h-48 w-48 shrink-0 sm:mx-0">
                <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                        <Pie
                            data={points}
                            dataKey="total"
                            nameKey="name"
                            innerRadius={48}
                            outerRadius={72}
                            paddingAngle={2}
                            stroke={colors.paper}
                            strokeWidth={2}
                        >
                            {points.map((entry, index) => (
                                <Cell
                                    key={entry.name}
                                    fill={colors.pie[index % colors.pie.length] ?? colors.mint}
                                />
                            ))}
                        </Pie>
                        <Tooltip content={<ChartTooltip />} />
                    </PieChart>
                </ResponsiveContainer>
            </div>
            <ul className="min-w-0 flex-1 space-y-2">
                {points.map((row, index) => {
                    const pct = Math.round((row.total / total) * 100);
                    return (
                        <li
                            key={row.name}
                            className="flex items-center justify-between gap-3 text-sm"
                        >
                            <span className="flex min-w-0 items-center gap-2">
                                <span
                                    className="size-2.5 shrink-0 rounded-sm"
                                    style={{
                                        backgroundColor:
                                            colors.pie[index % colors.pie.length] ?? colors.mint,
                                    }}
                                    aria-hidden="true"
                                />
                                <span className="truncate font-medium text-ink">{row.name}</span>
                            </span>
                            <span className="shrink-0 tabular-nums text-ink-soft">
                                {row.total.toLocaleString()} · {pct}%
                            </span>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

function formatDayLabel(day: string): string {
    const date = new Date(`${day}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return day;
    }
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
