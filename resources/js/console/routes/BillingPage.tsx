import { FileText } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { ConfirmDelete } from '@/components/ConfirmDelete';
import { EmptyState } from '@/components/EmptyState';
import { ListSkeleton } from '@/components/ListSkeleton';
import { PageHeader } from '@/components/PageHeader';
import { Pagination } from '@/components/Pagination';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/i18n/useI18n';
import { ApiError, apiGet, apiPost } from '@/lib/api';
import { formatWhen } from '@/lib/format';
import {
    emptyPaginationMeta,
    metaFromPaginated,
    type Paginated,
    type PaginationMeta,
    paginationQuery,
} from '@/lib/pagination';
import {
    clearSelectedPlan,
    normalizePlan,
    readSelectedPlan,
    type SelectedPlan,
    writeSelectedPlan,
} from '@/lib/planIntent';
import { toast } from '@/lib/toast';
import { useActiveOrg } from '@/workspace/useActiveOrg';

type SubscriptionInfo = {
    id: string;
    status: string;
    current_period_end: string | null;
    cancel_at: string | null;
    cancelled_at: string | null;
};

type BillingInfo = {
    billing_enabled: boolean;
    can_manage_billing: boolean;
    plan: { name: string; slug: string };
    subscription: SubscriptionInfo | null;
};

type InvoiceRow = {
    id: string;
    status: string | null;
    currency: string | null;
    amount: number | null;
    created_at: string | null;
    invoice_url: string | null;
    subscription_id: string | null;
};

const VERIFY_POLL_MS = 4000;
const VERIFY_MAX_MS = 120_000;
const CHECKOUT_STATUS_TTL_MS = 24 * 60 * 60 * 1000;

type CheckoutReturnStatus = 'failed' | 'succeeded' | 'verifying';

type StoredCheckoutStatus = {
    status: CheckoutReturnStatus;
    at: number;
};

function checkoutStatusStorageKey(orgId: string): string {
    return `azshrtr.billing.checkout.${orgId}`;
}

function readStoredCheckoutStatus(orgId: string): CheckoutReturnStatus | null {
    try {
        const raw = sessionStorage.getItem(checkoutStatusStorageKey(orgId));
        if (!raw) {
            return null;
        }
        const parsed = JSON.parse(raw) as StoredCheckoutStatus;
        if (
            !parsed ||
            typeof parsed.at !== 'number' ||
            Date.now() - parsed.at > CHECKOUT_STATUS_TTL_MS
        ) {
            sessionStorage.removeItem(checkoutStatusStorageKey(orgId));
            return null;
        }
        if (
            parsed.status === 'failed' ||
            parsed.status === 'succeeded' ||
            parsed.status === 'verifying'
        ) {
            return parsed.status;
        }
        return null;
    } catch {
        return null;
    }
}

function writeStoredCheckoutStatus(orgId: string, status: CheckoutReturnStatus): void {
    try {
        const payload: StoredCheckoutStatus = { status, at: Date.now() };
        sessionStorage.setItem(checkoutStatusStorageKey(orgId), JSON.stringify(payload));
    } catch {
        // Ignore quota / private mode.
    }
}

function formatInvoiceAmount(amount: number | null, currency: string | null): string {
    if (amount === null) {
        return '—';
    }

    const code = (currency ?? 'USD').toUpperCase();
    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: code,
        }).format(amount / 100);
    } catch {
        return `${(amount / 100).toFixed(2)} ${code}`;
    }
}

export function BillingPage() {
    const { t } = useI18n();
    const org = useActiveOrg();
    const orgId = org?.id;
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const intentFromUrl = normalizePlan(searchParams.get('intent'));
    const checkoutPending = searchParams.get('checkout') === 'pending';
    const returnStatus = (searchParams.get('status') ?? '').toLowerCase();
    const returnSubscriptionId = searchParams.get('subscription_id');
    const returnFailed =
        returnStatus === 'failed' || returnStatus === 'cancelled' || returnStatus === 'canceled';
    const returnSucceeded = ['succeeded', 'success', 'active', 'paid', 'completed'].includes(
        returnStatus,
    );
    const storedStatus = orgId ? readStoredCheckoutStatus(orgId) : null;
    const [selected, setSelected] = useState<SelectedPlan | null>(
        () => intentFromUrl ?? readSelectedPlan(),
    );
    const [info, setInfo] = useState<BillingInfo | null>(null);
    const [pending, setPending] = useState(false);
    const [confirmDowngrade, setConfirmDowngrade] = useState(false);
    const [verifying, setVerifying] = useState(
        () => (checkoutPending && !returnFailed) || returnSucceeded || storedStatus === 'verifying',
    );
    const [checkoutFailed, setCheckoutFailed] = useState(
        () => returnFailed || storedStatus === 'failed',
    );
    const [checkoutSucceeded, setCheckoutSucceeded] = useState(() => storedStatus === 'succeeded');
    const returnHandled = useRef(false);
    const [invoicePage, setInvoicePage] = useState(1);
    const [invoicePerPage, setInvoicePerPage] = useState(10);
    const [invoices, setInvoices] = useState<InvoiceRow[]>([]);
    const [invoiceMeta, setInvoiceMeta] = useState<PaginationMeta>(emptyPaginationMeta(10));
    const [invoicesLoading, setInvoicesLoading] = useState(false);
    const verifyStartedAt = useRef<number | null>(null);

    const refreshBilling = async (): Promise<BillingInfo | null> => {
        if (!orgId) {
            return null;
        }
        const data = await apiGet<BillingInfo>(`/api/v1/organizations/${orgId}/billing`);
        setInfo(data);
        return data;
    };

    const loadInvoices = async (page: number, perPage: number) => {
        if (!orgId) {
            return;
        }
        setInvoicesLoading(true);
        try {
            const payload = await apiGet<Paginated<InvoiceRow>>(
                `/api/v1/organizations/${orgId}/billing/invoices${paginationQuery(page, perPage)}`,
            );
            setInvoices(payload.data);
            setInvoiceMeta(metaFromPaginated(payload));
        } catch {
            setInvoices([]);
            setInvoiceMeta(emptyPaginationMeta(perPage));
        } finally {
            setInvoicesLoading(false);
        }
    };

    // biome-ignore lint/correctness/useExhaustiveDependencies: org-scoped bootstrap; avoid unstable fn deps
    useEffect(() => {
        if (!orgId) {
            return;
        }
        void refreshBilling();
    }, [orgId]);

    // biome-ignore lint/correctness/useExhaustiveDependencies: pagination + billing gate; avoid unstable fn deps
    useEffect(() => {
        if (!orgId || !info?.billing_enabled) {
            return;
        }
        void loadInvoices(invoicePage, invoicePerPage);
    }, [orgId, info?.billing_enabled, invoicePage, invoicePerPage]);

    useEffect(() => {
        if (intentFromUrl) {
            writeSelectedPlan(intentFromUrl);
            setSelected(intentFromUrl);
        }
    }, [intentFromUrl]);

    useEffect(() => {
        if (returnHandled.current) {
            return;
        }

        const hasReturnParams =
            checkoutPending ||
            returnStatus !== '' ||
            returnSubscriptionId !== null ||
            searchParams.has('email');

        if (!hasReturnParams) {
            return;
        }

        returnHandled.current = true;

        const next = new URLSearchParams(searchParams);
        next.delete('checkout');
        next.delete('status');
        next.delete('subscription_id');
        next.delete('email');
        setSearchParams(next, { replace: true });

        if (returnFailed) {
            setVerifying(false);
            setCheckoutFailed(true);
            setCheckoutSucceeded(false);
            if (orgId) {
                writeStoredCheckoutStatus(orgId, 'failed');
                void apiPost(`/api/v1/organizations/${orgId}/billing/checkout-return`, {
                    status: returnStatus || 'failed',
                    subscription_id: returnSubscriptionId,
                }).catch(() => {
                    // Banner still shows; webhook/email may arrive later.
                });
            }
            return;
        }

        // Success/pending return: poll for webhook-driven plan update only.
        setCheckoutFailed(false);
        setCheckoutSucceeded(false);
        setVerifying(true);
        verifyStartedAt.current = Date.now();
        if (orgId) {
            writeStoredCheckoutStatus(orgId, 'verifying');
            if (returnSucceeded || returnSubscriptionId) {
                void apiPost(`/api/v1/organizations/${orgId}/billing/checkout-return`, {
                    status: returnStatus || 'succeeded',
                    subscription_id: returnSubscriptionId,
                }).catch(() => {
                    // Plan still only updates via webhook.
                });
            }
        }
    }, [
        checkoutPending,
        returnFailed,
        returnSucceeded,
        returnStatus,
        returnSubscriptionId,
        orgId,
        searchParams,
        setSearchParams,
    ]);

    // biome-ignore lint/correctness/useExhaustiveDependencies: poll while verifying; avoid unstable fn/t deps
    useEffect(() => {
        if (!verifying || !orgId || checkoutFailed) {
            return;
        }

        let cancelled = false;
        let timer: ReturnType<typeof setTimeout> | undefined;

        const poll = async () => {
            if (cancelled) {
                return;
            }

            try {
                const data = await refreshBilling();
                if (cancelled) {
                    return;
                }

                if (data?.plan.slug === 'pro') {
                    setVerifying(false);
                    setCheckoutSucceeded(true);
                    writeStoredCheckoutStatus(orgId, 'succeeded');
                    toast.success(t('console.billing.payment_verified'), {
                        description: t('console.billing.payment_verified_description'),
                    });
                    void loadInvoices(invoicePage, invoicePerPage);
                    return;
                }
            } catch {
                // Keep verifying — plan only updates via webhook.
            }

            const started = verifyStartedAt.current ?? Date.now();
            if (Date.now() - started >= VERIFY_MAX_MS) {
                setVerifying(false);
                toast.info(t('console.billing.payment_verify_timeout'), {
                    description: t('console.billing.payment_verify_timeout_description'),
                });
                return;
            }

            timer = setTimeout(() => {
                void poll();
            }, VERIFY_POLL_MS);
        };

        void poll();

        return () => {
            cancelled = true;
            if (timer) {
                clearTimeout(timer);
            }
        };
    }, [verifying, orgId, checkoutFailed]);

    const choosePlan = (plan: SelectedPlan) => {
        writeSelectedPlan(plan);
        setSelected(plan);
        setSearchParams({ intent: plan }, { replace: true });
    };

    const continueWithFree = () => {
        clearSelectedPlan();
        setSelected(null);
        if (orgId) {
            void navigate(`/${orgId}`, { replace: true });
        }
    };

    const checkout = async () => {
        if (!orgId) {
            return;
        }
        setPending(true);
        try {
            const data = await apiPost<{ checkout_url: string }>(
                `/api/v1/organizations/${orgId}/billing/checkout`,
            );
            clearSelectedPlan();
            window.location.href = data.checkout_url;
        } catch (err) {
            toast.error(t('console.billing.checkout_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.billing.checkout_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const scheduleDowngrade = async () => {
        if (!orgId) {
            return;
        }
        setPending(true);
        try {
            const data = await apiPost<{
                subscription: SubscriptionInfo | null;
                plan: { name: string; slug: string };
            }>(`/api/v1/organizations/${orgId}/billing/cancel`);
            setInfo((prev) =>
                prev
                    ? {
                          ...prev,
                          plan: data.plan,
                          subscription: data.subscription,
                      }
                    : prev,
            );
            toast.success(t('console.billing.downgrade_scheduled_notice'), {
                description: t('console.billing.downgrade_scheduled_description'),
            });
            void refreshBilling();
        } catch (err) {
            toast.error(t('console.billing.downgrade_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.billing.downgrade_error_description'),
            });
            throw err;
        } finally {
            setPending(false);
        }
    };

    const keepPro = async () => {
        if (!orgId) {
            return;
        }
        setPending(true);
        try {
            const data = await apiPost<{
                subscription: SubscriptionInfo | null;
                plan: { name: string; slug: string };
            }>(`/api/v1/organizations/${orgId}/billing/resume`);
            setInfo((prev) =>
                prev
                    ? {
                          ...prev,
                          plan: data.plan,
                          subscription: data.subscription,
                      }
                    : prev,
            );
            toast.success(t('console.billing.keep_pro_notice'), {
                description: t('console.billing.keep_pro_description'),
            });
            void refreshBilling();
        } catch (err) {
            toast.error(t('console.billing.resume_error'), {
                description:
                    err instanceof ApiError
                        ? err.message
                        : t('console.billing.resume_error_description'),
            });
        } finally {
            setPending(false);
        }
    };

    const isPro = info?.plan.slug === 'pro';
    const cancelAt = info?.subscription?.cancel_at ?? null;
    const periodEnd = info?.subscription?.current_period_end ?? null;
    const canManageBilling = info?.can_manage_billing === true;
    const showPlanPicker = selected !== null && !isPro && canManageBilling;

    return (
        <section className="space-y-6">
            <PageHeader
                title={t('console.billing.title')}
                description={t('console.billing.description')}
            />

            {checkoutFailed ? (
                <div
                    className="rounded-[var(--radius-control)] border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-ink"
                    role="alert"
                >
                    {t('console.billing.payment_failed_return')}
                </div>
            ) : null}

            {checkoutSucceeded && !verifying && !checkoutFailed ? (
                <div
                    className="rounded-[var(--radius-control)] border border-teal/30 bg-teal/10 px-4 py-3 text-sm text-ink"
                    role="status"
                >
                    {t('console.billing.payment_verified')}
                </div>
            ) : null}

            {verifying && !checkoutFailed ? (
                <div
                    className="rounded-[var(--radius-control)] border border-teal/30 bg-teal/10 px-4 py-3 text-sm text-ink"
                    role="status"
                >
                    {t('console.billing.payment_verifying')}
                </div>
            ) : null}

            {showPlanPicker ? (
                <div className="console-panel space-y-4 p-4 sm:p-5">
                    <div>
                        <p className="text-sm text-ink-soft">
                            {t('console.billing.plan_from_pricing')}
                        </p>
                        <p className="mt-1 text-lg font-semibold text-ink">
                            {t(
                                selected === 'pro'
                                    ? 'console.billing.selected_pro'
                                    : 'console.billing.selected_free',
                            )}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant={selected === 'free' ? 'default' : 'outline'}
                            onClick={() => choosePlan('free')}
                        >
                            {t('console.billing.plan_free')}
                        </Button>
                        <Button
                            type="button"
                            variant={selected === 'pro' ? 'default' : 'outline'}
                            onClick={() => choosePlan('pro')}
                        >
                            {t('console.billing.plan_pro')}
                        </Button>
                    </div>

                    {selected === 'pro' ? (
                        info?.billing_enabled ? (
                            <Button
                                type="button"
                                disabled={pending || verifying}
                                onClick={() => void checkout()}
                                className="w-full sm:w-auto"
                            >
                                {pending
                                    ? t('common.loading')
                                    : t('console.billing.continue_pro_checkout')}
                            </Button>
                        ) : (
                            <p className="text-sm text-ink-soft">
                                {t('console.billing.self_host_disabled')}
                            </p>
                        )
                    ) : (
                        <Button
                            type="button"
                            onClick={continueWithFree}
                            className="w-full sm:w-auto"
                        >
                            {t('console.billing.continue_free')}
                        </Button>
                    )}

                    <p className="text-xs text-ink-soft">
                        {t('console.billing.change_anytime')}{' '}
                        <Link to={`/${orgId}`} className="text-teal hover:underline">
                            {t('console.billing.skip_for_now')}
                        </Link>
                    </p>
                </div>
            ) : null}

            {info === null ? (
                <ListSkeleton rows={3} withCheckbox={false} />
            ) : (
                <div className="console-panel space-y-5 p-4 sm:p-5">
                    <div className="space-y-1">
                        <p className="text-sm text-ink-soft">{t('console.billing.current_plan')}</p>
                        <p className="font-display text-xl font-semibold sm:text-2xl">
                            {info.plan.name}
                        </p>
                    </div>

                    {isPro && periodEnd ? (
                        <p className="text-sm text-ink-soft">
                            {cancelAt
                                ? t('console.billing.pro_until_cancel', {
                                      when: formatWhen(cancelAt),
                                  })
                                : t('console.billing.pro_renews', {
                                      when: formatWhen(periodEnd),
                                  })}
                        </p>
                    ) : null}

                    {!info.billing_enabled ? (
                        <p className="text-sm text-ink-soft">
                            {t('console.billing.billing_disabled')}
                        </p>
                    ) : null}

                    <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        {info.billing_enabled && !isPro && canManageBilling ? (
                            <Button
                                type="button"
                                disabled={pending || verifying}
                                onClick={() => void checkout()}
                                className="w-full sm:w-auto"
                            >
                                {pending ? t('common.loading') : t('console.billing.upgrade_pro')}
                            </Button>
                        ) : null}

                        {info.billing_enabled && isPro && !cancelAt ? (
                            <>
                                <p className="w-full text-sm text-success">
                                    {t('console.billing.on_pro')}
                                </p>
                                {canManageBilling ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={pending}
                                        onClick={() => setConfirmDowngrade(true)}
                                        className="w-full sm:w-auto"
                                    >
                                        {t('console.billing.downgrade_free')}
                                    </Button>
                                ) : null}
                            </>
                        ) : null}

                        {info.billing_enabled && isPro && cancelAt ? (
                            <>
                                <p className="w-full text-sm text-ink-soft">
                                    {t('console.billing.downgrade_pending', {
                                        when: formatWhen(cancelAt),
                                    })}
                                </p>
                                {canManageBilling ? (
                                    <Button
                                        type="button"
                                        disabled={pending}
                                        onClick={() => void keepPro()}
                                        className="w-full sm:w-auto"
                                    >
                                        {pending
                                            ? t('common.loading')
                                            : t('console.billing.keep_pro')}
                                    </Button>
                                ) : null}
                            </>
                        ) : null}
                    </div>

                    <div className="grid gap-3 border-t border-mist/60 pt-4 sm:grid-cols-2">
                        <div className="rounded-[var(--radius-control)] border border-mist/70 p-4">
                            <p className="font-display text-base font-semibold">
                                {t('console.billing.plan_free')}
                            </p>
                            <p className="mt-1 text-sm text-ink-soft">
                                {t('console.billing.free_blurb')}
                            </p>
                            {!isPro ? (
                                <p className="mt-3 text-xs font-semibold tracking-wide text-teal uppercase">
                                    {t('console.billing.current_badge')}
                                </p>
                            ) : null}
                        </div>
                        <div className="rounded-[var(--radius-control)] border border-mist/70 p-4">
                            <p className="font-display text-base font-semibold">
                                {t('console.billing.plan_pro')}
                            </p>
                            <p className="mt-1 text-sm text-ink-soft">
                                {t('console.billing.pro_blurb')}
                            </p>
                            {isPro ? (
                                <p className="mt-3 text-xs font-semibold tracking-wide text-teal uppercase">
                                    {t('console.billing.current_badge')}
                                </p>
                            ) : null}
                        </div>
                    </div>
                </div>
            )}

            {info?.billing_enabled ? (
                <div className="console-panel space-y-4 p-4 sm:p-5">
                    <div>
                        <h2 className="font-display text-lg font-semibold text-ink">
                            {t('console.billing.invoices_title')}
                        </h2>
                        <p className="mt-1 text-sm text-ink-soft">
                            {t('console.billing.invoices_description')}
                        </p>
                    </div>

                    {invoicesLoading ? <ListSkeleton rows={3} withCheckbox={false} /> : null}

                    {!invoicesLoading && invoices.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            className="border-0 bg-transparent px-0 py-8"
                            title={t('console.billing.invoices_empty_title')}
                            description={t('console.billing.invoices_empty_description')}
                        />
                    ) : null}

                    {!invoicesLoading && invoices.length > 0 ? (
                        <>
                            <ul className="divide-y divide-mist/60 rounded-[var(--radius-control)] border border-mist/70">
                                {invoices.map((invoice) => (
                                    <li
                                        key={invoice.id}
                                        className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0 space-y-0.5">
                                            <p className="truncate text-sm font-medium text-ink">
                                                {formatInvoiceAmount(
                                                    invoice.amount,
                                                    invoice.currency,
                                                )}
                                            </p>
                                            <p className="text-xs text-ink-soft">
                                                {invoice.created_at
                                                    ? formatWhen(invoice.created_at)
                                                    : t('common.em_dash')}
                                                {invoice.status ? ` · ${invoice.status}` : null}
                                            </p>
                                        </div>
                                        {invoice.invoice_url ? (
                                            <a
                                                href={invoice.invoice_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-sm font-medium text-teal hover:underline"
                                            >
                                                {t('console.billing.invoice_view')}
                                            </a>
                                        ) : (
                                            <span className="text-sm text-ink-soft">
                                                {t('common.em_dash')}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                            <Pagination
                                meta={invoiceMeta}
                                onPageChange={setInvoicePage}
                                onPerPageChange={(perPage) => {
                                    setInvoicePerPage(perPage);
                                    setInvoicePage(1);
                                }}
                            />
                        </>
                    ) : null}
                </div>
            ) : null}

            <ConfirmDelete
                open={confirmDowngrade}
                onOpenChange={setConfirmDowngrade}
                title={t('console.billing.downgrade_confirm_title')}
                description={t('console.billing.downgrade_confirm_description', {
                    when: periodEnd ? formatWhen(periodEnd) : t('common.em_dash'),
                })}
                confirmLabel={t('console.billing.downgrade_free')}
                confirmWord="downgrade"
                onConfirm={() => scheduleDowngrade()}
            />
        </section>
    );
}
