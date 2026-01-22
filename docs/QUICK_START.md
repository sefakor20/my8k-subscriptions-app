# my8k Subscriptions - Quick Start Guide

Get your my8k subscription platform up and running in 15 minutes.

---

## Prerequisites

- ✅ My8K reseller account with API key
- ✅ Stripe or Paystack account (or both)
- ✅ Server with PHP 8.3+, Composer, Node.js

---

## 5-Minute Setup

### Step 1: Install Dependencies

```bash
composer install
npm install
```

### Step 2: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Step 3: Configure Database

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=my8k
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

### Step 4: Configure My8K API

Add to `.env`:

```env
MY8K_API_BASE_URL=https://my8k.me/api/api.php
MY8K_API_KEY=your_api_key_here
```

### Step 5: Configure Payment Gateway

**For Stripe:**

```env
STRIPE_PUBLIC_KEY=pk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=USD
```

**For Paystack:**

```env
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx
PAYSTACK_WEBHOOK_SECRET=xxx
PAYSTACK_CURRENCY=GHS
```

### Step 6: Build Assets

```bash
npm run build
```

### Step 7: Start Services

```bash
# Terminal 1: Queue worker
php artisan queue:work

# Terminal 2: Development server (if not using Herd/Valet)
php artisan serve
```

---

## Create Your First Plan

### Option 1: Via Admin Panel

1. Visit `/admin/plans`
2. Click "Create Plan"
3. Fill in:
   - Name: "Basic Monthly"
   - Price: 9.99
   - Currency: USD
   - Billing Interval: Monthly
   - Duration Days: 30
   - Max Devices: 1
   - Features: `{"channels": "5000+", "quality": "HD"}`
   - Is Active: ✓
4. Click "Save"

### Option 2: Via Seeder (Development)

```bash
php artisan db:seed --class=PlanSeeder
```

Creates 9 pre-configured plans (Basic, Standard, Premium × Monthly, Quarterly, Yearly)

---

## Test Your First Subscription

### 1. Visit Pricing Page

Navigate to your site's homepage or `/checkout`

### 2. Select a Plan

Click "Get Started" on any active plan

### 3. Choose Payment Gateway

Select Stripe or Paystack

### 4. Complete Test Payment

**Stripe Test Card:**
- Number: `4242 4242 4242 4242`
- Expiry: Any future date
- CVC: Any 3 digits
- Email: `test@example.com`

**Paystack Test Card:**
- Number: `5060 6666 6666 6666 6666`
- Expiry: Any future date
- CVV: Any 3 digits
- Email: `test@example.com`

### 5. Verify Success

Check:
- ✓ Redirected to success page
- ✓ Email sent (check logs if using log mailer)
- ✓ Queue job processed provisioning
- ✓ Subscription created in database

```bash
php artisan tinker
Subscription::with('plan', 'user', 'serviceAccount')->latest()->first();
```

---

## Configure Webhooks

### Stripe Webhook

1. Go to https://dashboard.stripe.com/webhooks
2. Add endpoint: `https://your-domain.com/api/v1/webhooks/stripe`
3. Select events:
   - `checkout.session.completed`
   - `invoice.paid`
   - `invoice.payment_failed`
4. Copy signing secret → Add to `.env` as `STRIPE_WEBHOOK_SECRET`

### Paystack Webhook

1. Go to https://dashboard.paystack.com/settings/developer
2. Add URL: `https://your-domain.com/api/v1/webhooks/paystack`
3. Set secret → Add to `.env` as `PAYSTACK_WEBHOOK_SECRET`

---

## Setup Automation

### Queue Worker (Production)

Create supervisor config `/etc/supervisor/conf.d/my8k-worker.conf`:

```ini
[program:my8k-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/my8k/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/my8k/storage/logs/worker.log
```

Activate:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start my8k-worker:*
```

### Task Scheduler

Add to crontab:

```bash
* * * * * cd /var/www/my8k && php artisan schedule:run >> /dev/null 2>&1
```

This enables:
- Daily auto-renewals
- Expiration checks
- Email reminders

---

## Production Checklist

Before going live:

- [ ] Switch to live API keys (remove `test` keys)
- [ ] Configure production mail service (SMTP/Mailgun/etc)
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure webhooks with production URLs
- [ ] Set up supervisor for queue worker
- [ ] Configure cron for task scheduler
- [ ] Test full checkout flow with real card
- [ ] Test webhook delivery
- [ ] Test renewal process (or schedule test subscription)
- [ ] Set up error monitoring (optional: Sentry)
- [ ] Configure backups for database
- [ ] Review email templates and customize as needed

---

## Common Tasks

### Check Reseller Credits

```bash
php artisan my8k:check-credits
```

### Process Renewals Manually

```bash
php artisan subscriptions:renew --dry-run  # Test
php artisan subscriptions:renew            # Process
```

### View Failed Jobs

```bash
php artisan queue:failed
php artisan queue:retry {id}
php artisan queue:retry all
```

### Create Admin User

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'Admin',
    'email' => 'admin@my8k.me',
    'password' => bcrypt('secure-password'),
]);
```

---

## Getting Help

- **Full Documentation**: See `docs/SUBSCRIPTIONS_SETUP.md`
- **Application Logs**: `storage/logs/laravel.log`
- **Queue Logs**: `storage/logs/worker.log`
- **Test Mode**: Always test with gateway test keys first

---

## What's Next?

1. **Customize Plans** - Create plans that match your offerings
2. **Brand Emails** - Customize email templates in `resources/views/emails`
3. **Landing Page** - Customize the pricing section on your homepage
4. **Coupons** - Create promotional coupons for marketing campaigns
5. **Monitor** - Set up monitoring for subscriptions, renewals, and failures

---

**You're all set!** 🚀

Your my8k subscription platform is now ready to accept customers and process subscriptions.
