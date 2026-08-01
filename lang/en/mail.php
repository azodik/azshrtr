<?php

return [
    'verify_email' => [
        'subject' => 'One quick step — verify your email',
        'heading' => 'We’re glad you’re here',
        'hi' => 'Hi :name,',
        'body' => 'Thanks for joining azshrtr. Confirm your email with the code below, or tap the button — then you’re free to create links and share them.',
        'cta' => 'Verify email',
        'muted' => 'This link and code expire in 24 hours. If you didn’t sign up, you can safely ignore this.',
        'thanks' => 'Welcome aboard,',
    ],

    'email_otp' => [
        'subject' => 'Your sign-in code for azshrtr',
        'heading' => 'Here’s your code',
        'hi' => 'Hi :name,',
        'body' => 'Use this one-time code to sign in. We’re looking forward to seeing you again.',
        'muted' => 'This code expires in 10 minutes. If you didn’t ask for it, you can ignore this email — your account stays safe.',
        'thanks' => 'See you inside,',
    ],

    'password_reset' => [
        'subject' => 'Let’s get you back into azshrtr',
        'heading' => 'Reset your password',
        'hi' => 'Hi :name,',
        'body' => 'No worries — it happens. We received a request to reset your password. Choose a new one below and you’ll be back in moments.',
        'cta' => 'Choose a new password',
        'muted' => 'This link expires in :minutes minutes. If you didn’t ask for a reset, your password stays the same and you can ignore this.',
        'thanks' => 'We’re here if you need us,',
    ],

    'welcome' => [
        'subject' => 'Welcome to azshrtr — we’re happy you’re here',
        'heading' => 'Welcome aboard',
        'hi' => 'Hi :name,',
        'body' => 'Your account is ready. Create short links, QR codes, and share them from your workspace — we’re excited to see what you build.',
        'cta' => 'Open the console',
        'muted' => 'If you didn’t create this account, you can ignore this email.',
        'thanks' => 'Glad to have you,',
    ],

    'sign_in_activity' => [
        'subject' => 'Just checking — new sign-in on your account',
        'heading' => 'We noticed a new sign-in',
        'hi' => 'Hi :name,',
        'body' => 'Someone signed in to your azshrtr account. If that was you, you’re all set. If it wasn’t, let’s lock things down together.',
        'time' => 'When: :time',
        'ip' => 'IP address: :ip',
        'device' => 'Device: :device',
        'muted' => 'If this looks unfamiliar, reset your password right away — we’ll help you keep your workspace safe.',
        'cta' => 'Secure my account',
        'thanks' => 'Looking out for you,',
        'unknown_device' => 'Unknown device',
        'unknown_ip' => 'Unknown',
    ],

    'usage_warning' => [
        'subject' => 'Heads up — you’re at :threshold% of your :metric limit',
        'heading' => 'You’re getting close',
        'hi' => 'Hi :name,',
        'body' => '**:organization** has used **:used** of **:limit** :metric (:percent%) on the :plan plan. No rush — just a friendly nudge so nothing catches you by surprise.',
        'threshold' => 'You’ve crossed the **:threshold%** mark.',
        'cta' => 'See plans',
        'muted' => 'Pro gives you room to grow whenever you’re ready — or you can wait for the next monthly reset.',
        'thanks' => 'Cheering you on,',
        'metric_links' => 'links',
        'metric_qr' => 'QR codes',
    ],

    'usage_limit' => [
        'subject' => 'You’ve reached your :metric limit — we’ve got you',
        'heading' => 'You’ve hit a plan limit',
        'hi' => 'Hi :name,',
        'body' => '**:organization** has used all available :metric on the :plan plan (**:used** / **:limit**). That’s a sign things are going well — Pro can give you unlimited room when you’re ready.',
        'cta' => 'Upgrade to Pro',
        'muted' => 'You can upgrade anytime, or wait until your monthly allowance resets. Either way, we’re glad you’re building with us.',
        'thanks' => 'Thanks for growing with us,',
        'metric_links' => 'links',
        'metric_qr' => 'QR codes',
    ],

    'subscription_upgraded' => [
        'subject' => 'Welcome to Pro — we’re thrilled to have you',
        'heading' => 'You’re on Pro',
        'hi' => 'Hi :name,',
        'body' => 'Wonderful news — **:organization** is on Pro. Enjoy unlimited links & QR codes, custom domains, and password-protected links. We’re excited to grow with you.',
        'cta' => 'Open your workspace',
        'muted' => 'You’re billed once a year. You can manage your plan anytime from Billing.',
        'thanks' => 'Thanks for believing in us,',
    ],

    'subscription_downgrade_scheduled' => [
        'subject' => 'We’ll miss you on Pro — :organization',
        'heading' => 'We’re sorry to see you go',
        'hi' => 'Hi :name,',
        'body' => 'You’ve asked to move **:organization** to Free. Totally okay — you’ll keep everything Pro offers until the end of your current billing period.',
        'effective' => 'Free begins on **:date**.',
        'cta' => 'Stay on Pro',
        'muted' => 'Changed your mind? You can keep Pro with one click before that date — we’d truly love to have you stay.',
        'thanks' => 'With appreciation,',
    ],

    'subscription_downgraded' => [
        'subject' => 'We’re sorry to see you go — :organization',
        'heading' => 'We’re sorry to see you go',
        'hi' => 'Hi :name,',
        'body' => '**:organization** is now on Free. We’re grateful you tried Pro, and the door stays open — you can still create links and QR codes within Free limits, and Pro is here whenever you want unlimited room again.',
        'cta' => 'Come back to Pro',
        'muted' => 'Whenever you’re ready, unlimited links, custom domains, and password-protected links are one click away. We’d love to welcome you back.',
        'thanks' => 'Thank you for being with us,',
    ],

    'billing_payment_succeeded' => [
        'subject' => 'Thank you — Pro is active for :organization',
        'heading' => 'You’re all set — thank you',
        'hi' => 'Hi :name,',
        'body' => 'Your payment went through, and **:organization** is on Pro. We’re grateful for your support and can’t wait to see what you create.',
        'amount' => 'Amount charged: **:amount**.',
        'cta' => 'Open Billing',
        'muted' => 'You can view receipts anytime from Billing.',
        'thanks' => 'Thanks for supporting azshrtr,',
    ],

    'billing_payment_failed' => [
        'subject' => 'Let’s finish your Pro upgrade — :organization',
        'heading' => 'That payment didn’t go through',
        'hi' => 'Hi :name,',
        'body' => 'We couldn’t complete the payment for **:organization**. Nothing was charged, and your workspace is unchanged — no stress. Try again whenever you’re ready; we’re here for you.',
        'amount' => 'Attempted amount: **:amount**.',
        'cta' => 'Try Pro again',
        'muted' => 'A different card or payment method often helps. Take your time — we’ll be ready when you are.',
        'thanks' => 'Rooting for you,',
    ],

    'billing_checkout_abandoned' => [
        'subject' => 'Still thinking about Pro? We’re here — :organization',
        'heading' => 'Your Pro upgrade is waiting',
        'hi' => 'Hi :name,',
        'body' => 'You started upgrading **:organization** to Pro but didn’t finish. Totally fine — nothing was charged. Whenever you’re ready, pick up right where you left off.',
        'cta' => 'Continue to Pro',
        'muted' => 'If you didn’t mean to upgrade, you can ignore this email. No pressure at all.',
        'thanks' => 'We’re in your corner,',
    ],

    'billing_refund_initiated' => [
        'subject' => 'Your refund is on the way — :organization',
        'heading' => 'We’ve started your refund',
        'hi' => 'Hi :name,',
        'body' => 'We’ve started a refund for **:organization**. We know money matters — banks usually need a few business days to show it on your statement.',
        'amount' => 'Refund amount: **:amount**.',
        'cta' => 'Open Billing',
        'muted' => 'You’ll get another email from us when the refund is complete.',
        'thanks' => 'Thanks for your patience,',
    ],

    'billing_refund_succeeded' => [
        'subject' => 'Your refund is complete — :organization',
        'heading' => 'Your refund is complete',
        'hi' => 'Hi :name,',
        'body' => 'Good news — the refund for **:organization** is complete. It should appear on your original payment method soon if it hasn’t already. We’re sorry things didn’t work out the way you’d hoped.',
        'amount' => 'Refunded: **:amount**.',
        'cta' => 'Open Billing',
        'muted' => 'If you ever want to come back, Pro is ready whenever you are. Questions? You can manage billing anytime from your workspace.',
        'thanks' => 'Wishing you well,',
    ],

    'invitation_role' => [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'member' => 'Member',
    ],

    'invitation_invited' => [
        'subject' => 'You’re invited to join :organization',
        'heading' => 'You’ve been invited',
        'hi' => 'Hi :name,',
        'body' => '**:inviter** invited you to join **:organization** as a **:role**. We’d love to have you on the team — accept below to get started.',
        'expires' => 'This invitation expires on **:date**.',
        'cta' => 'Accept invitation',
        'muted' => 'You’ll need to sign in with this email address to join. If you weren’t expecting this, you can ignore the email.',
        'thanks' => 'Looking forward to having you,',
    ],

    'invitation_resent' => [
        'subject' => 'Reminder — you’re invited to :organization',
        'heading' => 'Your invitation is waiting',
        'hi' => 'Hi :name,',
        'body' => 'Just a friendly nudge — **:inviter** invited you to join **:organization** as a **:role**. Here’s a fresh link whenever you’re ready.',
        'expires' => 'This invitation expires on **:date**.',
        'cta' => 'Accept invitation',
        'muted' => 'Sign in with this email to join. If you don’t want to join, you can ignore this email.',
        'thanks' => 'Hope to see you soon,',
    ],

    'invitation_accepted' => [
        'subject' => 'Welcome to :organization',
        'heading' => 'You’re in — welcome',
        'hi' => 'Hi :name,',
        'body' => 'You’re now part of **:organization** as a **:role**. We’re glad you’re here — open the workspace and say hello to the team.',
        'cta' => 'Open workspace',
        'muted' => 'If anything looks off, reach out to the person who invited you.',
        'thanks' => 'Welcome aboard,',
    ],

    'invitation_accepted_admin' => [
        'subject' => ':member joined :organization',
        'heading' => 'Someone new joined your team',
        'hi' => 'Hi :name,',
        'body' => '**:member** accepted their invitation and joined **:organization** as a **:role**. Nice to grow the team.',
        'cta' => 'View members',
        'muted' => 'You can manage roles anytime from Members.',
        'thanks' => 'Happy collaborating,',
    ],

    'invitation_revoked' => [
        'subject' => 'Invitation to :organization was withdrawn',
        'heading' => 'That invitation was withdrawn',
        'hi' => 'Hi :name,',
        'body' => 'The invitation for you to join **:organization** as a **:role** was withdrawn by **:inviter**. No action is needed on your side.',
        'cta' => 'Visit azshrtr',
        'muted' => 'If you think this was a mistake, ask the workspace owner or admin to send a new invite.',
        'thanks' => 'Take care,',
    ],
];
