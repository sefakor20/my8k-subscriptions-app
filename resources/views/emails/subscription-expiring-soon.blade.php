<x-mail::message>
# Your Subscription Expires Soon

Hello {{ $user->name }},

This is a friendly reminder that your IPTV subscription will expire in **{{ $daysUntilExpiry }} day{{ $daysUntilExpiry > 1 ? 's' : '' }}**.

## Subscription Details

- **Plan:** {{ $plan->name }}
- **Expires:** {{ $subscription->expires_at->format('F j, Y g:i A') }}
- **Status:** {{ $subscription->status->value }}
@if($subscription->auto_renew)
- **Auto-Renewal:** Enabled ✓
@else
- **Auto-Renewal:** Disabled
@endif

@if($subscription->auto_renew)
## Your subscription will automatically renew

Your payment method will be charged on the renewal date. No action is needed from you.

If you wish to cancel auto-renewal, please update your subscription settings before the expiration date.
@else
## Action Required to Continue Service

Your subscription is set to expire and will not automatically renew. To avoid service interruption:

1. Visit your account dashboard
2. Renew your subscription
3. Or enable auto-renewal for uninterrupted service

**Important:** After expiration, your IPTV service will be suspended and you'll lose access to your streams.
@endif

<x-mail::button :url="config('app.url') . '/dashboard'">
Manage Subscription
</x-mail::button>

If you have any questions, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
