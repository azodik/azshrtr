<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserRegistered = 'user.registered';
    case UserLoggedIn = 'user.logged_in';
    case OrganizationCreated = 'organization.created';
    case MemberInvited = 'member.invited';
    case MemberJoined = 'member.joined';
    case MemberRemoved = 'member.removed';
    case MemberRoleUpdated = 'member.role_updated';
    case InvitationRevoked = 'invitation.revoked';
    case LinkCreated = 'link.created';
    case LinkUpdated = 'link.updated';
    case LinkDeleted = 'link.deleted';
    case LinkClaimed = 'link.claimed';
    case LinkPasswordSet = 'link.password_set';
    case QrGenerated = 'qr.generated';
    case DomainAdded = 'domain.added';
    case DomainVerified = 'domain.verified';
    case DomainDeleted = 'domain.deleted';
    case ApiKeyCreated = 'api_key.created';
    case ApiKeyRevoked = 'api_key.revoked';
    case BillingUpgraded = 'billing.upgraded';
    case BillingCancelled = 'billing.cancelled';
    case BillingCheckoutStarted = 'billing.checkout_started';
    case BillingPaymentSucceeded = 'billing.payment_succeeded';
    case BillingPaymentFailed = 'billing.payment_failed';
    case BillingCheckoutAbandoned = 'billing.checkout_abandoned';
    case BillingRefundInitiated = 'billing.refund_initiated';
    case BillingRefundSucceeded = 'billing.refund_succeeded';
    case ExportCompleted = 'export.completed';
    case ImportCompleted = 'import.completed';
    case MfaEnabled = 'mfa.enabled';
    case MfaDisabled = 'mfa.disabled';
    case ProfileUpdated = 'profile.updated';
    case PasswordChanged = 'password.changed';
    case PasskeyRegistered = 'passkey.registered';
    case PasskeyDeleted = 'passkey.deleted';
}
