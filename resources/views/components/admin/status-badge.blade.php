@props(['status'])

@php
    use App\Enums\OrderStatus;
    use App\Enums\SubscriptionStatus;
    use App\Enums\ServiceAccountStatus;
    use App\Enums\ProvisioningAction;

    // Determine the color variant based on the status
    $variant = match(true) {
        // Subscription Statuses
        $status === SubscriptionStatus::Active => 'success',
        $status === SubscriptionStatus::Pending => 'warning',
        $status === SubscriptionStatus::Expired => 'danger',
        $status === SubscriptionStatus::Suspended => 'muted',
        $status === SubscriptionStatus::Cancelled => 'muted',

        // Order Statuses
        $status === OrderStatus::PendingProvisioning => 'warning',
        $status === OrderStatus::Provisioned => 'success',
        $status === OrderStatus::ProvisioningFailed => 'danger',

        // Service Account Statuses
        $status === ServiceAccountStatus::Active => 'success',
        $status === ServiceAccountStatus::Suspended => 'muted',
        $status === ServiceAccountStatus::Expired => 'danger',

        // Provisioning Actions
        $status === ProvisioningAction::Create => 'info',
        $status === ProvisioningAction::Extend => 'info',
        $status === ProvisioningAction::Suspend => 'warning',

        // Default
        default => 'muted',
    };

    $label = $status->value ?? (string) $status;
@endphp

<flux:badge :variant="$variant" size="sm">
    {{ $label }}
</flux:badge>
