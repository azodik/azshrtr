export const AUDIT_ACTION_VALUES = [
    'user.registered',
    'user.logged_in',
    'organization.created',
    'member.invited',
    'member.joined',
    'member.removed',
    'member.role_updated',
    'invitation.revoked',
    'link.created',
    'link.updated',
    'link.deleted',
    'link.claimed',
    'link.password_set',
    'qr.generated',
    'domain.added',
    'domain.verified',
    'domain.deleted',
    'api_key.created',
    'api_key.revoked',
    'billing.upgraded',
    'billing.cancelled',
    'billing.checkout_started',
    'billing.payment_succeeded',
    'billing.payment_failed',
    'billing.checkout_abandoned',
    'billing.refund_initiated',
    'billing.refund_succeeded',
    'export.completed',
    'import.completed',
    'mfa.enabled',
    'mfa.disabled',
    'profile.updated',
    'password.changed',
    'passkey.registered',
    'passkey.deleted',
] as const;

export type AuditActionValue = (typeof AUDIT_ACTION_VALUES)[number];

export function isAuditActionValue(value: string): value is AuditActionValue {
    return AUDIT_ACTION_VALUES.some((action) => action === value);
}

export function auditActionKey(action: string): string {
    return `console.audit.action.${action}`;
}
