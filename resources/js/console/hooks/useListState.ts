import { useCallback, useState } from 'react';
import { defaultListQuery, type ListQueryState, type SortDirection } from '@/lib/listQuery';

export function useListState(sort = 'created_at', direction: SortDirection = 'desc', perPage = 25) {
    const initial = defaultListQuery(sort, direction, perPage);
    const [draft, setDraft] = useState<ListQueryState>(initial);
    const [applied, setApplied] = useState<ListQueryState>(initial);
    const [reloadToken, setReloadToken] = useState(0);

    const applyFilters = useCallback(() => {
        setApplied((prev) => ({
            ...draft,
            page: 1,
            perPage: prev.perPage,
        }));
    }, [draft]);

    const clearFilters = useCallback(() => {
        const next = defaultListQuery(sort, direction, applied.perPage);
        setDraft(next);
        setApplied(next);
    }, [applied.perPage, direction, sort]);

    const setPage = useCallback((page: number) => {
        setApplied((prev) => ({ ...prev, page }));
    }, []);

    const setPerPage = useCallback((next: number) => {
        setApplied((prev) => ({ ...prev, perPage: next, page: 1 }));
        setDraft((prev) => ({ ...prev, perPage: next }));
    }, []);

    const refresh = useCallback(() => setReloadToken((n) => n + 1), []);

    const patchDraft = useCallback((patch: Partial<ListQueryState>) => {
        setDraft((prev) => ({ ...prev, ...patch }));
    }, []);

    return {
        draft,
        applied,
        reloadToken,
        setDraft,
        patchDraft,
        applyFilters,
        clearFilters,
        setPage,
        setPerPage,
        refresh,
        setApplied,
    };
}
