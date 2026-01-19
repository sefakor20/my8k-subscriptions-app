<x-mail::message>
# Subscription Renewal Failed

Hello {{ $user->name }},

We were unable to automatically renew your IPTV subscription. Your service may be interrupted if action is not taken.

## Subscription Details

- **Plan:** {{ $plan->name }}
- **Current Expiry:** {{ $subscription->expires_at->format('F j, Y g:i A') }}
- **Status:** {{ $subscription->status->value }}

## Why This Happened

The automatic renewal could not be completed due to: **{{ $reason }}**

This can happen when:
- Your payment card has expired or was declined
- Insufficient funds in your account
- Your bank blocked the transaction

## Action Required

To avoid losing access to your IPTV service:

1. Visit your account dashboard
2. Update your payment information
3. Manually renew your subscription

<x-mail::button :url="config('app.url') . '/checkout'">
Renew Subscription Now
</x-mail::button>

**Important:** Your subscription will expire on {{ $subscription->expires_at->format('F j, Y') }}. After this date, your service will be suspended.

If you believe this is an error or need assistance, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
