<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hello {{ $user->name }},

Thank you for choosing our IPTV service! We're excited to have you as a customer.

## Complete Your Account Setup

Your account has been created successfully. To secure your account and set your own password, please click the button below:

<x-mail::button :url="$passwordResetUrl">
Set Your Password
</x-mail::button>

This link will expire in 60 minutes for security reasons.

## What's Next?

Once you've set your password and your order is processed:

1. **Account Provisioning** - We'll set up your IPTV service account (usually within a few minutes)
2. **Credentials Email** - You'll receive another email with your streaming credentials
3. **Start Watching** - Use your credentials with any IPTV player app

## Getting Started Guide

**Recommended IPTV Players:**
- **VLC Media Player** (Desktop - Free)
- **IPTV Smarters Pro** (Mobile & TV - Free)
- **TiviMate** (Android TV - Premium features available)

## Need Help?

Visit your dashboard for tutorials, FAQs, and support contact information.

<x-mail::button :url="config('app.url') . '/dashboard'">
Go to Dashboard
</x-mail::button>

We're here to ensure you have the best IPTV experience possible!

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
