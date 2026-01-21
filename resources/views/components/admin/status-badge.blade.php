@props(['status'])

@php
    use App\Enums\OrderStatus;
    use App\Enums\SubscriptionStatus;
    use App\Enums\ServiceAccountStatus;
    use App\Enums\ProvisioningAction;

    // Determine the color based on the status
    $color = match(true) {
        // Subscription Statuses
        $status === SubscriptionStatus::Active => 'green',
        $status === SubscriptionStatus::Pending => 'yellow',
        $status === SubscriptionStatus::Expired => 'red',
        $status === SubscriptionStatus::Suspended => 'zinc',
        $status === SubscriptionStatus::Cancelled => 'zinc',

        // Order Statuses
        $status === OrderStatus::PendingProvisioning => 'yellow',
        $status === OrderStatus::Provisioned => 'green',
        $status === OrderStatus::ProvisioningFailed => 'red',

        // Service Account Statuses
        $status === ServiceAccountStatus::Active => 'green',
        $status === ServiceAccountStatus::Suspended => 'zinc',
        $status === ServiceAccountStatus::Expired => 'red',

        // Provisioning Actions
        $status === ProvisioningAction::Create => 'blue',
        $status === ProvisioningAction::Extend => 'blue',
        $status === ProvisioningAction::Suspend => 'yellow',

        // Default
        default => 'zinc',
    };

    $label = $status->value ?? (string) $status;
@endphp

<flux:badge :color="$color" size="sm">
    {{ $label }}
</flux:badge>
