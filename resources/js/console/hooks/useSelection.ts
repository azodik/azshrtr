import { useCallback, useEffect, useMemo, useState } from 'react';

export function useSelection(ids: string[] = []) {
    const [selected, setSelected] = useState<Set<string>>(() => new Set());
    const idsKey = ids.join('|');

    useEffect(() => {
        setSelected(new Set());
    }, [idsKey]);

    const selectedIds = useMemo(() => Array.from(selected), [selected]);
    const selectedCount = selectedIds.length;
    const allSelected = ids.length > 0 && ids.every((id) => selected.has(id));
    const someSelected = ids.some((id) => selected.has(id));

    const toggle = useCallback((id: string) => {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const toggleAll = useCallback(() => {
        setSelected((prev) => {
            if (ids.length > 0 && ids.every((id) => prev.has(id))) {
                return new Set();
            }
            return new Set(ids);
        });
    }, [ids]);

    const clear = useCallback(() => setSelected(new Set()), []);

    const isSelected = useCallback((id: string) => selected.has(id), [selected]);

    return {
        selected,
        selectedIds,
        selectedCount,
        allSelected,
        someSelected,
        toggle,
        toggleAll,
        clear,
        isSelected,
    };
}
