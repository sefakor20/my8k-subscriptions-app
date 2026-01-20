<x-mail::message>
# Plan Change Scheduled

Hello {{ $user->name }},

Your plan change has been scheduled and will take effect at your next renewal.

## Scheduled Change Details

- **Current Plan:** {{ $fromPlan->name }}
- **New Plan:** {{ $toPlan->name }}
- **Scheduled Date:** {{ $planChange->scheduled_at->format('F j, Y') }}

@if($planChange->credit_amount > 0)
## Credit to Apply

When the change takes effect, a credit of **{{ $planChange->formattedCreditAmount() }}** will be applied to your account.
@endif

## What Happens Next

1. Your current plan ({{ $fromPlan->name }}) will remain active until {{ $subscription->expires_at->format('F j, Y') }}
2. At renewal, your subscription will automatically switch to {{ $toPlan->name }}
3. You will be charged the new plan price at renewal

You can cancel this scheduled change at any time before it takes effect.

<x-mail::button :url="config('app.url') . '/dashboard'">
Manage Subscription
</x-mail::button>

If you have any questions, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
