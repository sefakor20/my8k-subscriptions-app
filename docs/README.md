# my8k Subscriptions Documentation

Welcome to the my8k IPTV subscription platform documentation.

---

## 📚 Documentation Index

### Getting Started

- **[Quick Start Guide](QUICK_START.md)** - Get up and running in 15 minutes
- **[Subscriptions Setup Guide](SUBSCRIPTIONS_SETUP.md)** - Complete setup and configuration guide

### Quick Start (5 Minutes)

New to the platform? Follow these steps:

1. **Install dependencies**: `composer install && npm install`
2. **Setup environment**: `cp .env.example .env && php artisan key:generate`
3. **Configure database** and run `php artisan migrate`
4. **Add API credentials** (My8K, Stripe/Paystack)
5. **Build assets**: `npm run build`
6. **Start services**: `php artisan queue:work`
7. **Create plans** via `/admin/plans`
8. **Test checkout** with test payment cards

See [QUICK_START.md](QUICK_START.md) for detailed instructions.

---

## 🎯 What is my8k Subscriptions?

A complete IPTV subscription management platform featuring:

- ✅ Multi-gateway payments (Stripe, Paystack)
- ✅ Automated recurring billing
- ✅ Service provisioning via My8K API
- ✅ Customer self-service portal
- ✅ Admin management dashboard
- ✅ Plan changes with proration
- ✅ Credit & coupon system
- ✅ Email notifications
- ✅ Webhook integrations

---

## 🚀 Key Features

### For Customers
- Browse and purchase subscription plans
- Secure payment processing
- Automatic service provisioning
- Access IPTV credentials (M3U, EPG URLs)
- Self-service subscription management
- Plan upgrades/downgrades
- Payment history and invoices

### For Administrators
- Create and manage subscription plans
- Multi-currency and multi-gateway pricing
- Monitor subscriptions and revenue
- Handle refunds and credits
- Manage coupons and promotions
- View detailed analytics
- Manual provisioning and support

### For Developers
- Clean, modern Laravel 12 codebase
- Service-oriented architecture
- Comprehensive test coverage
- Gateway abstraction layer
- Webhook event handling
- Queue-based async processing
- Extensible and customizable

---

## 📖 Core Concepts

### Subscriptions

A subscription represents a customer's active service plan. Key properties:

- **Status**: Pending, Active, Suspended, Expired, Cancelled
- **Auto-renewal**: Automatic recurring billing
- **Grace period**: Keeps service active after payment failure
- **Credits**: Balance for refunds or promotions

### Plans

Plans define what customers can purchase:

- **Pricing**: Multi-currency, multi-gateway support
- **Billing intervals**: Monthly, Quarterly, Yearly
- **Features**: JSON-defined feature sets
- **Device limits**: Max concurrent connections

### Orders

Orders track payment transactions:

- **Gateway tracking**: Transaction IDs and metadata
- **Status**: Pending, Provisioned, Failed, Refunded
- **Idempotency**: Prevents duplicate processing

### Service Accounts

Provisioned IPTV accounts from My8K API:

- **Credentials**: Username and password
- **URLs**: M3U playlist and EPG guide
- **Expiration**: Synced with subscription

---

## 🛠 System Requirements

- PHP 8.3 or higher
- Composer 2.x
- Node.js 18+ and NPM
- MySQL 8.0+ or PostgreSQL 13+
- Redis (optional, for caching)
- Supervisor (production queue worker)
- Cron access (for scheduled tasks)

---

## 🔧 Configuration

### Environment Variables

Essential configuration in `.env`:

```env
# My8K API
MY8K_API_KEY=your_api_key
MY8K_API_BASE_URL=https://my8k.me/api/api.php

# Payment Gateway (choose one or both)
STRIPE_SECRET_KEY=sk_xxx
PAYSTACK_SECRET_KEY=sk_xxx

# Queue & Mail
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

See [SUBSCRIPTIONS_SETUP.md](SUBSCRIPTIONS_SETUP.md#initial-setup) for complete configuration.

### Webhook Endpoints

Configure these URLs in your payment gateway dashboards:

- **Stripe**: `https://your-domain.com/api/v1/webhooks/stripe`
- **Paystack**: `https://your-domain.com/api/v1/webhooks/paystack`

---

## 📋 Common Commands

### Subscription Management
```bash
# Process due renewals
php artisan subscriptions:renew

# Mark expired subscriptions
php artisan subscriptions:expire

# Check reseller credits
php artisan my8k:check-credits
```

### Queue Management
```bash
# Start queue worker
php artisan queue:work

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Development
```bash
# Run tests
php artisan test

# Seed sample plans
php artisan db:seed --class=PlanSeeder

# Start dev server
php artisan serve
```

---

## 🧪 Testing

### Test Payment Cards

**Stripe:**
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`

**Paystack:**
- Success: `5060 6666 6666 6666 6666`

### Running Tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --filter=Subscription

# With coverage
php artisan test --coverage
```

---

## 📊 Admin Access

### Routes

- **Plans Management**: `/admin/plans`
- **Subscriptions**: `/admin/subscriptions`
- **Plan Changes**: `/admin/plan-changes`

### Actions

Admins can:
- Create, edit, delete plans
- Configure multi-gateway pricing
- View all subscriptions
- Suspend/reactivate subscriptions
- Retry failed provisioning
- Process refunds

---

## 👥 Customer Portal

### Routes

- **Dashboard**: `/dashboard`
- **My Subscriptions**: `/dashboard/subscriptions`
- **Checkout**: `/checkout`

### Features

Customers can:
- View subscription details
- Access service credentials
- Download M3U playlists
- Toggle auto-renewal
- Change plans
- View payment history
- Cancel subscriptions

---

## 🔄 Subscription Lifecycle

```
Purchase → Pending → Payment → Provisioning → Active
                                     ↓
                                  Renewal
                                     ↓
                            Payment Success/Failure
                                     ↓
                        Active/Suspended/Expired
```

### Status Flow

1. **Pending** - Order created, awaiting payment confirmation
2. **Active** - Payment received, service provisioned and active
3. **Suspended** - Payment failed, in grace period or suspended
4. **Expired** - Subscription ended, renewal not processed
5. **Cancelled** - Manually cancelled by user or admin

---

## 🔐 Security

### Payment Security

- PCI-compliant payment processing (via Stripe/Paystack)
- No card data stored locally
- Webhook signature verification
- Idempotent payment processing

### Authentication

- Laravel Fortify for authentication
- Password hashing with bcrypt
- CSRF protection
- Rate limiting on sensitive endpoints

### Authorization

- Policy-based access control
- Admin vs customer role separation
- Subscription ownership verification

---

## 📧 Email Notifications

Automated emails for:

- Welcome new customers
- Account credentials ready
- Subscription expiring (7 days)
- Suspension warning (2 days)
- Renewal success/failure
- Payment failures
- Plan changes
- Invoices

Customize templates in `resources/views/emails/`

---

## 🐛 Debugging

### Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Filter for errors
tail -f storage/logs/laravel.log | grep ERROR

# Watch webhooks
tail -f storage/logs/laravel.log | grep Webhook
```

### Common Issues

See [SUBSCRIPTIONS_SETUP.md#troubleshooting](SUBSCRIPTIONS_SETUP.md#troubleshooting) for solutions to:

- Webhook delivery failures
- Provisioning errors
- Renewal processing issues
- Email notification problems

---

## 🎨 Customization

### Email Templates

Customize in `resources/views/emails/`:
- `welcome-new-customer.blade.php`
- `subscription-renewed.blade.php`
- `subscription-expiring-soon.blade.php`
- And more...

### Frontend Components

Built with Livewire + Flux UI:
- `app/Livewire/Dashboard/` - Customer components
- `app/Livewire/Admin/` - Admin components
- `resources/views/livewire/` - Component views

### Business Logic

Service classes in `app/Services/`:
- `SubscriptionOrderService` - Order creation
- `SubscriptionRenewalService` - Renewal processing
- `PlansService` - Plan management
- Payment gateway services

---

## 🌍 Multi-Currency Support

### Supported Currencies

- **Stripe**: USD, EUR, GBP, CAD, AUD, GHS, ZAR, KES
- **Paystack**: GHS, ZAR, USD, KES

### Gateway-Specific Pricing

Configure different prices per gateway and currency:

```
Plan: Premium Monthly
- Stripe/USD: $29.99
- Stripe/EUR: €26.99
- Paystack/GHS: ₵120.00
- Paystack/NGN: ₦14,500
```

Automatic currency selection based on customer's gateway choice.

---

## 📈 Monitoring

### Health Checks

```bash
# Basic ping
curl https://your-domain.com/api/health/ping

# Full health check
curl https://your-domain.com/api/health/

# Detailed (admin only)
curl https://your-domain.com/api/health/detailed
```

### Metrics to Monitor

- Subscription creation rate
- Renewal success rate
- Payment failure rate
- Provisioning success rate
- Queue job processing time
- Reseller credit balance

---

## 🤝 Support

### Resources

- **Documentation**: This directory
- **Application Logs**: `storage/logs/laravel.log`
- **Laravel Docs**: https://laravel.com/docs
- **Stripe Docs**: https://stripe.com/docs
- **Paystack Docs**: https://paystack.com/docs
- **My8K Support**: Contact My8K reseller support

### Getting Help

1. Check logs for errors
2. Review documentation
3. Test in development with debug mode
4. Check queue for failed jobs
5. Verify environment configuration

---

## 🗺 Roadmap

Future enhancements (examples):

- Multi-language support
- Additional payment gateways
- Mobile app integration
- Advanced analytics dashboard
- Affiliate program
- Gift subscriptions
- Family plans

---

## 📄 License

Proprietary - All rights reserved

---

## 🙏 Credits

Built with:
- Laravel 12
- Livewire 3
- Flux UI
- Tailwind CSS 4
- Stripe & Paystack APIs
- My8K IPTV API

---

**Version**: 1.0
**Last Updated**: 2026-01-22
