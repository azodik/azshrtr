const STORAGE_KEY = 'azshrtr.selectedPlan';

export type SelectedPlan = 'free' | 'pro';

export function normalizePlan(value: string | null | undefined): SelectedPlan | null {
    if (value === 'free' || value === 'pro') {
        return value;
    }

    return null;
}

/** Capture ?plan= from the URL into sessionStorage. */
export function capturePlanFromSearch(search: string): SelectedPlan | null {
    const params = new URLSearchParams(search.startsWith('?') ? search : `?${search}`);
    const plan = normalizePlan(params.get('plan'));

    if (plan) {
        sessionStorage.setItem(STORAGE_KEY, plan);
    }

    return plan ?? readSelectedPlan();
}

export function readSelectedPlan(): SelectedPlan | null {
    try {
        return normalizePlan(sessionStorage.getItem(STORAGE_KEY));
    } catch {
        return null;
    }
}

export function writeSelectedPlan(plan: SelectedPlan): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, plan);
    } catch {
        // ignore
    }
}

export function clearSelectedPlan(): void {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch {
        // ignore
    }
}

/** Path after login/register for the remembered plan. */
export function pathForSelectedPlan(orgId: string, plan: SelectedPlan | null): string {
    if (plan === 'pro' || plan === 'free') {
        return `/${orgId}/billing?intent=${plan}`;
    }

    return `/${orgId}`;
}

export function planQuery(plan: SelectedPlan | null): string {
    return plan ? `?plan=${plan}` : '';
}
