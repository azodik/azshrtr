import { useMemo } from 'react';
import { cssColor } from '@/lib/theme';
import { useTheme } from '@/theme/ThemeProvider';

export type ChartColors = {
    teal: string;
    tealBright: string;
    tealDeep: string;
    mint: string;
    inkSoft: string;
    mist: string;
    paper: string;
    pie: string[];
};

export function useChartColors(): ChartColors {
    const { resolved } = useTheme();

    return useMemo(() => {
        const teal = cssColor('--color-teal', '#0b6e6e');
        const tealBright = cssColor('--color-teal-bright', '#0f8a8a');
        const tealDeep = cssColor('--color-teal-deep', '#085454');
        const mint = cssColor('--color-mint', '#8fd4ce');
        const inkSoft = cssColor('--color-ink-soft', '#3a4a45');
        const mist = cssColor('--color-mist', '#a8c9c5');
        const paper = cssColor('--color-paper-elevated', '#f7fcfb');

        return {
            teal,
            tealBright,
            tealDeep,
            mint,
            inkSoft,
            mist,
            paper,
            pie: [teal, tealBright, mint, tealDeep, mint, inkSoft, mist],
        };
        // Re-read CSS variables whenever resolved theme changes.
    }, [resolved]);
}
