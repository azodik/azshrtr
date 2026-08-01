export type SortDirection = 'asc' | 'desc';

export function isSortDirection(value: string): value is SortDirection {
    return value === 'asc' || value === 'desc';
}

export type ListQueryState = {
    page: number;
    perPage: number;
    q: string;
    sort: string;
    direction: SortDirection;
    from: string;
    to: string;
};

export type ListQueryExtras = Record<string, string | string[] | undefined>;

export function buildListQuery(state: ListQueryState, extras: ListQueryExtras = {}): string {
    const params = new URLSearchParams();
    params.set('page', String(state.page));
    params.set('per_page', String(state.perPage));
    if (state.q.trim() !== '') {
        params.set('q', state.q.trim());
    }
    if (state.sort !== '') {
        params.set('sort', state.sort);
    }
    params.set('direction', state.direction);
    if (state.from !== '') {
        params.set('from', state.from);
    }
    if (state.to !== '') {
        params.set('to', state.to);
    }

    for (const [key, value] of Object.entries(extras)) {
        if (value === undefined || value === '') {
            continue;
        }
        if (Array.isArray(value)) {
            for (const item of value) {
                params.append(`${key}[]`, item);
            }
        } else {
            params.set(key, value);
        }
    }

    const qs = params.toString();
    return qs === '' ? '' : `?${qs}`;
}

export function defaultListQuery(
    sort = 'created_at',
    direction: SortDirection = 'desc',
    perPage = 25,
): ListQueryState {
    return {
        page: 1,
        perPage,
        q: '',
        sort,
        direction,
        from: '',
        to: '',
    };
}

export function toDateInputValue(date: Date | undefined): string {
    if (!date) {
        return '';
    }
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function parseDateInput(value: string): Date | undefined {
    if (value === '') {
        return undefined;
    }
    const [year, month, day] = value.split('-').map(Number);
    if (!year || !month || !day) {
        return undefined;
    }
    return new Date(year, month - 1, day);
}
