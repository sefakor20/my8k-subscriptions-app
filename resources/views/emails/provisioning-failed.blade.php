<x-mail::message>
# Provisioning Failed - Urgent Action Required

**Order:** #{{ $order->woocommerce_order_id }}
**Customer:** {{ $user->name }} ({{ $user->email }})
**Error Code:** {{ $errorCode }}

## Error Details

{{ $errorMessage }}

## Order Information

- **Subscription ID:** {{ $subscription->id }}
- **Plan:** {{ $subscription->plan->name }}
- **Order Date:** {{ $order->created_at->format('F j, Y g:i A') }}
- **Amount:** {{ $order->currency }} {{ number_format($order->amount, 2) }}

## Immediate Actions Required

1. Check My8K API credentials and connectivity
2. Verify the plan mapping is correct (Plan: {{ $subscription->plan->name }} → My8K Code: {{ $subscription->plan->my8k_plan_code }})
3. Review provisioning logs for detailed error information
4. Retry provisioning from the admin dashboard if the issue is resolved

<x-mail::button :url="config('app.url') . '/admin/orders/' . $order->id">
View Order Details
</x-mail::button>

**Retry Command:**
```
php artisan queue:retry {{ $order->id }}
```

This is an automated alert. Please investigate immediately to avoid customer dissatisfaction.

Thanks,<br>
{{ config('app.name') }} Monitoring System
</x-mail::message>
