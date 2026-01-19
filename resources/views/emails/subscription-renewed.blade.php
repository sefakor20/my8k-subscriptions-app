<x-mail::message>
# Subscription Renewed Successfully

Hello {{ $user->name }},

Great news! Your IPTV subscription has been automatically renewed.

## Renewal Details

- **Plan:** {{ $plan->name }}
- **Amount Charged:** {{ $order->formattedAmount() }}
- **Payment Date:** {{ $order->paid_at->format('F j, Y g:i A') }}
- **New Expiry Date:** {{ $subscription->expires_at->format('F j, Y g:i A') }}

## What's Next?

Your service continues uninterrupted. Enjoy your IPTV experience!

<x-mail::button :url="config('app.url') . '/dashboard'">
View Your Subscription
</x-mail::button>

If you have any questions about this renewal or wish to make changes to your subscription, please visit your account dashboard or contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
