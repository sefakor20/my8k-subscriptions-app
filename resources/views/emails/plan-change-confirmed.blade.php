<x-mail::message>
# Plan {{ $planChange->isUpgrade() ? 'Upgrade' : 'Downgrade' }} Confirmed

Hello {{ $user->name }},

Your subscription plan has been successfully {{ $planChange->isUpgrade() ? 'upgraded' : 'changed' }}!

## Change Details

- **Previous Plan:** {{ $fromPlan->name }}
- **New Plan:** {{ $toPlan->name }}
- **Change Type:** {{ $planChange->type->label() }}
- **Effective Date:** {{ $planChange->executed_at->format('F j, Y') }}

@if($planChange->isUpgrade() && $planChange->proration_amount > 0)
## Payment Summary

You paid a prorated amount of **{{ $planChange->formattedProrationAmount() }}** for the upgrade.
@endif

@if($planChange->isDowngrade() && $planChange->credit_amount > 0)
## Credit Applied

A credit of **{{ $planChange->formattedCreditAmount() }}** has been applied to your account and will be used towards your next renewal.
@endif

## Your New Plan Benefits

**{{ $toPlan->name }}**
- Duration: {{ $toPlan->duration_days }} days
- Max Devices: {{ $toPlan->max_devices }}
@if($toPlan->features && is_array($toPlan->features))
@foreach($toPlan->features as $feature)
- {{ $feature }}
@endforeach
@endif

<x-mail::button :url="config('app.url') . '/dashboard'">
View Dashboard
</x-mail::button>

If you have any questions about your plan change, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
