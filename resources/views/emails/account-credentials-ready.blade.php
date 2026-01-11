<x-mail::message>
# Your IPTV Account is Ready!

Hello {{ $user->name }},

Great news! Your IPTV service account has been successfully provisioned and is ready to use.

## Your Account Details

**Username:** {{ $serviceAccount->username }}
**Password:** {{ $serviceAccount->password }}
**Server URL:** {{ $serviceAccount->server_url }}

**M3U URL:**
```
{{ $serviceAccount->m3u_url }}
```

## Subscription Information

- **Plan:** {{ $subscription->plan->name }}
- **Expires:** {{ $subscription->expires_at->format('F j, Y') }}
- **Status:** {{ $subscription->status->value }}

## How to Get Started

1. **Download an IPTV Player** - We recommend VLC Media Player, IPTV Smarters, or TiviMate
2. **Add Your M3U URL** - Copy the M3U URL above and paste it into your IPTV player
3. **Enjoy** - Start streaming your favorite content!

<x-mail::button :url="config('app.url') . '/dashboard'">
View Your Dashboard
</x-mail::button>

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
