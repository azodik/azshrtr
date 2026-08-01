const STORAGE_KEY = 'azshrtr.activeOrgId';

export function readLastOrgId(): string | null {
    try {
        return sessionStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

export function writeLastOrgId(orgId: string): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, orgId);
    } catch {
        // ignore
    }
}
