<x-mail::message>
# Payment Failed - Action Required

Hello {{ $user->name }},

We were unable to process your payment for your IPTV subscription renewal.

## Subscription Details

- **Plan:** {{ $plan->name }}
- **Payment Method:** {{ $paymentMethod }}
- **Next Renewal Date:** {{ $subscription->next_renewal_at->format('F j, Y') }}

## What Happens Next?

Your subscription will remain active for a grace period. However, if payment is not received, your service will be suspended on {{ $subscription->expires_at->format('F j, Y') }}.

## How to Resolve This

1. **Update Your Payment Method** - Ensure your payment details are current
2. **Check Your Card Balance** - Make sure you have sufficient funds
3. **Contact Your Bank** - They may have declined the transaction
4. **Manual Payment** - You can manually renew your subscription

<x-mail::button :url="config('app.url') . '/dashboard'">
Update Payment Method
</x-mail::button>

## Need Help?

If you're experiencing issues or have questions about this payment failure, our support team is here to help.

**Important:** Failure to resolve this payment issue will result in service suspension.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
