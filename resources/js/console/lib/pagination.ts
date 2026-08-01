export const DEFAULT_PER_PAGE = 25;
export const DEFAULT_LOG_PER_PAGE = 50;

export const PER_PAGE_OPTIONS = [10, 25, 50, 100] as const;

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export function emptyPaginationMeta(perPage: number = DEFAULT_PER_PAGE): PaginationMeta {
    return {
        current_page: 1,
        last_page: 1,
        per_page: perPage,
        total: 0,
        from: null,
        to: null,
    };
}

export function metaFromPaginated<T>(payload: Paginated<T>): PaginationMeta {
    return {
        current_page: payload.current_page,
        last_page: payload.last_page,
        per_page: payload.per_page,
        total: payload.total,
        from: payload.from,
        to: payload.to,
    };
}

export function withPaginationParams(
    params: URLSearchParams,
    page: number,
    perPage: number,
): URLSearchParams {
    params.set('page', String(page));
    params.set('per_page', String(perPage));
    return params;
}

export function paginationQuery(page: number, perPage: number, extra?: URLSearchParams): string {
    const params = withPaginationParams(
        extra ? new URLSearchParams(extra) : new URLSearchParams(),
        page,
        perPage,
    );
    return `?${params.toString()}`;
}
