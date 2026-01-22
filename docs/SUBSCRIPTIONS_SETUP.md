# my8k Subscription System Setup Guide

Complete guide for setting up and managing subscriptions in the my8k.me IPTV subscription application.

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Initial Setup](#initial-setup)
4. [Creating Plans](#creating-plans)
5. [Testing the Checkout Flow](#testing-the-checkout-flow)
6. [Subscription Management](#subscription-management)
7. [Automated Renewals](#automated-renewals)
8. [Webhook Configuration](#webhook-configuration)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [API Integration](#api-integration)
11. [Advanced Features](#advanced-features)
12. [Troubleshooting](#troubleshooting)

---

## Overview

The my8k subscription system is a **production-ready IPTV subscription platform** that provides:

### Key Features
- Multi-gateway payment processing (Stripe, Paystack, WooCommerce)
- Automated recurring billing with stored payment methods
- Self-service customer portal
- Admin management dashboard
- Plan changes (upgrades/downgrades) with proration
- Credit system for refunds and promotions
- Coupon/discount system
- Automated service provisioning via My8K API
- Email notifications for all subscription events
- Payment failure handling with grace periods
- Comprehensive webhook integrations

### System Architecture
- **Frontend**: Livewire + Tailwind CSS + Flux UI components
- **Backend**: Laravel 12 with PHP 8.3
- **Payment Gateways**: Stripe, Paystack, WooCommerce (sync)
- **Service Provider**: My8K IPTV API
- **Queue**: Database-backed job queue for async processing
- **Notifications**: Email-based with queued delivery

---

## Prerequisites

### Required Accounts

1. **My8K IPTV Reseller Account**
   - Sign up at https://my8k.me
   - Obtain API credentials from your reseller dashboard
   - Ensure you have sufficient reseller credits

2. **Payment Gateway Accounts** (choose one or both):
   - **Stripe**: Create account at https://stripe.com
   - **Paystack**: Create account at https://paystack.com

3. **Email Service** (production):
   - SMTP service (e.g., Mailgun, SendGrid, AWS SES)
   - Or use Laravel's built-in mail drivers

### Server Requirements
- PHP 8.3 or higher
- Composer
- Node.js & NPM
- MySQL/PostgreSQL or SQLite
- Queue worker process
- Cron/scheduler access

---

## Initial Setup

### Step 1: Database Setup

Run migrations to create all required tables:

```bash
php artisan migrate
```

This creates tables for:
- `subscriptions` - Core subscription records
- `plans` & `plan_prices` - Plan definitions and pricing
- `orders` - Payment orders
- `payment_transactions` - Payment tracking
- `service_accounts` - Provisioned IPTV credentials
- `invoices` - Generated invoices
- `coupons` & `coupon_redemptions` - Discount system
- Additional supporting tables

### Step 2: Environment Configuration

Copy `.env.example` to `.env` if you haven't already:

```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Configure My8K API

Add your My8K reseller credentials to `.env`:

```env
# My8K IPTV Provisioning API
MY8K_API_BASE_URL=https://my8k.me/api/api.php
MY8K_API_KEY=your_api_key_here
MY8K_API_TIMEOUT=30
```

**How to get your API key:**
1. Login to your My8K reseller account at https://my8k.me
2. Navigate to API settings
3. Copy your API key

### Step 4: Configure Payment Gateways

#### Option A: Stripe Configuration

Add Stripe credentials to `.env`:

```env
# Stripe Payment Gateway
STRIPE_PUBLIC_KEY=pk_test_xxx  # Use pk_live_xxx for production
STRIPE_SECRET_KEY=sk_test_xxx  # Use sk_live_xxx for production
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=USD
```

**How to get Stripe credentials:**
1. Login to https://dashboard.stripe.com
2. Navigate to Developers → API keys
3. Copy your publishable key (pk_xxx) and secret key (sk_xxx)
4. For webhook secret, see [Webhook Configuration](#webhook-configuration)

**Supported currencies:** USD, EUR, GBP, CAD, AUD, GHS, ZAR, KES

#### Option B: Paystack Configuration

Add Paystack credentials to `.env`:

```env
# Paystack Payment Gateway
PAYSTACK_PUBLIC_KEY=pk_test_xxx  # Use pk_live_xxx for production
PAYSTACK_SECRET_KEY=sk_test_xxx  # Use sk_live_xxx for production
PAYSTACK_WEBHOOK_SECRET=your_webhook_secret
PAYSTACK_BASE_URL=https://api.paystack.co
PAYSTACK_CURRENCY=GHS
```

**How to get Paystack credentials:**
1. Login to https://dashboard.paystack.com
2. Navigate to Settings → API Keys & Webhooks
3. Copy your public key and secret key
4. For webhook secret, see [Webhook Configuration](#webhook-configuration)

**Supported currencies:** GHS, ZAR, USD, KES

#### Option C: WooCommerce Integration (Optional)

If you want to sync subscriptions from an existing WooCommerce store:

```env
# WooCommerce API Integration
WOOCOMMERCE_STORE_URL=https://yourstore.com
WOOCOMMERCE_CONSUMER_KEY=ck_xxx
WOOCOMMERCE_CONSUMER_SECRET=cs_xxx
WOOCOMMERCE_WEBHOOK_SECRET=your_webhook_secret
WOOCOMMERCE_API_VERSION=wc/v3
```

### Step 5: Email Configuration

For **development**, use log driver (already configured):

```env
MAIL_MAILER=log
```

For **production**, configure SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@my8k.me"
MAIL_FROM_NAME="my8k Subscriptions"
```

### Step 6: Queue Configuration

The app uses queued jobs for:
- Service provisioning
- Email notifications
- Payment processing

Ensure queue is configured in `.env`:

```env
QUEUE_CONNECTION=database
```

Start the queue worker:

```bash
php artisan queue:work --tries=3 --timeout=90
```

For production, use a process manager like Supervisor:

```ini
[program:my8k-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### Step 7: Task Scheduler

Add to your server's crontab:

```bash
* * * * * cd /path/to/my8k-subscriptions-app && php artisan schedule:run >> /dev/null 2>&1
```

This runs scheduled tasks including:
- Daily subscription renewals
- Expiration checks
- Suspension warnings
- Credit balance notifications

### Step 8: Build Frontend Assets

```bash
npm install
npm run build
```

For development:

```bash
npm run dev
```

---

## Creating Plans

### Access Admin Panel

1. Create an admin user (if you haven't already):

```bash
php artisan tinker
```

```php
$admin = User::create([
    'name' => 'Admin',
    'email' => 'admin@my8k.me',
    'password' => bcrypt('password'),
]);
```

2. Visit the admin plans page:
   - URL: `https://your-domain.com/admin/plans`
   - Login with your admin credentials

### Create Your First Plan

Click **"Create Plan"** and fill in:

#### Basic Information
- **Name**: e.g., "Basic Monthly", "Premium Yearly"
- **Slug**: Auto-generated from name (e.g., "basic-monthly")
- **Description**: Brief description of the plan

#### Pricing
- **Price**: Base price (e.g., 9.99)
- **Currency**: USD, EUR, GBP, GHS, etc.
- **Billing Interval**: Monthly, Quarterly, or Yearly
- **Duration Days**: How long subscription lasts (e.g., 30 for monthly)

#### Features
- **Max Devices**: Maximum concurrent devices (e.g., 1, 3, 5)
- **Features** (JSON format):

```json
{
  "channels": "5000+",
  "vod_movies": "10000+",
  "vod_series": "5000+",
  "quality": "4K/HD/SD",
  "epg": true,
  "catchup": "7 days",
  "anti_freeze": true
}
```

#### Gateway Integration (Optional)
- **WooCommerce ID**: Product ID from your WooCommerce store
- **My8K Plan Code**: Specific plan code from My8K (if required)
- **Paystack Plan Code**: Plan code from Paystack dashboard
- **Stripe Price ID**: Price ID from Stripe dashboard

#### Status
- **Is Active**: Check to make plan available for purchase

### Configure Multi-Gateway Pricing (Optional)

After creating a plan, you can add different prices for different gateways and currencies:

1. Click **"Manage Pricing"** on the plan
2. Add price for specific gateway/currency combination:
   - Gateway: Default (Any), Paystack, or Stripe
   - Currency: GHS, USD, NGN, EUR, GBP, KES, ZAR
   - Price: Amount in that currency
   - Active: Enable/disable this price

**Example configuration:**
- Default/USD: $9.99
- Paystack/GHS: ₵45.00
- Paystack/NGN: ₦5,500
- Stripe/EUR: €8.99

This allows customers to pay in their local currency automatically.

### Seed Sample Plans (Development Only)

For testing, you can seed sample plans:

```bash
php artisan db:seed --class=PlanSeeder
php artisan db:seed --class=PlanPriceSeeder
```

This creates:
- Basic Monthly Plan ($9.99)
- Standard Monthly Plan ($19.99)
- Premium Monthly Plan ($29.99)
- Quarterly and Yearly variants

---

## Testing the Checkout Flow

### Enable Test Mode

Use test API keys from your payment gateway:
- Stripe: Keys starting with `pk_test_` and `sk_test_`
- Paystack: Keys starting with `pk_test_` and `sk_test_`

### Test Payment Cards

**Stripe Test Cards:**
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Auth required: `4000 0025 0000 3155`
- Any future expiry date, any CVC

**Paystack Test Cards:**
- Success: `5060 6666 6666 6666 6666`
- Success (PIN): `5078 5078 5078 5078 0408` (PIN: 1111, OTP: 123456)
- Insufficient funds: `5060 6666 6666 6666 6611`

### Complete Test Purchase

1. Visit your site's homepage or pricing page
2. Click "Get Started" on a plan
3. Select payment gateway (Stripe or Paystack)
4. Enter test email: `test@example.com`
5. Complete payment with test card
6. You should be redirected to success page
7. Check provisioning:

```bash
php artisan queue:work
```

8. Verify subscription created:

```bash
php artisan tinker
```

```php
Subscription::with('plan', 'user', 'serviceAccount')->latest()->first();
```

### Verify Email Notifications

Check logs for sent emails:

```bash
tail -f storage/logs/laravel.log | grep "Mail"
```

You should see:
- Welcome email (for new users)
- Account credentials ready email
- Invoice generated email

---

## Subscription Management

### Admin Management Features

Access admin dashboard at `/admin/subscriptions`

#### Available Filters
- Search by user email or name
- Filter by status (Active, Pending, Suspended, Expired, Cancelled)
- Filter by plan
- Filter by date range

#### Available Actions
- **View Details**: See full subscription info
- **Manual Provision**: Retry service provisioning if failed
- **Suspend**: Temporarily disable subscription
- **Reactivate**: Re-enable suspended subscription
- **Cancel**: Permanently cancel subscription

#### Subscription Details View
- Subscription status and dates
- Associated plan and pricing
- User information
- Service account credentials
- Payment history (orders)
- Plan change history
- Activity timeline

### Customer Self-Service Features

Customers can access their subscriptions at `/dashboard/subscriptions`

#### Customer Portal Features
- View all subscriptions
- See subscription status and expiry
- Access service credentials (username, password)
- View M3U and EPG URLs with QR codes
- Download M3U playlist file
- Toggle auto-renewal on/off
- Manually renew subscription
- Change plan (upgrade/downgrade)
- View payment history
- See activity timeline

#### Activity Timeline Includes
- Subscription creation
- Payment events
- Service provisioning
- Plan changes
- Renewals
- Cancellations

---

## Automated Renewals

### How Auto-Renewal Works

1. **Subscription created** with `auto_renew = true` (default)
2. **Payment method stored** by gateway during initial checkout:
   - Stripe: Customer ID + Payment Method ID
   - Paystack: Authorization code + Email
3. **Renewal scheduled** based on `next_renewal_at` timestamp
4. **Command runs daily** to process renewals
5. **Payment charged** using stored payment method
6. **Subscription extended** on successful payment
7. **Email sent** confirming renewal

### Renewal Command

Manual renewal processing:

```bash
# Process all due renewals
php artisan subscriptions:renew

# Dry run (test without charging)
php artisan subscriptions:renew --dry-run

# Limit number of renewals
php artisan subscriptions:renew --limit=50

# Renew specific subscription
php artisan subscriptions:renew --subscription=uuid-here
```

### Scheduled Task

The renewal command runs automatically via task scheduler. Verify scheduling:

```bash
php artisan schedule:list
```

You should see:
- `subscriptions:renew` - Daily at 00:00
- `subscriptions:expire` - Daily at 01:00

### Payment Failure Handling

When renewal payment fails:

1. **Failure recorded** - Increments `payment_failure_count`
2. **Notification sent** - Email to customer
3. **Grace period starts** - Subscription stays active
4. **Warning sent** - 2 days before expiry
5. **Subscription suspended** - When grace period expires
6. **Auto-renew disabled** - After 3 consecutive failures

**Grace Period Logic:**
- Subscription remains Active for configured grace period
- Customer can still use service
- Renewal will be retried on next scheduled date
- After 3 failures, auto-renew is automatically disabled

### Credit-Based Renewals

If customer has credit balance:

1. Credit is applied first
2. If credit covers full amount, renewal is free
3. If partial credit, remaining amount is charged
4. Credit balance is deducted
5. Renewal succeeds without gateway charge

---

## Webhook Configuration

Webhooks enable real-time payment notifications and subscription updates.

### Stripe Webhook Setup

1. Login to https://dashboard.stripe.com
2. Navigate to **Developers → Webhooks**
3. Click **Add endpoint**
4. Enter webhook URL:
   ```
   https://your-domain.com/api/v1/webhooks/stripe
   ```
5. Select events to listen to:
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `invoice.paid`
   - `invoice.payment_failed`
   - `charge.refunded`
   - `customer.subscription.deleted`
6. Copy the **Signing secret** (starts with `whsec_`)
7. Add to `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_xxx
   ```

### Paystack Webhook Setup

1. Login to https://dashboard.paystack.com
2. Navigate to **Settings → API Keys & Webhooks**
3. Scroll to **Webhooks** section
4. Enter webhook URL:
   ```
   https://your-domain.com/api/v1/webhooks/paystack
   ```
5. Set a webhook secret (random string)
6. Add to `.env`:
   ```env
   PAYSTACK_WEBHOOK_SECRET=your_secret_here
   ```

### Testing Webhooks Locally

Use a tunneling service like ngrok or Expose:

```bash
# Using Laravel Herd (if available)
herd share

# Or using Expose
expose share https://my8k-subscriptions-app.test

# Or using ngrok
ngrok http 80
```

This provides a public URL like `https://abc123.ngrok.io` that forwards to your local app.

Update webhook URLs in gateway dashboards to use this temporary URL.

### Verify Webhook Configuration

Test webhook delivery:

1. **Stripe**: Use Stripe CLI
   ```bash
   stripe listen --forward-to localhost/api/v1/webhooks/stripe
   stripe trigger checkout.session.completed
   ```

2. **Paystack**: Make a test payment and check logs
   ```bash
   tail -f storage/logs/laravel.log | grep "Webhook"
   ```

---

## Monitoring & Maintenance

### Console Commands

#### Subscription Management
```bash
# Process renewals
php artisan subscriptions:renew

# Check for expired subscriptions
php artisan subscriptions:expire

# Send expiration reminders
php artisan subscriptions:send-expiration-reminders
```

#### Health Checks
```bash
# Check system health
curl https://your-domain.com/api/health/detailed

# Check reseller credits
php artisan my8k:check-credits
```

### Scheduled Tasks

All scheduled tasks are configured in `bootstrap/app.php`. View schedule:

```bash
php artisan schedule:list
```

**Default schedule:**
- **Daily 00:00** - Process auto-renewals
- **Daily 01:00** - Mark expired subscriptions
- **Daily 02:00** - Send expiration reminders (7 days before)
- **Daily 03:00** - Send suspension warnings (2 days before)

### Email Notifications

The system sends these automated emails:

**Customer Emails:**
- Welcome email (new users)
- Subscription created
- Account credentials ready
- Subscription expiring soon (7 days)
- Suspension warning (2 days)
- Subscription renewed
- Renewal failed
- Payment failure reminder
- Subscription suspended
- Subscription cancelled
- Plan change confirmed
- Plan change scheduled
- Invoice generated

**Admin Emails (if configured):**
- Provisioning failed
- Low reseller credits
- Payment gateway errors

### Monitoring Queue Jobs

Check queue status:

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Log Monitoring

Important logs to monitor:

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs (if using supervisor)
tail -f /var/log/supervisor/my8k-worker.log

# Payment gateway interactions
tail -f storage/logs/laravel.log | grep "Gateway"

# Webhook events
tail -f storage/logs/laravel.log | grep "Webhook"
```

### Health Check Endpoints

The app provides health check endpoints:

```bash
# Basic ping
curl https://your-domain.com/api/health/ping
# Response: {"status":"ok"}

# Standard health check
curl https://your-domain.com/api/health/
# Response: {"status":"healthy","timestamp":"..."}

# Detailed health check (admin only)
curl https://your-domain.com/api/health/detailed
# Response includes database, queue, cache, storage status
```

---

## API Integration

### My8K Service Provisioning

The app integrates with My8K IPTV API for service provisioning.

#### How It Works

1. **Order completed** → `ProvisionNewAccountJob` dispatched
2. **Job processes** → Calls My8K API to create account
3. **Account created** → Credentials saved to `service_accounts` table
4. **Customer notified** → Email with credentials sent

#### API Endpoints Used

The app uses these My8K API endpoints:

- `create_account` - Create new IPTV account
- `get_account` - Retrieve account details
- `update_account` - Update account settings
- `delete_account` - Deactivate account
- `check_credits` - Check reseller credit balance

#### Account Credentials

Provisioned accounts include:

- **Username** - IPTV username
- **Password** - IPTV password
- **M3U URL** - Playlist URL
- **EPG URL** - Electronic Program Guide URL
- **Expires At** - Account expiration date
- **Max Devices** - Concurrent device limit

#### Reseller Credits

Check your reseller credits:

```bash
php artisan my8k:check-credits
```

Monitor credits to ensure sufficient balance for provisioning.

**Low Credit Alerts:**
- System sends alert when credits below threshold
- Configure alert threshold in app settings
- Add credits through My8K reseller dashboard

#### Manual Provisioning

If automatic provisioning fails, admin can retry:

1. Go to `/admin/subscriptions`
2. Find the subscription
3. Click **"Manual Provision"**
4. Job is re-dispatched

Or via command line:

```bash
php artisan tinker
```

```php
$subscription = Subscription::find('uuid-here');
dispatch(new ProvisionNewAccountJob($subscription));
```

---

## Advanced Features

### Plan Changes (Upgrades/Downgrades)

Customers can change their subscription plan at any time.

#### How Plan Changes Work

**Immediate Change (with proration):**
1. Customer selects new plan
2. Pro-rata calculation:
   - Unused time on current plan → Credit
   - New plan cost → Charge
   - Net amount = New plan - Credit
3. Payment processed for net amount
4. Subscription updated immediately
5. Service re-provisioned if needed

**Scheduled Change (at next renewal):**
1. Customer selects new plan
2. Plan change scheduled for next renewal date
3. Current plan remains active until renewal
4. At renewal, new plan activated
5. New plan price charged

#### Pro-Rata Calculation Example

Current plan: $30/month (Premium)
Days used: 10 out of 30
Days remaining: 20

New plan: $10/month (Basic)

```
Credit = ($30 / 30) × 20 = $20
New plan cost = $10
Net charge = $10 - $20 = -$10 (credit balance)
```

Result: Customer gets $10 credit on account

#### Enable Plan Changes

Plan changes are enabled by default. Customers access via:
- Dashboard → My Subscriptions → Click subscription → "Change Plan"

### Credits and Refunds

#### Credit System

Credits can be added to subscription accounts:

```bash
php artisan tinker
```

```php
$subscription = Subscription::find('uuid-here');
$subscription->addCredit(10.00); // Add $10 credit
```

**Credit is automatically applied** at next renewal.

#### Processing Refunds

Refunds can be issued through payment gateways:

**Via Stripe:**
```php
$stripeGateway = app(StripeGateway::class);
$stripeGateway->processRefund($order, $amount, 'Refund reason');
```

**Via Paystack:**
```php
$paystackGateway = app(PaystackGateway::class);
$paystackGateway->processRefund($order, $amount, 'Refund reason');
```

When refund is processed:
1. Gateway refunds payment
2. Credit added to subscription
3. Customer notified via email

### Coupon System

#### Creating Coupons

Create discount coupons for promotions:

```bash
php artisan tinker
```

```php
$coupon = Coupon::create([
    'code' => 'WELCOME20',
    'discount_type' => DiscountType::Percentage,
    'discount_value' => 20, // 20% off
    'max_uses' => 100,
    'valid_from' => now(),
    'valid_until' => now()->addMonth(),
    'trial_days' => 7, // Optional: Add 7 day trial
    'is_active' => true,
]);

// Limit to specific plans
$coupon->plans()->attach($planId);
```

#### Applying Coupons

Customers apply coupons at checkout:
1. Select plan
2. Enter coupon code in checkout form
3. Discount applied to order
4. Coupon usage tracked in `coupon_redemptions`

#### Coupon Validation

The system validates:
- Coupon code exists and is active
- Within valid date range
- Has remaining uses
- Applicable to selected plan
- Not already used by customer (if one-time)

### Grace Periods

Grace periods keep subscriptions active after payment failure.

#### Configuration

Grace period is calculated from subscription `expires_at` date.

#### Grace Period Flow

```
Day 0: Payment fails
Day 1-N: Subscription stays Active (grace period)
Day N-2: Suspension warning sent
Day N: Subscription marked as Suspended
```

#### Check Grace Period Status

```bash
php artisan tinker
```

```php
$subscription = Subscription::find('uuid-here');
$subscription->isInGracePeriod(); // true/false
$subscription->gracePeriodExpired(); // true/false
```

---

## Troubleshooting

### Common Issues

#### Issue: Webhooks not received

**Symptoms:**
- Payments succeed but orders not created
- Subscription stays in "Pending" status

**Solutions:**
1. Verify webhook URL is publicly accessible
2. Check webhook secret is correct in `.env`
3. Check gateway dashboard for webhook delivery logs
4. Look for errors in `storage/logs/laravel.log`
5. Test with webhook testing tools (Stripe CLI, Paystack test)

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep "Webhook"
```

#### Issue: Provisioning fails

**Symptoms:**
- Order created but no service account
- Email "Provisioning Failed" sent

**Solutions:**
1. Check My8K API credentials in `.env`
2. Verify reseller credits balance
3. Check queue worker is running
4. Review failed jobs

**Debug:**
```bash
php artisan queue:failed

# View job details
php artisan tinker
DB::table('failed_jobs')->latest()->first();

# Retry provisioning
php artisan queue:retry {id}
```

#### Issue: Renewals not processing

**Symptoms:**
- Subscriptions expire despite auto-renew enabled
- No renewal attempts in logs

**Solutions:**
1. Verify cron is running (`php artisan schedule:run`)
2. Check queue worker is running
3. Verify payment method is stored on order
4. Check gateway credentials are valid

**Debug:**
```bash
# Test renewal command
php artisan subscriptions:renew --dry-run

# Check subscription renewal status
php artisan tinker
$sub = Subscription::find('uuid-here');
$sub->next_renewal_at;
$sub->auto_renew;
```

#### Issue: Email notifications not sending

**Symptoms:**
- No emails received
- Queue jobs succeed but no emails

**Solutions:**
1. Check mail configuration in `.env`
2. Verify queue worker is running
3. Check mail logs
4. Test email connection

**Debug:**
```bash
# Test email
php artisan tinker
Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'));

# Check mail logs
tail -f storage/logs/laravel.log | grep "Mail"
```

#### Issue: Payment fails with "No payment method"

**Symptoms:**
- Renewal fails with "No stored payment method"
- Order has no authorization data

**Solutions:**
1. Verify gateway is storing payment method on initial checkout
2. Check order `gateway_metadata` field has authorization data
3. Re-subscribe customer to store new payment method

**Debug:**
```bash
php artisan tinker
$order = Order::where('subscription_id', 'uuid')->latest()->first();
$order->gateway_metadata; // Should contain authorization data
```

### Log Files

Important log locations:

```bash
# Application logs
storage/logs/laravel.log

# Queue worker logs (if using supervisor)
/var/log/supervisor/my8k-worker.log

# Nginx/Apache logs
/var/log/nginx/error.log
/var/log/nginx/access.log
```

### Database Queries for Debugging

Useful database queries:

```bash
php artisan tinker
```

```php
// Find subscriptions expiring soon
Subscription::expiringSoon(7)->get();

// Find subscriptions needing suspension warning
Subscription::needingSuspensionWarning()->get();

// Find subscriptions ready for suspension
Subscription::readyForSuspension()->get();

// Find failed orders
Order::where('status', OrderStatus::ProvisioningFailed)->get();

// Find payment failures
Subscription::whereNotNull('payment_failed_at')->get();

// Check recent orders
Order::with('user', 'subscription.plan')->latest()->limit(10)->get();

// Check provisioning jobs
DB::table('jobs')->where('queue', 'default')->get();
```

### Support Resources

If you need additional help:

1. **Check logs first** - Most issues are logged in `storage/logs/laravel.log`
2. **Review queue** - Many operations are queued, check failed jobs
3. **Test mode** - Use gateway test keys to debug payment issues
4. **Gateway docs**:
   - Stripe: https://stripe.com/docs
   - Paystack: https://paystack.com/docs
5. **My8K Support** - Contact My8K for provisioning API issues
6. **Laravel docs** - https://laravel.com/docs

### Debug Mode

For development, enable debug mode:

```env
APP_DEBUG=true
APP_ENV=local
```

**Never enable debug mode in production** - it exposes sensitive information.

---

## Quick Reference

### Environment Variables Checklist

```env
# App
APP_NAME=my8k
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_DATABASE=my8k

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_STORE=database

# Mail
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@my8k.me

# My8K API
MY8K_API_BASE_URL=https://my8k.me/api/api.php
MY8K_API_KEY=your_key

# Payment Gateways
STRIPE_PUBLIC_KEY=pk_xxx
STRIPE_SECRET_KEY=sk_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=USD

PAYSTACK_PUBLIC_KEY=pk_xxx
PAYSTACK_SECRET_KEY=sk_xxx
PAYSTACK_WEBHOOK_SECRET=xxx
PAYSTACK_CURRENCY=GHS
```

### Webhook URLs

```
Stripe:   https://your-domain.com/api/v1/webhooks/stripe
Paystack: https://your-domain.com/api/v1/webhooks/paystack
WooCommerce: https://your-domain.com/api/v1/webhooks/woocommerce/{event}
```

### Important Commands

```bash
# Setup
php artisan migrate
php artisan queue:work
php artisan schedule:run

# Subscriptions
php artisan subscriptions:renew
php artisan subscriptions:expire

# Queue Management
php artisan queue:failed
php artisan queue:retry all

# Health Check
php artisan my8k:check-credits

# Testing
php artisan test --filter=Subscription
```

### Admin Routes

```
Plans: /admin/plans
Subscriptions: /admin/subscriptions
Plan Changes: /admin/plan-changes
```

### Customer Routes

```
Dashboard: /dashboard
Subscriptions: /dashboard/subscriptions
Checkout: /checkout
```

---

## Next Steps

Now that you've completed setup:

1. ✅ Create your production plans
2. ✅ Test checkout flow with test cards
3. ✅ Configure webhooks in production
4. ✅ Set up queue worker with supervisor
5. ✅ Configure cron for scheduled tasks
6. ✅ Set up email service for production
7. ✅ Monitor logs for first few days
8. ✅ Test renewal process
9. ✅ Review and customize email templates
10. ✅ Set up monitoring/alerting

---

**Documentation Version:** 1.0
**Last Updated:** 2026-01-22
**Application:** my8k Subscriptions v1.0
