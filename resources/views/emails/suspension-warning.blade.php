<x-mail::message>
# Urgent: Subscription Suspension Warning

Hello {{ $user->name }},

**Your IPTV subscription will be suspended in {{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}** due to a failed payment.

## Subscription Details

- **Plan:** {{ $plan->name }}
- **Suspension Date:** {{ $subscription->expires_at->format('F j, Y') }}
- **Payment Attempts:** {{ $subscription->payment_failure_count }}

## Immediate Action Required

To avoid service interruption, please update your payment method and complete the payment before {{ $subscription->expires_at->format('F j, Y') }}.

<x-mail::button :url="config('app.url') . '/dashboard'">
Update Payment Now
</x-mail::button>

## What Happens If I Don't Pay?

If payment is not received by the suspension date:
- Your IPTV service will be **immediately suspended**
- You will lose access to all channels and content
- Your service credentials will be deactivated

## Need Help?

If you're having trouble with your payment or need assistance, please contact our support team immediately.

**Don't lose your service - act now!**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
