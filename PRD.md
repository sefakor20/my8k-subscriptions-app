# Product Requirements Document
## Laravel Provisioning Service for My8K IPTV Subscriptions

---

**Document Version:** 1.0
**Date:** January 10, 2026
**Author:** Product Management & Solutions Architecture
**Status:** Draft for Review

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Background & Context](#background--context)
3. [Problem Statement](#problem-statement)
4. [Goals & Objectives](#goals--objectives)
5. [System Architecture](#system-architecture)
6. [Data Model & Entities](#data-model--entities)
7. [Feature Requirements](#feature-requirements)
8. [Integration Specifications](#integration-specifications)
9. [Subscription Lifecycle](#subscription-lifecycle)
10. [Queue & Reliability](#queue--reliability)
11. [Admin Interface](#admin-interface)
12. [Security Requirements](#security-requirements)
13. [Non-Functional Requirements](#non-functional-requirements)
14. [Success Metrics](#success-metrics)
15. [Implementation Roadmap](#implementation-roadmap)
16. [Risks & Mitigations](#risks--mitigations)
17. [Acceptance Criteria](#acceptance-criteria)

---

## 1. Executive Summary

**Product Name:** Laravel Provisioning Service for My8K IPTV Subscriptions

**Mission:** Build a reliable, automated system that handles end-to-end subscription management—from customer checkout to My8K IPTV account provisioning—with queue-based processing, comprehensive retry logic, and full operational visibility.

**Target Users:**
- **End Customers:** Purchase and manage IPTV subscriptions
- **Administrators:** Monitor provisioning, handle exceptions, analyze metrics
- **System Integrators:** WooCommerce (payment events), My8K API (provisioning)

**Core Value Proposition:**
- **Reliability:** Queue-based provisioning with automatic retries
- **Scalability:** Horizontal scaling via Laravel queues
- **Observability:** Full audit trail and admin analytics
- **Automation:** Zero-touch provisioning for successful payments

---

## 2. Background & Context

### Current State
The application is a **Laravel 12 + Livewire 3 starter kit** with:
- User authentication (Fortify with 2FA support)
- Modern UI (Flux UI Free + Tailwind CSS v4)
- Queue infrastructure (database driver, ready for use)
- Comprehensive test suite (Pest v4)

### Business Context
The business sells IPTV subscription products. Currently:
- **Payment Processing:** External (or to be integrated)
- **Service Provisioning:** Manual or unreliable
- **Customer Experience:** Delayed activation, manual intervention required
- **Operations:** High support burden, no visibility into failures

### Strategic Drivers
1. **Automation:** Eliminate manual provisioning steps
2. **Customer Satisfaction:** Instant activation post-payment
3. **Operational Efficiency:** Reduce support tickets and manual work
4. **Scalability:** Handle growth without proportional ops overhead
5. **Reliability:** Graceful handling of API failures and retries

---

## 3. Problem Statement

**Current Pain Points:**
1. Manual provisioning is error-prone and slow
2. No systematic retry mechanism for API failures
3. Synchronous processing blocks payment flow
4. Duplicate provisioning risks when retrying manually
5. Zero visibility into provisioning status and failure rates
6. No audit trail for compliance or debugging

**Business Impact:**
- Customer churn due to delayed activations
- High operational costs from manual intervention
- Revenue leakage from failed provisioning
- Poor customer experience and support burden

**Technical Constraints:**
- Third-party API (My8K) may experience downtime
- Network failures between systems
- Rate limits and quota constraints
- Event deduplication challenges

---

## 4. Goals & Objectives

### Primary Goals
1. **Automate 95%+ of provisioning** with zero human intervention
2. **Reduce activation time** from hours/days to <5 minutes
3. **Achieve 99.5% provisioning success rate** (with retries)
4. **Provide real-time visibility** into provisioning status

### Secondary Goals
1. **Handle subscription lifecycle** (creation, renewal, suspension, cancellation)
2. **Ensure idempotency** across all provisioning operations
3. **Build comprehensive audit trail** for compliance
4. **Enable operational analytics** for continuous improvement

### Out of Scope (Phase 1)
- Multi-tenant / white-label functionality
- Customer-facing API for third-party integrations
- Advanced reporting/BI dashboards (beyond admin analytics)
- Content delivery or streaming infrastructure

---

## 5. System Architecture

### 5.1 High-Level Architecture

```
┌─────────────────┐          ┌──────────────────────────────────┐
│                 │          │                                  │
│  WooCommerce    │──────────▶│  Laravel Provisioning Service   │
│  (Payment)      │ Webhooks │                                  │
│                 │          │  ┌──────────────────────────┐   │
└─────────────────┘          │  │                          │   │
                             │  │   Web Interface          │   │
                             │  │   (Customer + Admin)     │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   API Endpoints          │   │
                             │  │   (Webhooks, REST)       │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   Event Processing       │   │
                             │  │   (Validation, Dedup)    │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   Queue System           │   │
                             │  │   (Database/Redis)       │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   Provisioning Engine    │   │
                             │  │   (Jobs, Retry Logic)    │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   My8K API Client        │   │
                             │  │   (HTTP, Auth, Errors)   │   │
                             │  │                          │   │
                             │  └────────┬─────────────────┘   │
                             │           │                     │
                             │  ┌────────▼─────────────────┐   │
                             │  │                          │   │
                             │  │   Database               │   │
                             │  │   (Orders, Accounts,     │   │
                             │  │    Audit Logs)           │   │
                             │  │                          │   │
                             │  └──────────────────────────┘   │
                             │                                  │
                             └──────────────────────────────────┘
                                          │
                                          │ API Calls
                                          ▼
                             ┌──────────────────────────────────┐
                             │                                  │
                             │       My8K IPTV API             │
                             │    (Provision, Extend, Query)    │
                             │                                  │
                             └──────────────────────────────────┘
```

### 5.2 Request Lifecycle

#### Successful Provisioning Flow

```
1. Payment Success (WooCommerce)
   │
   ├─▶ Webhook POST /webhooks/woocommerce/order-completed
   │
   ├─▶ Validate signature & payload
   │
   ├─▶ Check for duplicate (idempotency key)
   │
   ├─▶ Store Order record (status: pending_provisioning)
   │
   ├─▶ Dispatch ProvisionAccountJob to queue
   │
   └─▶ Return 200 OK to WooCommerce

2. Queue Worker Picks Up Job
   │
   ├─▶ Load Order and related data
   │
   ├─▶ Determine provisioning action (create/extend)
   │
   ├─▶ Call My8K API
   │   ├─▶ POST /create-account OR /extend-account
   │   └─▶ Receive My8K account credentials
   │
   ├─▶ Store ServiceAccount record
   │
   ├─▶ Update Order status: provisioned
   │
   ├─▶ Create ProvisioningLog entry
   │
   └─▶ Send confirmation email to customer

3. Customer Experience
   │
   └─▶ Receives email with credentials within 5 minutes
```

#### Failure & Retry Flow

```
1. Payment Success (WooCommerce)
   │
   └─▶ ... (same as above)

2. Queue Worker: First Attempt
   │
   ├─▶ My8K API call fails (timeout/500 error)
   │
   ├─▶ Log failure in ProvisioningLog
   │
   ├─▶ Laravel releases job back to queue
   │
   └─▶ Exponential backoff: retry after 10 seconds

3. Queue Worker: Retry #2
   │
   ├─▶ My8K API still failing
   │
   ├─▶ Retry after 30 seconds

4. Queue Worker: Retry #3
   │
   ├─▶ My8K API returns success
   │
   ├─▶ Complete provisioning (same as successful flow)
   │
   └─▶ Update ProvisioningLog with success

5. If All Retries Exhausted (e.g., 5 attempts)
   │
   ├─▶ Job fails, moved to failed_jobs table
   │
   ├─▶ Update Order status: provisioning_failed
   │
   ├─▶ Create alert for admin dashboard
   │
   └─▶ Admin can manually retry via UI
```

### 5.3 Component Interactions

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│             │         │             │         │             │
│  Customer   │────────▶│ WooCommerce │────────▶│   Laravel   │
│             │ Payment │             │ Webhook │             │
└─────────────┘         └─────────────┘         └──────┬──────┘
                                                        │
                                                   Dispatch Job
                                                        │
                                                        ▼
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│             │         │             │         │             │
│   My8K API  │◀────────│ Queue Worker│◀────────│    Queue    │
│             │ Provision│             │ Process │             │
└─────────────┘         └─────────────┘         └─────────────┘
      │                       │
      │ Credentials          │ Update
      │                       │
      ▼                       ▼
┌─────────────┐         ┌─────────────┐
│             │         │             │
│  Customer   │◀────────│  Database   │
│   (Email)   │  Notify │             │
└─────────────┘         └─────────────┘
```

---

## 6. Data Model & Entities

### 6.1 Core Entities

#### 6.1.1 Plans

Represents subscription products (IPTV packages).

| Field              | Type           | Description                          | Constraints           |
|--------------------|----------------|--------------------------------------|-----------------------|
| id                 | uuid           | Primary key (UUID)                   | UUID, primary         |
| name               | string         | Plan name (e.g., "Premium Monthly")  | Required, max 255     |
| slug               | string         | URL-friendly identifier              | Unique, required      |
| description        | text           | Plan details                         | Nullable              |
| price              | decimal(10,2)  | Price in cents/dollars               | Required, >= 0        |
| currency           | string(3)      | ISO currency code (USD, EUR)         | Required, default USD |
| billing_interval   | string         | Billing frequency (uses BillingInterval enum) | Required |
| duration_days      | integer        | Service duration in days             | Required, > 0         |
| max_devices        | integer        | Concurrent device limit              | Nullable              |
| features           | json           | Additional features/metadata         | Nullable              |
| is_active          | boolean        | Whether plan is available for sale   | Default true          |
| woocommerce_id     | string         | WooCommerce product ID               | Nullable, unique      |
| my8k_plan_code     | string         | My8K API plan identifier             | Required              |
| created_at         | timestamp      |                                      |                       |
| updated_at         | timestamp      |                                      |                       |

**Relationships:**
- `hasMany` Subscriptions

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'billing_interval' => BillingInterval::class,
        'is_active' => 'boolean',
        'features' => 'array',
    ];
}
```

#### 6.1.2 Subscriptions

Represents a customer's active subscription.

| Field                  | Type           | Description                          | Constraints           |
|------------------------|----------------|--------------------------------------|-----------------------|
| id                     | uuid           | Primary key (UUID)                   | UUID, primary         |
| user_id                | uuid           | Foreign key to users                 | UUID, required, indexed |
| plan_id                | uuid           | Foreign key to plans                 | UUID, required, indexed |
| service_account_id     | uuid           | Foreign key to service_accounts      | UUID, nullable, indexed |
| status                 | string         | Subscription status (uses SubscriptionStatus enum) | Required |
| woocommerce_subscription_id | string    | WooCommerce subscription ID          | Nullable, unique      |
| starts_at              | timestamp      | Subscription start date              | Required              |
| expires_at             | timestamp      | Subscription expiry date             | Required              |
| cancelled_at           | timestamp      | Cancellation timestamp               | Nullable              |
| last_renewal_at        | timestamp      | Last successful renewal              | Nullable              |
| next_renewal_at        | timestamp      | Next expected renewal                | Nullable              |
| auto_renew             | boolean        | Whether to auto-renew                | Default true          |
| metadata               | json           | Additional data from WooCommerce     | Nullable              |
| created_at             | timestamp      |                                      |                       |
| updated_at             | timestamp      |                                      |                       |

**Relationships:**
- `belongsTo` User
- `belongsTo` Plan
- `hasOne` ServiceAccount
- `hasMany` Orders
- `hasMany` ProvisioningLogs

**Indexes:**
- `user_id, status`
- `expires_at, status`
- `woocommerce_subscription_id`

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'user_id' => 'string',
        'plan_id' => 'string',
        'service_account_id' => 'string',
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_renewal_at' => 'datetime',
        'next_renewal_at' => 'datetime',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];
}
```

#### 6.1.3 Orders

Represents individual payment transactions.

| Field                  | Type           | Description                          | Constraints           |
|------------------------|----------------|--------------------------------------|-----------------------|
| id                     | uuid           | Primary key (UUID)                   | UUID, primary         |
| subscription_id        | uuid           | Foreign key to subscriptions         | UUID, required, indexed |
| user_id                | uuid           | Foreign key to users                 | UUID, required, indexed |
| woocommerce_order_id   | string         | WooCommerce order ID                 | Required, unique, indexed |
| status                 | string         | Order status (uses OrderStatus enum) | Required              |
| amount                 | decimal(10,2)  | Order amount                         | Required              |
| currency               | string(3)      | Currency code                        | Required              |
| payment_method         | string         | Payment gateway used                 | Nullable              |
| paid_at                | timestamp      | Payment completion timestamp         | Required              |
| provisioned_at         | timestamp      | Provisioning completion timestamp    | Nullable              |
| idempotency_key        | string         | For deduplication                    | Unique, indexed       |
| webhook_payload        | json           | Raw webhook data                     | Nullable              |
| created_at             | timestamp      |                                      |                       |
| updated_at             | timestamp      |                                      |                       |

**Relationships:**
- `belongsTo` Subscription
- `belongsTo` User
- `hasMany` ProvisioningLogs

**Indexes:**
- `woocommerce_order_id`
- `idempotency_key`
- `status, created_at`

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'subscription_id' => 'string',
        'user_id' => 'string',
        'status' => OrderStatus::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'provisioned_at' => 'datetime',
        'webhook_payload' => 'array',
    ];
}
```

**Note:** This table uses UUID as primary key (not a separate `uuid` field as originally specified).

#### 6.1.4 ServiceAccounts

Represents My8K IPTV account credentials.

| Field                  | Type           | Description                          | Constraints           |
|------------------------|----------------|--------------------------------------|-----------------------|
| id                     | uuid           | Primary key (UUID)                   | UUID, primary         |
| subscription_id        | uuid           | Foreign key to subscriptions         | UUID, unique, required |
| user_id                | uuid           | Foreign key to users                 | UUID, required, indexed |
| my8k_account_id        | string         | My8K account identifier              | Unique, required      |
| username               | string         | IPTV username                        | Required (encrypted)  |
| password               | string         | IPTV password                        | Required (encrypted)  |
| server_url             | string         | M3U/EPG server URL                   | Required              |
| max_connections        | integer        | Concurrent stream limit              | Required              |
| status                 | string         | Account status (uses ServiceAccountStatus enum) | Required |
| activated_at           | timestamp      | First activation timestamp           | Required              |
| expires_at             | timestamp      | Account expiry date                  | Required              |
| last_extended_at       | timestamp      | Last extension timestamp             | Nullable              |
| my8k_metadata          | json           | Additional My8K API response data    | Nullable              |
| created_at             | timestamp      |                                      |                       |
| updated_at             | timestamp      |                                      |                       |

**Relationships:**
- `belongsTo` Subscription
- `belongsTo` User
- `hasMany` ProvisioningLogs

**Indexes:**
- `subscription_id`
- `my8k_account_id`
- `expires_at, status`

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'subscription_id' => 'string',
        'user_id' => 'string',
        'username' => 'encrypted',
        'password' => 'encrypted',
        'status' => ServiceAccountStatus::class,
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_extended_at' => 'datetime',
        'my8k_metadata' => 'array',
    ];
}
```

**Security Note:** `username` and `password` fields are encrypted at rest using Laravel's encrypted casting.

#### 6.1.5 ProvisioningLogs

Audit trail for all provisioning attempts.

| Field                  | Type           | Description                          | Constraints           |
|------------------------|----------------|--------------------------------------|-----------------------|
| id                     | uuid           | Primary key (UUID)                   | UUID, primary         |
| subscription_id        | uuid           | Foreign key to subscriptions         | UUID, nullable, indexed |
| order_id               | uuid           | Foreign key to orders                | UUID, nullable, indexed |
| service_account_id     | uuid           | Foreign key to service_accounts      | UUID, nullable, indexed |
| action                 | string         | Provisioning action (uses ProvisioningAction enum) | Required |
| status                 | string         | Provisioning status (uses ProvisioningStatus enum) | Required |
| attempt_number         | integer        | Retry attempt count                  | Default 1             |
| job_id                 | string         | Laravel queue job ID                 | Nullable              |
| my8k_request           | json           | Request sent to My8K API             | Nullable              |
| my8k_response          | json           | Response from My8K API               | Nullable              |
| error_message          | text           | Error details if failed              | Nullable              |
| error_code             | string         | Categorized error code               | Nullable              |
| duration_ms            | integer        | API call duration in milliseconds    | Nullable              |
| created_at             | timestamp      | When attempt was made                |                       |
| updated_at             | timestamp      |                                      |                       |

**Relationships:**
- `belongsTo` Subscription
- `belongsTo` Order
- `belongsTo` ServiceAccount

**Indexes:**
- `subscription_id, created_at`
- `status, created_at`
- `action, status`

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'subscription_id' => 'string',
        'order_id' => 'string',
        'service_account_id' => 'string',
        'action' => ProvisioningAction::class,
        'status' => ProvisioningStatus::class,
        'my8k_request' => 'array',
        'my8k_response' => 'array',
    ];
}
```

#### 6.1.6 Users

Laravel Fortify users table (modified to use UUID).

| Field                  | Type           | Description                          | Constraints           |
|------------------------|----------------|--------------------------------------|-----------------------|
| id                     | uuid           | Primary key (UUID)                   | UUID, primary         |
| name                   | string         | User's full name                     | Required              |
| email                  | string         | User's email address                 | Required, unique      |
| email_verified_at      | timestamp      | Email verification timestamp         | Nullable              |
| password               | string         | Hashed password                      | Required              |
| remember_token         | string         | Remember me token                    | Nullable              |
| two_factor_secret      | text           | 2FA secret                           | Nullable (encrypted)  |
| two_factor_recovery_codes | text        | 2FA recovery codes                   | Nullable (encrypted)  |
| two_factor_confirmed_at | timestamp     | 2FA confirmation timestamp           | Nullable              |
| created_at             | timestamp      |                                      |                       |
| updated_at             | timestamp      |                                      |                       |

**Relationships:**
- `hasMany` Subscriptions
- `hasMany` Orders
- `hasMany` ServiceAccounts

**Model Casts:**
```php
protected function casts(): array
{
    return [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
    ];
}
```

**Note:** Existing users table must be migrated to UUID. This requires a database migration strategy for existing records.

### 6.2 Entity Relationship Diagram

```
┌─────────────┐
│    Users    │
│   (UUID)    │
└──────┬──────┘
       │
       │ 1:N (UUID FKs)
       │
       ▼
┌─────────────────┐         ┌─────────────┐
│  Subscriptions  │────────▶│    Plans    │
│     (UUID)      │   N:1   │   (UUID)    │
└────────┬────────┘         └─────────────┘
         │
         │ 1:1 (UUID FK)
         │
         ▼
┌─────────────────┐
│ ServiceAccounts │
│     (UUID)      │
└────────┬────────┘
         │
         │ 1:N (UUID FKs)
         │
         ▼
┌─────────────────┐         ┌─────────────────┐
│     Orders      │────────▶│ ProvisioningLogs│
│     (UUID)      │   1:N   │     (UUID)      │
└─────────────────┘         └─────────────────┘
```

**Key Changes:**
- All primary keys are now UUIDs
- All foreign keys are UUIDs
- Relationships remain the same, but reference UUID fields

### 6.3 Idempotency Strategy

**Idempotency Key Generation:**
- For WooCommerce webhooks: `woocommerce:{order_id}:{event_type}`
- Store in `orders.idempotency_key` with unique constraint
- On duplicate key collision, return success (already processed)

**Database Constraints:**
- `orders.woocommerce_order_id` → UNIQUE
- `orders.idempotency_key` → UNIQUE
- `service_accounts.subscription_id` → UNIQUE (1:1 relationship)
- `service_accounts.my8k_account_id` → UNIQUE

### 6.4 PHP-Backed Enums Specification

This application uses **PHP 8.1+ enums** (backed by strings) instead of database `ENUM` columns. All enum values are stored as strings in the database and cast to PHP enum types in the models.

**Location:** All enums are stored in `app/Enums/`

#### 6.4.1 SubscriptionStatus

**File:** `app/Enums/SubscriptionStatus.php`

**Values:**
- `pending` - Payment processing or initial provisioning in progress
- `active` - Subscription is active and service is provisioned
- `suspended` - Temporarily suspended due to payment failure
- `expired` - Subscription has passed expiry date
- `cancelled` - User or admin cancelled the subscription

**Implementation:**
```php
<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'yellow',
            self::Active => 'green',
            self::Suspended => 'orange',
            self::Expired => 'gray',
            self::Cancelled => 'red',
        };
    }
}
```

#### 6.4.2 OrderStatus

**File:** `app/Enums/OrderStatus.php`

**Values:**
- `pending_provisioning` - Order paid, awaiting provisioning
- `provisioned` - Service successfully provisioned
- `provisioning_failed` - Provisioning failed after all retries
- `refunded` - Order was refunded

**Implementation:**
```php
<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingProvisioning = 'pending_provisioning';
    case Provisioned = 'provisioned';
    case ProvisioningFailed = 'provisioning_failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PendingProvisioning => 'Pending Provisioning',
            self::Provisioned => 'Provisioned',
            self::ProvisioningFailed => 'Provisioning Failed',
            self::Refunded => 'Refunded',
        };
    }
}
```

#### 6.4.3 ServiceAccountStatus

**File:** `app/Enums/ServiceAccountStatus.php`

**Values:**
- `active` - Account is active and usable
- `suspended` - Account temporarily suspended
- `expired` - Account has expired

**Implementation:**
```php
<?php

namespace App\Enums;

enum ServiceAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
        };
    }
}
```

#### 6.4.4 ProvisioningAction

**File:** `app/Enums/ProvisioningAction.php`

**Values:**
- `create` - Create new My8K account
- `extend` - Extend existing account expiry
- `suspend` - Suspend account
- `reactivate` - Reactivate suspended account
- `query` - Query account status

**Implementation:**
```php
<?php

namespace App\Enums;

enum ProvisioningAction: string
{
    case Create = 'create';
    case Extend = 'extend';
    case Suspend = 'suspend';
    case Reactivate = 'reactivate';
    case Query = 'query';

    public function label(): string
    {
        return match($this) {
            self::Create => 'Create Account',
            self::Extend => 'Extend Account',
            self::Suspend => 'Suspend Account',
            self::Reactivate => 'Reactivate Account',
            self::Query => 'Query Account',
        };
    }
}
```

#### 6.4.5 ProvisioningStatus

**File:** `app/Enums/ProvisioningStatus.php`

**Values:**
- `pending` - Provisioning queued but not started
- `success` - Provisioning completed successfully
- `failed` - Provisioning failed
- `retrying` - Provisioning failed, will retry

**Implementation:**
```php
<?php

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Retrying = 'retrying';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Retrying => 'Retrying',
        };
    }
}
```

#### 6.4.6 BillingInterval

**File:** `app/Enums/BillingInterval.php`

**Values:**
- `monthly` - Billed every month
- `quarterly` - Billed every 3 months
- `yearly` - Billed every year

**Implementation:**
```php
<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    public function months(): int
    {
        return match($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Yearly => 12,
        };
    }
}
```

### 6.5 Migration Examples

#### 6.5.1 Users Table Migration (UUID Conversion)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

#### 6.5.2 Plans Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_interval'); // Stores enum value as string
            $table->integer('duration_days');
            $table->integer('max_devices')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('woocommerce_id')->nullable()->unique();
            $table->string('my8k_plan_code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
```

#### 6.5.3 Subscriptions Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // Stores enum value as string
            $table->string('woocommerce_subscription_id')->nullable()->unique();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_renewal_at')->nullable();
            $table->timestamp('next_renewal_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
```

#### 6.5.4 Orders Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('woocommerce_order_id')->unique();
            $table->string('status'); // Stores enum value as string
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at');
            $table->timestamp('provisioned_at')->nullable();
            $table->string('idempotency_key')->unique();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

#### 6.5.5 Service Accounts Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('my8k_account_id')->unique();
            $table->string('username'); // Encrypted via model cast
            $table->string('password'); // Encrypted via model cast
            $table->string('server_url');
            $table->integer('max_connections');
            $table->string('status'); // Stores enum value as string
            $table->timestamp('activated_at');
            $table->timestamp('expires_at');
            $table->timestamp('last_extended_at')->nullable();
            $table->json('my8k_metadata')->nullable();
            $table->timestamps();

            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_accounts');
    }
};
```

#### 6.5.6 Provisioning Logs Table Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisioning_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_account_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action'); // Stores enum value as string
            $table->string('status'); // Stores enum value as string
            $table->integer('attempt_number')->default(1);
            $table->string('job_id')->nullable();
            $table->json('my8k_request')->nullable();
            $table->json('my8k_response')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['action', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_logs');
    }
};
```

---

## 7. Feature Requirements

### 7.1 Customer-Facing Features

#### 7.1.1 Subscription Checkout

**User Story:** As a customer, I want to purchase an IPTV subscription plan so that I can watch content.

**Requirements:**
- Display available plans with pricing and features
- Integrate payment processing (Stripe, PayPal, or WooCommerce)
- Collect customer information (email, name, phone if required)
- Create user account if not logged in
- Support guest checkout OR require registration (decision needed)
- Apply promo codes / discounts (if applicable)
- Terms of service acceptance
- Redirect to thank-you page after successful payment

**Acceptance Criteria:**
- Customer can complete checkout in <3 clicks
- Payment processing is secure (PCI compliance)
- Order confirmation email sent immediately
- Account credentials emailed within 5 minutes of payment

#### 7.1.2 Subscription Management Dashboard

**User Story:** As a customer, I want to view and manage my subscriptions in one place.

**Requirements:**
- View all active/expired subscriptions
- Display current plan details (expiry date, features, credentials)
- Download M3U playlist / EPG URL
- View payment history and invoices
- Cancel auto-renewal (if enabled)
- Upgrade/downgrade plan (Phase 2)
- Contact support link

**Acceptance Criteria:**
- Dashboard loads in <2 seconds
- Credentials are redacted but copyable
- Expiry date prominently displayed
- Clear visual status indicators (active, expiring soon, expired)

#### 7.1.3 Account Credentials Display

**User Story:** As a customer, I want to easily access my IPTV credentials after provisioning.

**Requirements:**
- Show username, password (with reveal/hide toggle)
- Display server URL and connection details
- Provide setup instructions / links to apps
- Copy-to-clipboard buttons for each field
- QR code for mobile setup (Phase 2)

**Acceptance Criteria:**
- Credentials only visible to authenticated subscription owner
- Password hidden by default, revealable with click
- Copy buttons work on all major browsers

### 7.2 Admin Features

#### 7.2.1 Subscription Management

**User Story:** As an admin, I want to view and search all subscriptions for support and monitoring.

**Requirements:**
- List all subscriptions with filters:
  - Status (active, pending, suspended, expired, cancelled)
  - User (search by email/name)
  - Plan
  - Date range
- Display key metrics per subscription:
  - Current status
  - Provisioning status
  - Expiry date
  - Last renewal date
- View subscription details:
  - Full user information
  - Associated orders
  - Service account credentials
  - Provisioning history
- Actions:
  - View customer details
  - View provisioning logs
  - Manually trigger provisioning
  - Suspend/reactivate account
  - Extend expiry date
  - Cancel subscription

**UI Components:**
- DataTable with sorting, filtering, pagination
- Status badges (color-coded)
- Quick action buttons
- Drill-down modal for details

**Acceptance Criteria:**
- Supports 10,000+ subscriptions without performance degradation
- Search returns results in <1 second
- Filters are combinable (AND logic)

#### 7.2.2 Manual Provisioning Controls

**User Story:** As an admin, I need to manually trigger provisioning when automatic provisioning fails.

**Requirements:**
- Button to "Retry Provisioning" on failed orders
- Ability to force re-provision (even if previously succeeded)
- Confirm action with warning modal
- Real-time status updates (using Livewire polling or WebSockets)
- Show provisioning progress/result
- Log admin action in audit trail

**Acceptance Criteria:**
- Manual provisioning uses same job queue as automatic
- Admin sees success/failure notification
- Action recorded with admin user ID in logs

#### 7.2.3 Failed Jobs Management

**User Story:** As an admin, I want to see all failed provisioning jobs and retry them.

**Requirements:**
- List all failed jobs from `failed_jobs` table
- Show:
  - Job type
  - Failure timestamp
  - Exception message
  - Payload (order/subscription details)
  - Number of retry attempts made
- Actions:
  - Retry individual job
  - Bulk retry multiple jobs
  - Delete job (with confirmation)
- Filter by date range and job type

**Acceptance Criteria:**
- Failed jobs display with stack trace for debugging
- Retry button dispatches job back to queue
- Success/failure feedback after retry attempt

#### 7.2.4 Analytics Dashboard

**User Story:** As an admin, I want to monitor provisioning health and trends.

**Requirements:**
- **Key Metrics (Last 24h, 7d, 30d):**
  - Total orders received
  - Successful provisioning count & rate
  - Failed provisioning count & rate
  - Average provisioning time
  - Retry attempts (average)
  - Active subscriptions
  - Expiring soon (within 7 days)

- **Charts:**
  - Provisioning success rate over time (line chart)
  - Orders by status (pie chart)
  - Provisioning time histogram
  - Failures by error type (bar chart)

- **Alerts:**
  - Failed provisioning count exceeds threshold
  - My8K API downtime detected
  - Queue backlog exceeds limit

**Acceptance Criteria:**
- Dashboard refreshes every 60 seconds (or manual refresh)
- Charts are interactive and filterable by date range
- Alerts displayed prominently at top of dashboard
- Exports to CSV/PDF for reporting

### 7.3 API Endpoints

#### 7.3.1 Webhook Endpoints

**POST /webhooks/woocommerce/order-completed**
- Receives WooCommerce `order.completed` event
- Validates webhook signature
- Creates Order record
- Dispatches provisioning job
- Returns 200 OK immediately

**POST /webhooks/woocommerce/subscription-renewed**
- Handles recurring payment success
- Extends existing subscription and service account
- Updates `expires_at` and `last_renewal_at`

**POST /webhooks/woocommerce/subscription-cancelled**
- Marks subscription as cancelled
- Optionally suspends My8K account (or let it expire naturally)

**POST /webhooks/woocommerce/subscription-payment-failed**
- Updates subscription status to `suspended`
- Sends notification to customer
- May trigger retry payment flow (if configured)

**Security:**
- All webhook endpoints require signature validation
- Rate limiting: 100 requests/minute per IP
- Log all webhook attempts (success and failure)

#### 7.3.2 Internal API (for Admin UI)

**GET /api/admin/subscriptions**
- Paginated list of subscriptions with filters
- Auth: Admin role required

**POST /api/admin/subscriptions/{id}/provision**
- Manually trigger provisioning
- Auth: Admin role required

**GET /api/admin/failed-jobs**
- List failed jobs
- Auth: Admin role required

**POST /api/admin/failed-jobs/{id}/retry**
- Retry specific failed job
- Auth: Admin role required

**GET /api/admin/analytics**
- Return analytics data for dashboard
- Auth: Admin role required

---

## 8. Integration Specifications

### 8.1 WooCommerce Integration

#### 8.1.1 Webhook Configuration

**WooCommerce Webhooks to Create:**
1. **Order Completed** → `POST /webhooks/woocommerce/order-completed`
2. **Subscription Renewed** → `POST /webhooks/woocommerce/subscription-renewed`
3. **Subscription Cancelled** → `POST /webhooks/woocommerce/subscription-cancelled`
4. **Subscription Payment Failed** → `POST /webhooks/woocommerce/subscription-payment-failed`

**Webhook Secret:**
- Store in `.env` as `WOOCOMMERCE_WEBHOOK_SECRET`
- Used for HMAC signature validation

**Signature Validation:**
```php
$signature = hash_hmac('sha256', $payload, config('services.woocommerce.webhook_secret'));
if (!hash_equals($signature, $request->header('X-WC-Webhook-Signature'))) {
    abort(401, 'Invalid signature');
}
```

#### 8.1.2 Payload Structure

**Order Completed Event:**
```json
{
  "id": 12345,
  "order_key": "wc_order_xxx",
  "status": "completed",
  "currency": "USD",
  "total": "29.99",
  "customer_id": 789,
  "billing": {
    "email": "customer@example.com",
    "first_name": "John",
    "last_name": "Doe"
  },
  "line_items": [
    {
      "id": 1,
      "product_id": 101,
      "name": "Premium Monthly IPTV",
      "quantity": 1,
      "total": "29.99"
    }
  ],
  "date_paid": "2026-01-10T12:34:56",
  "payment_method": "stripe"
}
```

**Subscription Renewed Event:**
```json
{
  "id": 67890,
  "subscription_id": 456,
  "parent_order_id": 12345,
  "status": "active",
  "billing_period": "month",
  "billing_interval": 1,
  "start_date": "2026-01-10T00:00:00",
  "next_payment_date": "2026-02-10T00:00:00",
  "end_date": null,
  "customer_id": 789,
  "line_items": [...]
}
```

#### 8.1.3 Product Mapping

**Requirement:** Map WooCommerce product IDs to Laravel `Plan` records.

**Approach:**
- Store `woocommerce_product_id` in `plans` table
- On webhook receipt, lookup Plan by product ID
- Validate that product is still active and mappable

**Configuration:**
```php
// config/services.php
'woocommerce' => [
    'url' => env('WOOCOMMERCE_API_URL'),
    'consumer_key' => env('WOOCOMMERCE_CONSUMER_KEY'),
    'consumer_secret' => env('WOOCOMMERCE_CONSUMER_SECRET'),
    'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),
],
```

#### 8.1.4 WooCommerce REST API Client Setup

This section describes the **outbound** integration where Laravel calls WooCommerce APIs to fetch data or update records.

**Purpose:** Enable Laravel to:
- Query order details not included in webhooks
- Add order notes after provisioning
- Update subscription metadata (store My8K account ID)
- Perform daily reconciliation to catch missed webhooks

**Composer Package:**
```bash
composer require automattic/woocommerce
```

**Service Class Implementation:**

**File:** `app/Services/WooCommerceApiClient.php`

```php
<?php

namespace App\Services;

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class WooCommerceApiClient
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.woocommerce.url'),
            config('services.woocommerce.consumer_key'),
            config('services.woocommerce.consumer_secret'),
            [
                'version' => 'wc/v3',
                'timeout' => 30,
                'verify_ssl' => true,
            ]
        );
    }

    /**
     * Get a single order by ID
     */
    public function getOrder(int $orderId): array
    {
        try {
            return $this->client->get("orders/{$orderId}");
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to fetch order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a single subscription by ID
     */
    public function getSubscription(int $subscriptionId): array
    {
        try {
            return $this->client->get("subscriptions/{$subscriptionId}");
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to fetch subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a note to an order
     */
    public function addOrderNote(int $orderId, string $note, bool $customerNote = false): array
    {
        try {
            return $this->client->post("orders/{$orderId}/notes", [
                'note' => $note,
                'customer_note' => $customerNote,
            ]);
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to add order note', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update subscription metadata
     */
    public function updateSubscriptionMeta(int $subscriptionId, array $metadata): array
    {
        try {
            return $this->client->put("subscriptions/{$subscriptionId}", [
                'meta_data' => $metadata,
            ]);
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to update subscription metadata', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get orders with optional filters
     */
    public function getOrders(array $params = []): array
    {
        try {
            return $this->client->get('orders', $params);
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to fetch orders', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get subscriptions with optional filters
     */
    public function getSubscriptions(array $params = []): array
    {
        try {
            return $this->client->get('subscriptions', $params);
        } catch (HttpClientException $e) {
            \Log::error('WooCommerce API: Failed to fetch subscriptions', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

**Usage Example in Provisioning Job:**

```php
// app/Jobs/ProvisionAccountJob.php
public function handle(My8kApiClient $my8kClient, WooCommerceApiClient $wooCommerceClient): void
{
    // 1. Provision My8K account
    $accountData = $my8kClient->createAccount(/* ... */);

    // 2. Store service account in Laravel
    $serviceAccount = ServiceAccount::create(/* ... */);

    // 3. Add note to WooCommerce order
    $wooCommerceClient->addOrderNote(
        $this->order->woocommerce_order_id,
        "My8K account provisioned successfully. Account ID: {$accountData['account_id']}"
    );

    // 4. Store My8K account ID in WooCommerce subscription metadata
    if ($this->order->subscription->woocommerce_subscription_id) {
        $wooCommerceClient->updateSubscriptionMeta(
            $this->order->subscription->woocommerce_subscription_id,
            [
                ['key' => '_my8k_account_id', 'value' => $accountData['account_id']],
                ['key' => '_provisioned_at', 'value' => now()->toIso8601String()],
            ]
        );
    }
}
```

#### 8.1.5 Bidirectional Integration Patterns

This section defines when to use **webhooks (inbound)** vs **REST API (outbound)**.

**Integration Pattern Matrix:**

| Scenario | Method | Direction | Why |
|----------|--------|-----------|-----|
| WooCommerce notifies Laravel of payment | Webhook | WC → Laravel | Real-time, event-driven, push-based |
| Laravel needs full order details | REST API | Laravel → WC | Pull additional data as needed |
| Laravel adds provisioning notes to WC order | REST API | Laravel → WC | Update WooCommerce with status |
| Laravel stores My8K account ID in WC | REST API | Laravel → WC | Sync data back to WooCommerce |
| Daily sync to catch missed webhooks | REST API | Laravel → WC | Query orders from last 24 hours |
| Laravel queries customer information | REST API | Laravel → WC | Pull customer data on demand |

**Architecture Diagram:**

```
┌─────────────────────────────────────┐
│        WooCommerce Store            │
│   - Products & Checkout             │
│   - Payment Processing              │
│   - Subscription Management         │
└────────────┬────────────────────────┘
             │
             │ (1) INBOUND: Webhooks (Push)
             │     Events: order.completed,
             │             subscription.renewed, etc.
             │
             ▼
┌─────────────────────────────────────┐
│    Laravel Provisioning Service     │
│  ┌──────────────────────────────┐  │
│  │  Webhook Receivers           │  │
│  │  - Validate signature        │  │
│  │  - Check idempotency         │  │
│  │  - Dispatch queue jobs       │  │
│  └──────────────────────────────┘  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │  Provisioning Engine         │  │
│  │  - Queue jobs                │  │
│  │  - My8K API calls            │  │
│  │  - Update local database     │  │
│  └──────────────────────────────┘  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │  WooCommerce API Client      │  │
│  │  - Add order notes           │  │
│  │  - Update subscription meta  │  │
│  │  - Query missing data        │  │
│  └──────────────────────────────┘  │
└────────────┬────────────────────────┘
             │
             │ (2) OUTBOUND: REST API (Pull/Push)
             │     Actions: GET orders, POST notes,
             │              PUT subscriptions
             │
             ▼
┌─────────────────────────────────────┐
│     WooCommerce REST API            │
│   - GET /orders/{id}                │
│   - GET /subscriptions/{id}         │
│   - POST /orders/{id}/notes         │
│   - PUT /subscriptions/{id}         │
└─────────────────────────────────────┘
```

**Data Flow Examples:**

**1. Successful Order Provisioning (Bidirectional):**
```
1. Customer pays on WooCommerce
   ↓
2. WooCommerce sends webhook → Laravel receives (INBOUND)
   ↓
3. Laravel validates, creates Order record, dispatches job
   ↓
4. Queue worker provisions My8K account
   ↓
5. Laravel calls WooCommerce API to add order note (OUTBOUND)
   ↓
6. Laravel calls WooCommerce API to store My8K account ID (OUTBOUND)
   ↓
7. Customer receives credentials email from Laravel
```

**2. Daily Reconciliation (Outbound Only):**
```
1. Laravel scheduled job runs at 2 AM
   ↓
2. Laravel calls WooCommerce API: GET /orders?after=yesterday (OUTBOUND)
   ↓
3. Laravel compares WooCommerce orders with local Order records
   ↓
4. If order exists in WC but not in Laravel → Log warning, create order
   ↓
5. If order status differs → Investigate and reconcile
```

#### 8.1.6 Data Synchronization Strategy

**Challenge:** Webhooks may fail, be delayed, or arrive out of order. The system must handle these edge cases gracefully.

**Synchronization Approaches:**

**1. Webhook-First Strategy (Primary)**
- Rely on WooCommerce webhooks for real-time event notifications
- Webhooks are the primary source of truth for order/subscription events
- 99% of events should arrive via webhooks under normal conditions

**2. Daily Reconciliation Job (Backup)**
- Scheduled command runs daily to catch missed webhooks
- Queries WooCommerce for orders/subscriptions modified in last 24-48 hours
- Compares against local database
- Creates missing records or updates stale data

**Implementation:**

**File:** `app/Console/Commands/ReconcileWooCommerceOrdersCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\WooCommerceApiClient;
use App\Models\Order;
use Illuminate\Console\Command;

class ReconcileWooCommerceOrdersCommand extends Command
{
    protected $signature = 'woocommerce:reconcile-orders {--hours=24}';
    protected $description = 'Reconcile WooCommerce orders with local database';

    public function handle(WooCommerceApiClient $client): int
    {
        $hours = $this->option('hours');
        $after = now()->subHours($hours)->toIso8601String();

        $this->info("Fetching WooCommerce orders modified after {$after}");

        // Fetch orders from WooCommerce
        $wcOrders = $client->getOrders([
            'modified_after' => $after,
            'status' => 'completed',
            'per_page' => 100,
        ]);

        $missingCount = 0;
        $updatedCount = 0;

        foreach ($wcOrders as $wcOrder) {
            $localOrder = Order::where('woocommerce_order_id', $wcOrder['id'])->first();

            if (!$localOrder) {
                // Order exists in WooCommerce but not in Laravel
                $this->warn("Missing order: WC Order ID {$wcOrder['id']}");
                $missingCount++;

                // TODO: Create order and dispatch provisioning job
                // This requires parsing the webhook payload format
            } elseif ($this->needsUpdate($localOrder, $wcOrder)) {
                // Order exists but data differs
                $this->info("Updating order: {$localOrder->id}");
                $this->updateOrder($localOrder, $wcOrder);
                $updatedCount++;
            }
        }

        $this->info("Reconciliation complete: {$missingCount} missing, {$updatedCount} updated");

        return self::SUCCESS;
    }

    protected function needsUpdate(Order $local, array $wc): bool
    {
        // Compare relevant fields
        return $local->amount != $wc['total']
            || $local->status->value != $this->mapWcStatusToLocal($wc['status']);
    }

    protected function updateOrder(Order $order, array $wcData): void
    {
        // Update local order with WooCommerce data
        $order->update([
            'amount' => $wcData['total'],
            // Add other fields as needed
        ]);
    }

    protected function mapWcStatusToLocal(string $wcStatus): string
    {
        // Map WooCommerce status to local OrderStatus enum
        return match($wcStatus) {
            'completed' => 'provisioned',
            'processing' => 'pending_provisioning',
            'refunded' => 'refunded',
            default => 'pending_provisioning',
        };
    }
}
```

**Schedule Registration:**

```php
// app/Console/Kernel.php or routes/console.php
Schedule::command('woocommerce:reconcile-orders')->daily()->at('02:00');
```

**3. Idempotency Handling (Critical)**
- All webhook handlers must check `idempotency_key` before processing
- Prevents duplicate provisioning if webhook is received multiple times
- Database unique constraint enforces this at the schema level

**4. Out-of-Order Event Handling**
- Renewal webhook may arrive before initial order webhook (rare but possible)
- Solution: Queue job retries if parent subscription doesn't exist
- After 3-5 retries, create alert for manual investigation

**5. Manual Admin Sync (On-Demand)**
- Admin UI provides "Sync from WooCommerce" button on order detail page
- Fetches latest data from WooCommerce and updates local record
- Useful for debugging discrepancies

**Monitoring & Alerts:**
- Alert if reconciliation job finds >10 missing orders
- Alert if webhook failures exceed 5% in last hour
- Daily report of sync status sent to admin team

### 8.2 My8K IPTV API Integration

#### 8.2.1 API Endpoints

**Base URL:** `https://api.my8k.tv/v1` (example, verify actual)

**Authentication:**
- Method: API Key in header
- Header: `Authorization: Bearer {API_KEY}`
- Store in `.env` as `MY8K_API_KEY`

**Endpoints:**

1. **Create Account**
   - `POST /accounts/create`
   - Request:
     ```json
     {
       "plan_code": "premium_monthly",
       "duration_days": 30,
       "email": "customer@example.com",
       "max_connections": 2
     }
     ```
   - Response:
     ```json
     {
       "status": "success",
       "data": {
         "account_id": "acc_abc123",
         "username": "user12345",
         "password": "securepass",
         "server_url": "http://example.m3u8",
         "expires_at": "2026-02-10T23:59:59Z",
         "max_connections": 2
       }
     }
     ```

2. **Extend Account**
   - `POST /accounts/{account_id}/extend`
   - Request:
     ```json
     {
       "duration_days": 30
     }
     ```
   - Response:
     ```json
     {
       "status": "success",
       "data": {
         "expires_at": "2026-03-12T23:59:59Z"
       }
     }
     ```

3. **Query Account**
   - `GET /accounts/{account_id}`
   - Response:
     ```json
     {
       "status": "success",
       "data": {
         "account_id": "acc_abc123",
         "status": "active",
         "expires_at": "2026-02-10T23:59:59Z",
         "created_at": "2026-01-10T12:00:00Z"
       }
     }
     ```

4. **Suspend Account** (if supported)
   - `POST /accounts/{account_id}/suspend`

5. **Reactivate Account** (if supported)
   - `POST /accounts/{account_id}/reactivate`

#### 8.2.2 Error Handling

**Expected Error Codes:**
- `400` - Invalid request (missing/invalid parameters)
- `401` - Authentication failed
- `402` - Insufficient credits/quota
- `404` - Account not found
- `409` - Account already exists (for create)
- `429` - Rate limit exceeded
- `500` - My8K server error
- `503` - Service temporarily unavailable

**Retry Strategy:**
- **Retryable errors:** 429, 500, 503, network timeouts
- **Non-retryable errors:** 400, 401, 402, 404, 409
- **Backoff:** Exponential (10s, 30s, 90s, 270s, 810s)
- **Max attempts:** 5

**Credit Exhaustion (402):**
- Do NOT retry automatically
- Create alert for admin
- Store order in `provisioning_failed` state with error message
- Admin must resolve (add credits, then manually retry)

#### 8.2.3 API Client Implementation

**Service Class:**
```php
// app/Services/My8kApiClient.php
class My8kApiClient
{
    public function createAccount(string $planCode, int $durationDays, string $email, int $maxConnections): array;
    public function extendAccount(string $accountId, int $durationDays): array;
    public function queryAccount(string $accountId): array;
    public function suspendAccount(string $accountId): array;
    public function reactivateAccount(string $accountId): array;
}
```

**Logging:**
- Log all API requests and responses to `ProvisioningLog`
- Redact sensitive data in logs (passwords, API keys)
- Include request duration for performance monitoring

**Timeout Configuration:**
- Connection timeout: 10 seconds
- Request timeout: 30 seconds

**Configuration:**
```php
// config/services.php
'my8k' => [
    'api_url' => env('MY8K_API_URL', 'https://api.my8k.tv/v1'),
    'api_key' => env('MY8K_API_KEY'),
    'timeout' => env('MY8K_API_TIMEOUT', 30),
    'retry_attempts' => env('MY8K_RETRY_ATTEMPTS', 5),
],
```

---

## 9. Subscription Lifecycle

### 9.1 State Diagram

```
                        ┌──────────────┐
                        │   pending    │ (Payment processing)
                        └──────┬───────┘
                               │
                         Payment Success
                               │
                               ▼
                        ┌──────────────┐
                        │    active    │ (Provisioned, service active)
                        └──────┬───────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
          Renewal Success   Expires      Payment Fails
                │              │              │
                ▼              ▼              ▼
         ┌──────────┐   ┌──────────┐   ┌──────────┐
         │  active  │   │ expired  │   │suspended │
         └──────────┘   └──────────┘   └─────┬────┘
                                              │
                                     ┌────────┴────────┐
                                     │                 │
                              Payment Retried     User Cancels
                                     │                 │
                                     ▼                 ▼
                              ┌──────────┐      ┌───────────┐
                              │  active  │      │ cancelled │
                              └──────────┘      └───────────┘
```

### 9.2 Lifecycle Events

#### 9.2.1 Initial Purchase

**Trigger:** WooCommerce `order.completed` webhook

**Actions:**
1. Validate webhook signature
2. Check idempotency (prevent duplicate processing)
3. Create `Subscription` record (status: `pending`)
4. Create `Order` record (status: `pending_provisioning`)
5. Dispatch `ProvisionAccountJob` to queue
6. Send order confirmation email

**Queue Job: ProvisionAccountJob**
1. Load `Order` and related `Subscription`
2. Call `My8kApiClient::createAccount()`
3. On success:
   - Create `ServiceAccount` record with credentials
   - Update `Subscription.status` → `active`
   - Update `Order.status` → `provisioned`
   - Set `Subscription.starts_at` and `expires_at`
   - Create `ProvisioningLog` (status: success)
   - Send "Credentials Ready" email to customer
4. On failure:
   - Create `ProvisioningLog` (status: failed)
   - If retryable error: release job back to queue
   - If non-retryable: fail job, update `Order.status` → `provisioning_failed`

#### 9.2.2 Renewal

**Trigger:** WooCommerce `subscription.renewed` webhook

**Actions:**
1. Validate webhook
2. Lookup `Subscription` by `woocommerce_subscription_id`
3. Create new `Order` record for renewal
4. Dispatch `ExtendAccountJob` to queue

**Queue Job: ExtendAccountJob**
1. Load `Subscription` and `ServiceAccount`
2. Call `My8kApiClient::extendAccount()`
3. On success:
   - Update `ServiceAccount.expires_at` (add duration_days)
   - Update `Subscription.expires_at`
   - Update `Subscription.last_renewal_at`
   - Update `Order.status` → `provisioned`
   - Create `ProvisioningLog` (status: success)
   - Send renewal confirmation email
4. On failure: (same retry logic as create)

#### 9.2.3 Payment Failure

**Trigger:** WooCommerce `subscription.payment_failed` webhook

**Actions:**
1. Validate webhook
2. Lookup `Subscription`
3. Update `Subscription.status` → `suspended`
4. Optionally call `My8kApiClient::suspendAccount()` (if My8K supports)
5. Send payment failure email to customer

**Grace Period:**
- Allow 7-day grace period before suspending service
- Queue job to suspend account if no payment received after 7 days

#### 9.2.4 Cancellation

**Trigger:** WooCommerce `subscription.cancelled` webhook OR customer action in dashboard

**Actions:**
1. Update `Subscription.status` → `cancelled`
2. Set `Subscription.cancelled_at` timestamp
3. Do NOT immediately suspend My8K account (let it expire naturally)
4. Disable auto-renewal
5. Send cancellation confirmation email

**My8K Account Behavior:**
- Account remains active until `expires_at` date
- No further renewals processed
- On expiry, My8K will automatically deactivate (no API call needed)

#### 9.2.5 Expiration

**Trigger:** Scheduled job `ExpireSubscriptionsCommand` (runs daily)

**Actions:**
1. Query all subscriptions where `expires_at` < now AND status != `expired`
2. For each:
   - Update `Subscription.status` → `expired`
   - Update `ServiceAccount.status` → `expired`
   - Send expiry notification email (if auto_renew was disabled)
3. Log expiration in `ProvisioningLog`

**Note:** Do not call My8K API to suspend—accounts expire naturally.

### 9.3 Edge Cases

#### 9.3.1 Duplicate Webhook Events

**Problem:** WooCommerce may send duplicate webhooks due to retries.

**Solution:**
- Use `idempotency_key` (e.g., `woocommerce:{order_id}:{event_type}`)
- Check for existing `Order` with same key before processing
- If found, return 200 OK without processing (already handled)

#### 9.3.2 Out-of-Order Events

**Problem:** Renewal webhook arrives before initial order webhook.

**Solution:**
- For renewal events, check if parent subscription exists
- If not found, log warning and retry after delay (job retry)
- Eventually fail if parent never appears (data inconsistency alert)

#### 9.3.3 My8K Account Already Exists

**Problem:** Attempting to create account that already exists (e.g., retry after partial failure).

**Solution:**
- My8K returns 409 Conflict
- Query account status via `GET /accounts/{id}`
- If account exists and active, consider provisioning successful
- Update local records accordingly (idempotent operation)

#### 9.3.4 My8K Credits Exhausted

**Problem:** My8K returns 402 Payment Required (insufficient credits).

**Solution:**
- Mark job as failed (non-retryable)
- Create high-priority alert for admin
- Store order in `provisioning_failed` state
- Admin must:
  1. Add credits to My8K account
  2. Manually retry provisioning via admin UI

---

## 10. Queue & Reliability

### 10.1 Queue Configuration

**Queue Driver:** Database (with option to upgrade to Redis)

**Queue Names:**
- `default` - General jobs
- `provisioning` - Provisioning jobs (high priority)
- `notifications` - Email notifications (low priority)

**Queue Workers:**
- Run via `php artisan queue:work --queue=provisioning,default,notifications`
- Supervisor configuration for auto-restart
- Multiple workers for horizontal scaling

**Worker Configuration:**
```bash
# .env
QUEUE_CONNECTION=database
QUEUE_PROVISIONING_RETRY_AFTER=90  # seconds
QUEUE_PROVISIONING_MAX_TRIES=5
QUEUE_PROVISIONING_BACKOFF=10,30,90,270,810  # exponential backoff in seconds
```

### 10.2 Job Classes

#### 10.2.1 ProvisionAccountJob

**Responsibility:** Create new My8K account for initial order.

**Properties:**
- `public Order $order`
- `public int $tries = 5`
- `public array $backoff = [10, 30, 90, 270, 810]`
- `public string $queue = 'provisioning'`

**Methods:**
```php
public function handle(My8kApiClient $client): void
{
    // 1. Load related models
    // 2. Call $client->createAccount()
    // 3. Store ServiceAccount
    // 4. Update Order and Subscription status
    // 5. Log to ProvisioningLog
    // 6. Dispatch notification job
}

public function failed(\Throwable $exception): void
{
    // 1. Update Order.status = 'provisioning_failed'
    // 2. Log to ProvisioningLog
    // 3. Create admin alert
    // 4. Send failure notification email
}
```

#### 10.2.2 ExtendAccountJob

**Responsibility:** Extend existing My8K account for renewals.

**Properties:** (same as ProvisionAccountJob)

**Methods:**
```php
public function handle(My8kApiClient $client): void
{
    // 1. Load ServiceAccount
    // 2. Call $client->extendAccount()
    // 3. Update ServiceAccount.expires_at
    // 4. Update Subscription
    // 5. Log to ProvisioningLog
    // 6. Dispatch notification job
}

public function failed(\Throwable $exception): void
{
    // Similar to ProvisionAccountJob
}
```

#### 10.2.3 SendProvisioningNotificationJob

**Responsibility:** Send email notifications (decoupled from provisioning logic).

**Properties:**
- `public string $queue = 'notifications'`
- `public int $tries = 3`

**Types:**
- `CredentialsReadyNotification` - Send credentials after successful provisioning
- `RenewalSuccessNotification` - Confirm renewal
- `ProvisioningFailedNotification` - Notify customer of provisioning failure

### 10.3 Retry & Error Handling

#### 10.3.1 Retry Logic

**Exponential Backoff:**
- Attempt 1: Immediate
- Attempt 2: After 10 seconds
- Attempt 3: After 30 seconds
- Attempt 4: After 90 seconds (1.5 minutes)
- Attempt 5: After 270 seconds (4.5 minutes)
- Attempt 6: After 810 seconds (13.5 minutes) [if configured]

**When to Retry:**
- Network timeouts
- My8K API returns 429 (rate limit), 500, 503
- Connection errors

**When NOT to Retry:**
- 400 (bad request) - data validation error
- 401 (unauthorized) - API key issue (alert admin)
- 402 (insufficient credits) - alert admin, do not retry
- 404 (not found) - account lookup failure
- 409 (conflict) - account already exists (treat as success)

#### 10.3.2 Error Classification

**Error Codes (stored in `provisioning_logs.error_code`):**
- `NETWORK_TIMEOUT` - Connection/request timeout
- `API_AUTH_FAILED` - 401 authentication error
- `API_RATE_LIMIT` - 429 rate limit
- `API_SERVER_ERROR` - 500/503 server error
- `API_BAD_REQUEST` - 400 validation error
- `API_INSUFFICIENT_CREDITS` - 402 quota exceeded
- `API_CONFLICT` - 409 duplicate account
- `UNKNOWN_ERROR` - Unexpected exception

**Admin Dashboard Filtering:**
- Group failed jobs by error_code
- Show trends (e.g., "5 API_SERVER_ERROR in last hour")

### 10.4 Failed Jobs Management

**Storage:** `failed_jobs` table (Laravel default)

**Fields:**
- `uuid` - Unique identifier
- `connection` - Queue connection name
- `queue` - Queue name
- `payload` - Serialized job data
- `exception` - Full stack trace
- `failed_at` - Timestamp

**Admin Actions:**
- **Retry:** `php artisan queue:retry {uuid}` or via UI
- **Retry All:** `php artisan queue:retry all`
- **Delete:** `php artisan queue:forget {uuid}`
- **Flush:** `php artisan queue:flush` (clear all failed jobs)

**Automated Cleanup:**
- Schedule command to delete failed jobs older than 90 days
- Archive critical failures to separate table before deletion

### 10.5 Monitoring & Alerts

**Key Metrics to Monitor:**
- Queue depth (jobs waiting in queue)
- Job processing time (average, p95, p99)
- Failure rate (last hour, last 24h)
- Retry rate
- Worker health (dead workers, memory usage)

**Alerting Thresholds:**
- Queue depth > 100 for 5+ minutes → Scale workers
- Failure rate > 10% in last hour → Investigate My8K API status
- No jobs processed in 10 minutes → Worker may be down
- Failed jobs count > 50 → Manual intervention needed

**Integration:**
- Laravel Horizon (if using Redis queues)
- Custom Livewire dashboard
- Slack notifications for critical alerts
- Email alerts to admin team

---

## 11. Admin Interface

### 11.1 Navigation Structure

```
Admin Dashboard
├── Overview (Analytics)
├── Subscriptions
│   ├── All Subscriptions
│   ├── Active
│   ├── Expiring Soon
│   ├── Suspended
│   └── Cancelled
├── Orders
│   ├── All Orders
│   ├── Pending Provisioning
│   ├── Provisioned
│   └── Failed
├── Failed Jobs
│   ├── View All
│   └── Retry Management
├── Provisioning Logs
│   └── Audit Trail
├── Plans
│   ├── Manage Plans
│   └── Create New Plan
└── Settings
    ├── WooCommerce Config
    ├── My8K API Config
    └── Email Templates
```

### 11.2 Page-Level Requirements

#### 11.2.1 Overview (Analytics Dashboard)

**URL:** `/admin/dashboard`

**Components:**
- **Stat Cards:**
  - Total Active Subscriptions
  - Orders Today
  - Provisioning Success Rate (24h)
  - Failed Jobs Count

- **Charts:**
  - Provisioning Success Rate (last 7 days, line chart)
  - Orders by Status (pie chart)
  - Provisioning Time Distribution (histogram)
  - Top Error Types (bar chart)

- **Recent Activity Feed:**
  - Last 10 provisioning attempts (success/failure)
  - Last 10 failed jobs
  - Link to detailed logs

**Refresh:** Auto-refresh every 60 seconds (Livewire polling)

#### 11.2.2 Subscriptions List

**URL:** `/admin/subscriptions`

**Features:**
- **Filters:**
  - Status dropdown (all, active, suspended, expired, cancelled)
  - User search (email/name)
  - Plan filter
  - Date range picker (created_at)

- **DataTable Columns:**
  - ID
  - User (email)
  - Plan Name
  - Status (badge)
  - Starts At
  - Expires At
  - Actions (View, Edit, Provision, Suspend)

- **Bulk Actions:**
  - Export to CSV
  - Bulk extend expiry date (for migrations/promotions)

- **Row Actions:**
  - View Details (modal)
  - Manual Provision
  - Suspend/Reactivate
  - Cancel

**Performance:**
- Paginate 50 per page
- Eager load relationships (user, plan, service_account)
- Index on status, expires_at, user_id

#### 11.2.3 Subscription Detail Modal

**Trigger:** Click "View" on subscriptions list

**Sections:**
1. **User Information:**
   - Name, email, user ID
   - Registration date
   - Total subscriptions count

2. **Subscription Details:**
   - Plan name and price
   - Status (with color indicator)
   - Dates (starts_at, expires_at, cancelled_at, last_renewal_at)
   - Auto-renew status
   - WooCommerce subscription ID

3. **Service Account:**
   - My8K account ID
   - Username (copyable)
   - Password (hidden, revealable)
   - Server URL
   - Max connections
   - Account status

4. **Associated Orders:**
   - Table of all related orders
   - Columns: Order ID, Amount, Status, Paid At

5. **Provisioning History:**
   - Table of all provisioning attempts
   - Columns: Action, Status, Attempt #, Timestamp, Error (if any)

6. **Actions:**
   - Retry Provisioning (button)
   - Extend Expiry (date picker + confirm)
   - Suspend Account (confirm modal)
   - Cancel Subscription (confirm modal)

#### 11.2.4 Orders List

**URL:** `/admin/orders`

**Features:**
- **Filters:**
  - Status (all, pending_provisioning, provisioned, provisioning_failed, refunded)
  - Date range
  - User search

- **DataTable Columns:**
  - Order ID
  - WooCommerce Order ID
  - User (email)
  - Amount
  - Status (badge)
  - Paid At
  - Provisioned At
  - Actions (View, Retry)

- **Actions:**
  - View Order Details
  - Retry Provisioning (for failed orders)

#### 11.2.5 Failed Jobs Page

**URL:** `/admin/failed-jobs`

**Features:**
- **Filters:**
  - Job type (ProvisionAccountJob, ExtendAccountJob, etc.)
  - Date range
  - Error code

- **DataTable Columns:**
  - UUID
  - Job Type
  - Failed At
  - Exception Message (truncated)
  - Actions (View, Retry, Delete)

- **Bulk Actions:**
  - Retry All Visible
  - Delete All Visible
  - Retry All Failed Jobs (confirmation required)

- **Detail View:**
  - Full exception stack trace
  - Job payload (formatted JSON)
  - Retry button
  - Delete button

#### 11.2.6 Provisioning Logs

**URL:** `/admin/provisioning-logs`

**Features:**
- **Filters:**
  - Action (create, extend, suspend, etc.)
  - Status (pending, success, failed, retrying)
  - Date range
  - Subscription/Order ID search

- **DataTable Columns:**
  - ID
  - Subscription ID
  - Order ID
  - Action
  - Status (badge)
  - Attempt #
  - Duration (ms)
  - Created At
  - Actions (View Details)

- **Detail View:**
  - Full request sent to My8K
  - Full response received
  - Error message and code (if failed)
  - Link to related subscription/order

**Performance:** This table will grow large; implement archiving strategy.

#### 11.2.7 Plans Management

**URL:** `/admin/plans`

**Features:**
- **List View:**
  - All plans with name, price, billing_interval, status
  - Toggle active/inactive
  - Edit/Delete actions

- **Create/Edit Form:**
  - Name, slug (auto-generated from name)
  - Description (rich text editor)
  - Price, currency
  - Billing interval (dropdown)
  - Duration (days)
  - Max devices
  - WooCommerce Product ID (for mapping)
  - My8K Plan Code (for API calls)
  - Is Active toggle

- **Validation:**
  - Unique slug
  - WooCommerce ID unique (if provided)
  - My8K Plan Code matches valid codes from API

### 11.3 Authorization

**Roles:**
- `admin` - Full access to all admin pages
- `support` - Read-only access + manual provisioning retry

**Implementation:**
- Use Laravel Gates or Policies
- Middleware: `can:access-admin` on all admin routes
- Check specific permissions for destructive actions (delete, cancel)

**Admin User Setup:**
- Add `is_admin` boolean to `users` table
- Seed initial admin user
- Admin panel link only visible to admin users

### 11.4 UI/UX Guidelines

**Technology Stack:**
- **Livewire 3** for reactive components
- **Flux UI Free** components (buttons, badges, modals, forms)
- **Tailwind CSS v4** for custom styling
- **Heroicons** for icons

**Design Principles:**
- Clean, minimal interface
- Color-coded status badges (green=success, yellow=pending, red=failed)
- Confirm modals for destructive actions
- Toast notifications for action feedback
- Responsive (tablet and desktop support)

**Performance:**
- Lazy-load large datatables
- Debounce search inputs (300ms)
- Use Livewire pagination for large datasets
- Cache chart data (5-minute TTL)

---

## 12. Security Requirements

### 12.1 Authentication & Authorization

**Webhook Endpoints:**
- HMAC signature validation (SHA-256)
- Reject requests with invalid/missing signatures
- Log all failed authentication attempts
- Rate limiting: 100 requests/minute per IP

**Admin Interface:**
- Protected by `auth` middleware
- Require `admin` role/permission
- 2FA recommended for admin users
- Session timeout: 2 hours of inactivity

**API Key Storage:**
- Store My8K API key in `.env`
- Never log or display API keys in UI
- Rotate keys periodically (quarterly)

### 12.2 Data Protection

**Encryption at Rest:**
- Encrypt `service_accounts.username` and `password` fields using Laravel's encrypted casting
- Database encryption for sensitive columns

**Encryption in Transit:**
- Enforce HTTPS for all web traffic
- TLS 1.2+ for My8K API calls
- Verify SSL certificates (no self-signed in production)

**PII Handling:**
- Redact sensitive data in logs (passwords, API keys, payment details)
- Mask customer emails in non-admin UI
- GDPR compliance: allow data export/deletion

### 12.3 Input Validation

**Webhook Payloads:**
- Validate JSON structure
- Type-check all fields
- Sanitize strings
- Reject payloads exceeding 1MB

**Admin Forms:**
- Laravel Form Requests for validation
- CSRF protection on all forms
- XSS prevention (escape output)
- SQL injection prevention (use Eloquent, no raw queries with user input)

### 12.4 Rate Limiting & Abuse Prevention

**Webhook Endpoints:**
- 100 requests/minute per IP
- 1000 requests/hour per IP
- Block IPs after 10 failed auth attempts (1-hour ban)

**Admin Interface:**
- 60 requests/minute per user
- Login rate limit: 5 attempts/minute (Fortify default)

**My8K API Calls:**
- Respect My8K rate limits (document their limits)
- Implement backoff if 429 received
- Circuit breaker pattern (stop calling API if consistently failing)

### 12.5 Logging & Audit Trail

**What to Log:**
- All webhook receipts (success and failure)
- All provisioning attempts and outcomes
- Admin actions (manual provisions, retries, cancellations)
- Authentication failures
- API errors

**What NOT to Log:**
- Plaintext passwords or API keys
- Full credit card numbers
- Unencrypted PII

**Log Retention:**
- Application logs: 90 days
- Audit logs: 2 years
- Provisioning logs: 1 year (then archive)

**Log Storage:**
- Use Laravel's logging (`storage/logs/laravel.log`)
- Rotate daily logs
- Consider external logging service (Papertrail, Logtail) for production

### 12.6 Dependency Security

**Composer Packages:**
- Run `composer audit` regularly
- Update dependencies quarterly
- Monitor security advisories

**Environment Variables:**
- Never commit `.env` to version control
- Use different credentials per environment (dev/staging/prod)
- Restrict access to production `.env`

### 12.7 Infrastructure Security

**Web Server:**
- Disable directory listing
- Hide server version headers
- Configure firewall (allow 80/443 only)

**Database:**
- Restrict database access to application server IP only
- Use strong passwords
- Backup encrypted databases

**Queue Workers:**
- Run as non-root user
- Monitor for unauthorized access
- Secure Supervisor configuration

---

## 13. Non-Functional Requirements

### 13.1 Performance

**Response Times:**
- Webhook endpoints: < 200ms (excluding job dispatch)
- Admin dashboard load: < 2 seconds
- Subscription list page: < 3 seconds (with 10,000 records)
- Provisioning job execution: < 10 seconds (My8K API dependent)

**Throughput:**
- Handle 100 orders/minute during peak traffic
- Process 1000 provisioning jobs/hour
- Support 10,000 active subscriptions initially

**Database:**
- Optimize queries with indexes
- Use eager loading to prevent N+1 queries
- Paginate all large datasets

### 13.2 Scalability

**Horizontal Scaling:**
- Stateless application (session stored in database/redis)
- Load balancer support (sticky sessions not required)
- Queue workers can scale independently (add more workers as needed)

**Database Scaling:**
- Read replicas for reporting queries (Phase 2)
- Partition large tables if exceeding 1M rows (provisioning_logs)

**Queue Scaling:**
- Upgrade from database to Redis queue for performance
- Use separate Redis instance for cache vs. queue
- Consider managed queue service (SQS) for high volume

### 13.3 Reliability

**Uptime Target:** 99.5% (excluding planned maintenance)

**Fault Tolerance:**
- Retry logic for transient failures
- Graceful degradation (show cached data if API down)
- Dead letter queue for permanently failed jobs

**Backup & Recovery:**
- Daily automated database backups
- 30-day retention
- Tested restore procedure (quarterly drill)

**Monitoring:**
- Health check endpoint `/health` (returns 200 if app is healthy)
- Uptime monitoring (Pingdom, UptimeRobot)
- Error tracking (Sentry, Bugsnag)

### 13.4 Maintainability

**Code Quality:**
- Follow Laravel best practices
- PSR-12 coding standards
- PHP 8.3+ type hints
- 80%+ test coverage (Pest tests)

**Documentation:**
- Inline PHPDoc blocks
- README with setup instructions
- Architecture decision records (ADR)
- API documentation (Swagger/OpenAPI for future external API)

**Deployment:**
- Zero-downtime deployments
- Database migrations run automatically
- Rollback procedure documented
- Deployment checklist

### 13.5 Observability

**Application Monitoring:**
- Laravel Telescope (development)
- Laravel Pulse (lightweight production monitoring)
- Custom metrics dashboard

**Key Metrics:**
- Request rate and error rate
- Queue depth and processing time
- Database query time
- Cache hit rate
- Provisioning success rate

**Alerting:**
- Slack integration for critical alerts
- Email for warnings
- Escalation policy (who to contact)

---

## 14. Success Metrics

### 14.1 Primary KPIs

| Metric | Target | Measurement | Frequency |
|--------|--------|-------------|-----------|
| Provisioning Success Rate | 99.5% | (Successful provisions / Total orders) × 100 | Daily |
| Average Provisioning Time | < 5 minutes | Time from payment to credentials sent | Hourly |
| Manual Intervention Rate | < 1% | Failed orders requiring admin retry / Total | Daily |
| Customer Activation Time | < 10 minutes | Payment to first login (via support data) | Weekly |

### 14.2 Operational Metrics

| Metric | Target | Measurement | Frequency |
|--------|--------|-------------|-----------|
| Queue Processing Time (p95) | < 30 seconds | 95th percentile job duration | Hourly |
| Failed Jobs Count | < 10 per day | Count in failed_jobs table | Daily |
| My8K API Error Rate | < 1% | Failed API calls / Total calls | Hourly |
| Webhook Processing Time | < 200ms | Average response time | Hourly |

### 14.3 Business Metrics

| Metric | Target | Measurement | Frequency |
|--------|--------|-------------|-----------|
| Support Ticket Reduction | -50% | Tickets related to activation delays | Monthly |
| Revenue Recognition Time | < 24 hours | Order to provisioning complete | Weekly |
| Churn Rate | < 5% | Cancelled subs / Total subs | Monthly |

### 14.4 User Satisfaction

| Metric | Target | Measurement | Frequency |
|--------|--------|-------------|-----------|
| Customer NPS | > 50 | Post-activation survey | Quarterly |
| Activation Email Open Rate | > 70% | Email analytics | Weekly |
| Time to First Support Contact | > 48 hours | Support ticket timestamp | Monthly |

**Reporting:**
- Weekly email summary to stakeholders
- Monthly business review presentation
- Real-time dashboard for ops team

---

## 15. Implementation Roadmap

### 15.1 Phase 1: MVP (Weeks 1-4)

**Goal:** Core provisioning for new orders only.

**Deliverables:**
1. **Database Schema & Models**
   - Create migrations for all tables (with UUIDs and string-based enum columns)
   - Define Eloquent models with relationships and casts
   - Create PHP-backed enums in `app/Enums/`
   - Seeders for test data
   - **Acceptance:** All migrations run successfully, factories work, enums cast properly

2. **My8K API Integration**
   - Implement `My8kApiClient` service
   - Create account endpoint
   - Error handling and retry logic
   - **Acceptance:** Can create test account via Tinker

3. **WooCommerce Webhook Ingestion**
   - `/webhooks/woocommerce/order-completed` endpoint
   - Signature validation
   - Idempotency checking
   - **Acceptance:** Webhook test succeeds with valid signature

3a. **WooCommerce REST API Client**
   - Install `automattic/woocommerce` package
   - Implement `WooCommerceApiClient` service
   - Configure API credentials
   - **Acceptance:** Can fetch order from WooCommerce via Tinker

4. **Provisioning Queue Jobs**
   - `ProvisionAccountJob` implementation
   - Exponential backoff retry
   - Failed job handling
   - **Acceptance:** Job processes order and creates service account

5. **Basic Admin Dashboard**
   - Subscriptions list (read-only)
   - Orders list
   - Failed jobs list with retry button
   - **Acceptance:** Admin can view data and retry failed jobs

6. **Email Notifications**
   - Credentials ready email
   - Provisioning failed email (to admin)
   - **Acceptance:** Emails sent successfully after provisioning

**Testing:**
- Unit tests for models and services
- Feature tests for webhook endpoints
- Job tests with fake My8K API

**Deployment:**
- Deploy to staging environment
- Manual smoke testing
- Process 10 test orders end-to-end

---

### 15.2 Phase 2: Lifecycle & Admin (Weeks 5-6)

**Goal:** Handle renewals, cancellations, and full admin UI.

**Deliverables:**
1. **Renewal Processing**
   - `/webhooks/woocommerce/subscription-renewed` endpoint
   - `ExtendAccountJob` implementation
   - Update subscription and service account expiry
   - Add WooCommerce order note after renewal
   - **Acceptance:** Renewal extends My8K account correctly and syncs to WooCommerce

2. **Cancellation Handling**
   - `/webhooks/woocommerce/subscription-cancelled` endpoint
   - Update subscription status
   - Stop auto-renewal
   - **Acceptance:** Cancelled subscriptions do not renew

3. **Expiration Management**
   - `ExpireSubscriptionsCommand` scheduled job
   - Mark expired subscriptions
   - Send expiry notifications
   - **Acceptance:** Daily cron expires old subscriptions

4. **Enhanced Admin Interface**
   - Subscription detail modal
   - Manual provisioning controls
   - Suspend/reactivate actions
   - Extend expiry date form
   - "Sync from WooCommerce" button
   - **Acceptance:** Admin can manage subscriptions fully

5. **Provisioning Logs Page**
   - Audit trail viewing
   - Filter by status, action, date
   - **Acceptance:** Admin can debug provisioning issues

6. **WooCommerce Reconciliation**
   - `ReconcileWooCommerceOrdersCommand` scheduled job
   - Daily sync to catch missed webhooks
   - Compare WooCommerce orders with local records
   - **Acceptance:** Reconciliation job runs daily and alerts on discrepancies

**Testing:**
- Test renewal flow with fake webhooks
- Test cancellation and expiration logic
- Admin UI browser tests (Pest v4)

**Deployment:**
- Deploy to staging
- Beta test with limited customers (10-20 orders)

---

### 15.3 Phase 3: Analytics & Optimization (Weeks 7-8)

**Goal:** Operational visibility and performance tuning.

**Deliverables:**
1. **Analytics Dashboard**
   - Stat cards (active subs, success rate, failed jobs)
   - Charts (success rate, order status, error types)
   - Recent activity feed
   - **Acceptance:** Dashboard updates every 60 seconds

2. **Plans Management**
   - CRUD interface for plans
   - WooCommerce and My8K plan mapping
   - **Acceptance:** Admin can create/edit plans

3. **Performance Optimization**
   - Add database indexes
   - Optimize N+1 queries
   - Implement query caching
   - **Acceptance:** Dashboard loads in < 2 seconds

4. **Monitoring & Alerts**
   - Set up health checks
   - Configure Slack alerts
   - Error tracking integration (Sentry)
   - **Acceptance:** Alert fires when queue depth exceeds threshold

5. **Payment Failure Handling**
   - `/webhooks/woocommerce/subscription-payment-failed` endpoint
   - Suspend subscription after grace period
   - Send payment reminder emails
   - **Acceptance:** Failed payment suspends service after 7 days

**Testing:**
- Load testing (simulate 100 orders/minute)
- Stress testing (queue backlog recovery)
- Alert testing (trigger conditions manually)

**Deployment:**
- Deploy to production
- Full customer rollout

---

### 15.4 Phase 4: Advanced Features (Weeks 9-12)

**Goal:** Enhanced customer experience and operational efficiency.

**Deliverables:**
1. **Customer Subscription Dashboard**
   - View active subscriptions
   - Download credentials and M3U links
   - View payment history
   - Cancel auto-renewal
   - **Acceptance:** Customers can self-service

2. **Upgrade/Downgrade Flow**
   - Change plan mid-cycle
   - Prorated billing (if supported by WooCommerce)
   - Update My8K account accordingly
   - **Acceptance:** Plan change reflects in My8K

3. **Advanced Analytics**
   - Export reports to CSV
   - Custom date range filters
   - Cohort analysis (retention by plan)
   - **Acceptance:** Admin can export data for reporting

4. **Automated Testing**
   - CI/CD pipeline (GitHub Actions)
   - Automated browser tests
   - Test coverage reporting
   - **Acceptance:** Tests run on every commit

5. **Documentation**
   - API documentation (for future integrations)
   - Admin user guide
   - Customer onboarding guide
   - **Acceptance:** Documentation complete and accessible

**Optional Enhancements (if time permits):**
- Multi-language support
- SMS notifications
- Referral program
- Gift subscriptions

**Deployment:**
- Production deployment with monitoring
- Post-launch review (2 weeks after)

---

### 15.5 Dependencies & Critical Path

**Critical Path:**
1. Database schema → My8K API client → Webhook ingestion → Queue jobs → Email notifications
2. Cannot test webhooks without My8K API integration
3. Cannot deploy admin UI without core provisioning working
4. Analytics requires provisioning logs data

**External Dependencies:**
- WooCommerce webhook setup (requires production site access)
- My8K API credentials (request from My8K support)
- Email service configuration (Postmark, SES)
- SSL certificate for HTTPS

**Risk to Timeline:**
- My8K API documentation incomplete → Allocate 3 days for exploration
- WooCommerce webhook reliability issues → Implement robust retry and dedup
- Performance bottlenecks → Budget 1 week for optimization

---

## 16. Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation Strategy |
|------|--------|------------|---------------------|
| **My8K API Downtime** | High (blocks all provisioning) | Medium | - Implement retry with exponential backoff<br>- Queue jobs for later processing<br>- Alert admin immediately<br>- SLA discussion with My8K |
| **WooCommerce Webhook Failures** | High (missed orders) | Low | - Idempotency prevents duplicates<br>- Implement webhook retry on WooCommerce side<br>- Periodic sync job to catch missed orders |
| **Duplicate Events** | Medium (double provisioning) | Medium | - Idempotency keys on all webhook events<br>- Database unique constraints<br>- Transaction locks where needed |
| **My8K Credit Exhaustion** | High (revenue loss) | Low | - Monitor credit balance via API (if available)<br>- Alert admin at 20% remaining<br>- Auto-pause new orders if exhausted |
| **Queue Worker Crashes** | High (provisioning stops) | Medium | - Supervisor auto-restart<br>- Health check monitoring<br>- Alert if no jobs processed in 10 minutes |
| **Database Performance** | Medium (slow admin UI) | Low | - Add indexes proactively<br>- Paginate all queries<br>- Archive old provisioning logs |
| **Network Failures** | Medium (API timeouts) | Medium | - Retry logic handles transient failures<br>- Increase timeout if My8K API slow<br>- Circuit breaker to stop calling if persistent failures |
| **Incomplete My8K API Docs** | Medium (delays) | High | - Request detailed docs upfront<br>- Test all endpoints in sandbox<br>- Budget extra time for discovery |
| **Refund/Chargeback Handling** | Low (edge case) | Low | - WooCommerce webhook for refunds<br>- Optionally suspend account on refund<br>- Manual admin review for chargebacks |
| **Data Privacy Compliance** | High (legal risk) | Low | - Encrypt credentials at rest<br>- Implement data export/deletion<br>- GDPR compliance audit |
| **Key Rotation** | Low (service interruption) | Low | - Document key rotation procedure<br>- Test in staging first<br>- Rotate during low-traffic window |

**Risk Management Plan:**
- Weekly risk review in standup
- Mitigation tasks added to backlog
- Escalation path for critical risks (e.g., My8K outage)

---

## 17. Acceptance Criteria

### 17.1 Functional Acceptance Criteria

#### 17.1.1 Successful Provisioning Flow

**Given:** A customer completes a WooCommerce order for an IPTV plan
**When:** WooCommerce sends `order.completed` webhook
**Then:**
- ✅ Webhook is received and validated (200 OK returned)
- ✅ Order record is created in database (status: `pending_provisioning`)
- ✅ Subscription record is created (status: `pending`)
- ✅ `ProvisionAccountJob` is dispatched to queue
- ✅ Job calls My8K API to create account
- ✅ `ServiceAccount` record is created with credentials
- ✅ Order status updated to `provisioned`
- ✅ Subscription status updated to `active`
- ✅ Provisioning log entry created (status: `success`)
- ✅ Customer receives credentials email within 5 minutes

#### 17.1.2 Retry on Failure

**Given:** My8K API returns 503 (service unavailable)
**When:** `ProvisionAccountJob` executes
**Then:**
- ✅ Job logs failure to `provisioning_logs` (status: `failed`)
- ✅ Job is released back to queue with backoff delay
- ✅ Job retries up to 5 times with exponential backoff
- ✅ On success, provisioning completes normally
- ✅ If all retries exhausted, job moves to `failed_jobs` table
- ✅ Order status updated to `provisioning_failed`
- ✅ Admin receives alert notification

#### 17.1.3 Idempotency

**Given:** WooCommerce sends duplicate `order.completed` webhook
**When:** Webhook is received a second time
**Then:**
- ✅ Idempotency key check detects duplicate
- ✅ 200 OK returned without processing
- ✅ No duplicate Order or Subscription created
- ✅ No duplicate My8K account created

#### 17.1.4 Renewal Processing

**Given:** Customer has active subscription with auto-renewal enabled
**When:** WooCommerce sends `subscription.renewed` webhook
**Then:**
- ✅ Renewal order is created
- ✅ `ExtendAccountJob` is dispatched
- ✅ My8K API is called to extend account expiry
- ✅ `ServiceAccount.expires_at` is updated (+30 days)
- ✅ `Subscription.expires_at` and `last_renewal_at` updated
- ✅ Customer receives renewal confirmation email

#### 17.1.5 Cancellation

**Given:** Customer cancels subscription in WooCommerce
**When:** Cancellation webhook is received
**Then:**
- ✅ Subscription status updated to `cancelled`
- ✅ `cancelled_at` timestamp set
- ✅ Auto-renewal disabled
- ✅ My8K account remains active until expiry date
- ✅ Customer receives cancellation confirmation email

#### 17.1.6 Expiration

**Given:** Subscription `expires_at` date has passed
**When:** Daily `ExpireSubscriptionsCommand` runs
**Then:**
- ✅ Subscription status updated to `expired`
- ✅ ServiceAccount status updated to `expired`
- ✅ Customer receives expiry notification (if applicable)
- ✅ No API call made to My8K (natural expiration)

#### 17.1.7 Admin Manual Provisioning

**Given:** An order failed provisioning (status: `provisioning_failed`)
**When:** Admin clicks "Retry Provisioning" in admin dashboard
**Then:**
- ✅ `ProvisionAccountJob` is dispatched to queue again
- ✅ Provisioning attempt logged with admin user ID
- ✅ Admin sees success/failure notification
- ✅ If successful, order status updated to `provisioned`

#### 17.1.8 Credit Exhaustion Handling

**Given:** My8K API returns 402 (insufficient credits)
**When:** `ProvisionAccountJob` executes
**Then:**
- ✅ Job fails immediately (no retries)
- ✅ Error logged with code `API_INSUFFICIENT_CREDITS`
- ✅ Order status set to `provisioning_failed`
- ✅ High-priority alert sent to admin (Slack + email)
- ✅ Job added to failed jobs table for manual retry

### 17.2 Non-Functional Acceptance Criteria

#### 17.2.1 Performance

- ✅ Webhook endpoint responds in < 200ms (excluding job dispatch)
- ✅ Provisioning job completes in < 10 seconds (average)
- ✅ Admin dashboard loads in < 2 seconds with 10,000 subscriptions
- ✅ Subscriptions list supports 100,000 records with pagination

#### 17.2.2 Scalability

- ✅ System handles 100 orders/minute without queue backlog
- ✅ Can scale to 5 concurrent queue workers without issues
- ✅ Database queries use indexes (no full table scans on large tables)

#### 17.2.3 Reliability

- ✅ 99.5% uptime (monitored via health checks)
- ✅ 99.5% provisioning success rate (with retries)
- ✅ Zero data loss (all webhooks processed or logged)
- ✅ Failed jobs can be retried manually with 100% success rate (if My8K is healthy)

#### 17.2.4 Security

- ✅ All webhook signatures validated (100% of requests)
- ✅ Service account credentials encrypted at rest
- ✅ HTTPS enforced on all endpoints (HSTS headers)
- ✅ Admin panel requires authentication + authorization
- ✅ No sensitive data in logs (passwords, API keys redacted)

#### 17.2.5 Observability

- ✅ All provisioning attempts logged in `provisioning_logs`
- ✅ Admin dashboard shows real-time metrics
- ✅ Alerts fire within 1 minute of threshold breach
- ✅ Failed jobs visible in admin UI with full context

### 17.3 Test Coverage

- ✅ Unit tests for all models (factories, relationships, scopes)
- ✅ Unit tests for My8K API client (mocked HTTP responses)
- ✅ Feature tests for all webhook endpoints (valid/invalid signatures)
- ✅ Job tests with fake queues and mocked API
- ✅ Browser tests for admin UI (Pest v4)
- ✅ Overall code coverage > 80%

### 17.4 Deployment Acceptance

- ✅ All migrations run successfully on staging and production
- ✅ Queue workers start automatically (Supervisor config)
- ✅ Environment variables documented in `.env.example`
- ✅ Deployment checklist followed (zero downtime)
- ✅ Rollback procedure tested in staging

### 17.5 Documentation

- ✅ README with setup instructions
- ✅ Admin user guide (with screenshots)
- ✅ API documentation (webhook payloads, error codes)
- ✅ Architecture diagram included in docs
- ✅ Provisioning flow documented for support team

---

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| **Provisioning** | The automated process of creating or extending a My8K IPTV account after payment |
| **Idempotency** | The property that performing an operation multiple times has the same effect as performing it once |
| **Webhook** | HTTP callback triggered by WooCommerce when events occur (order completed, renewal, etc.) |
| **Queue Job** | A background task processed asynchronously by Laravel queue workers |
| **Service Account** | My8K IPTV account credentials (username, password, server URL) |
| **Subscription** | A customer's recurring payment agreement for an IPTV plan |
| **Order** | A single payment transaction (initial purchase or renewal) |
| **Plan** | A subscription product (e.g., "Premium Monthly") with pricing and features |
| **Backoff** | Delay strategy for retrying failed operations (exponential backoff: 10s, 30s, 90s, etc.) |
| **Circuit Breaker** | Pattern to stop calling an API if it's consistently failing (prevent cascading failures) |

---

## Appendix B: Configuration Reference

### Required Environment Variables

```bash
# Application
APP_NAME="My8K Subscriptions"
APP_ENV=production
APP_KEY=base64:xxx
APP_DEBUG=false
APP_URL=https://subscriptions.example.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my8k_subscriptions
DB_USERNAME=root
DB_PASSWORD=secret

# Queue
QUEUE_CONNECTION=database  # or redis
QUEUE_PROVISIONING_RETRY_AFTER=90
QUEUE_PROVISIONING_MAX_TRIES=5
QUEUE_PROVISIONING_BACKOFF=10,30,90,270,810

# WooCommerce Integration (Webhooks - Inbound)
WOOCOMMERCE_WEBHOOK_SECRET=wh_secret_xxx

# WooCommerce REST API (Outbound - Laravel → WooCommerce)
WOOCOMMERCE_API_URL=https://store.example.com
WOOCOMMERCE_CONSUMER_KEY=ck_xxxxxxxxxxxxx
WOOCOMMERCE_CONSUMER_SECRET=cs_xxxxxxxxxxxxx

# My8K IPTV API
MY8K_API_URL=https://api.my8k.tv/v1
MY8K_API_KEY=my8k_api_key_xxx
MY8K_API_TIMEOUT=30
MY8K_RETRY_ATTEMPTS=5

# Email (example: Postmark)
MAIL_MAILER=postmark
POSTMARK_TOKEN=xxx

# Monitoring (optional)
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx

# Alerts
SLACK_ALERT_WEBHOOK=https://hooks.slack.com/services/xxx
ADMIN_ALERT_EMAIL=admin@example.com
```

---

## Appendix C: My8K API Reference (Example)

**Note:** This is a hypothetical API structure. Update with actual My8K API documentation.

### Authentication
```
Authorization: Bearer {API_KEY}
Content-Type: application/json
```

### Create Account
```http
POST /accounts/create
{
  "plan_code": "premium_monthly",
  "duration_days": 30,
  "email": "customer@example.com",
  "max_connections": 2
}

Response 200:
{
  "status": "success",
  "data": {
    "account_id": "acc_abc123",
    "username": "user12345",
    "password": "securepass",
    "server_url": "http://example.m3u8",
    "expires_at": "2026-02-10T23:59:59Z",
    "max_connections": 2
  }
}

Response 402:
{
  "status": "error",
  "message": "Insufficient credits",
  "code": "INSUFFICIENT_CREDITS"
}
```

### Extend Account
```http
POST /accounts/{account_id}/extend
{
  "duration_days": 30
}

Response 200:
{
  "status": "success",
  "data": {
    "expires_at": "2026-03-12T23:59:59Z"
  }
}
```

### Query Account
```http
GET /accounts/{account_id}

Response 200:
{
  "status": "success",
  "data": {
    "account_id": "acc_abc123",
    "status": "active",
    "expires_at": "2026-02-10T23:59:59Z",
    "created_at": "2026-01-10T12:00:00Z"
  }
}
```

---

## Appendix D: WooCommerce Webhook Signature Validation

```php
// app/Http/Middleware/ValidateWooCommerceSignature.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateWooCommerceSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('services.woocommerce.webhook_secret');

        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        if (!hash_equals($expectedSignature, $signature)) {
            \Log::warning('Invalid WooCommerce webhook signature', [
                'ip' => $request->ip(),
                'payload' => $payload,
            ]);

            abort(401, 'Invalid signature');
        }

        return $next($request);
    }
}
```

---

## Appendix E: Supervisor Configuration for Queue Workers

```ini
; /etc/supervisor/conf.d/my8k-provisioning-worker.conf

[program:my8k-provisioning-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --queue=provisioning,default,notifications --sleep=3 --tries=5 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/my8k-worker.log
stopwaitsecs=3600
```

**Commands:**
```bash
# Reload Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start my8k-provisioning-worker:*

# Check status
sudo supervisorctl status
```

---

## Appendix F: WooCommerce REST API Examples

### Get Order Details

```php
use App\Services\WooCommerceApiClient;

$client = app(WooCommerceApiClient::class);

// Get single order
$order = $client->getOrder(12345);

echo "Order Total: {$order['total']}\n";
echo "Customer Email: {$order['billing']['email']}\n";
```

### Get Subscription Details

```php
$subscription = $client->getSubscription(67890);

echo "Status: {$subscription['status']}\n";
echo "Next Payment: {$subscription['next_payment_date']}\n";
```

### Add Order Note

```php
// Add internal note (not visible to customer)
$client->addOrderNote(12345, 'Provisioning completed successfully', false);

// Add customer-visible note
$client->addOrderNote(12345, 'Your IPTV account is ready!', true);
```

### Update Subscription Metadata

```php
$client->updateSubscriptionMeta(67890, [
    ['key' => '_my8k_account_id', 'value' => 'acc_abc123'],
    ['key' => '_provisioned_at', 'value' => '2026-01-10T12:34:56Z'],
    ['key' => '_my8k_expires_at', 'value' => '2026-02-10T23:59:59Z'],
]);
```

### Query Recent Orders

```php
// Get orders from last 24 hours
$orders = $client->getOrders([
    'modified_after' => now()->subDay()->toIso8601String(),
    'status' => 'completed',
    'per_page' => 100,
]);

foreach ($orders as $order) {
    echo "Order #{$order['id']}: {$order['total']}\n";
}
```

### Query Active Subscriptions

```php
$subscriptions = $client->getSubscriptions([
    'status' => 'active',
    'per_page' => 50,
]);

foreach ($subscriptions as $sub) {
    echo "Subscription #{$sub['id']}: {$sub['status']}\n";
}
```

### Full Integration Example (Provisioning Job)

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\My8kApiClient;
use App\Services\WooCommerceApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(
        My8kApiClient $my8kClient,
        WooCommerceApiClient $wooCommerceClient
    ): void {
        // 1. Get full order details from WooCommerce (if needed)
        $wcOrder = $wooCommerceClient->getOrder(
            $this->order->woocommerce_order_id
        );

        // 2. Provision My8K account
        $accountData = $my8kClient->createAccount(
            planCode: $this->order->subscription->plan->my8k_plan_code,
            durationDays: $this->order->subscription->plan->duration_days,
            email: $this->order->user->email,
            maxConnections: $this->order->subscription->plan->max_devices ?? 1
        );

        // 3. Store service account in Laravel
        $serviceAccount = $this->order->subscription->serviceAccount()->create([
            'user_id' => $this->order->user_id,
            'my8k_account_id' => $accountData['account_id'],
            'username' => $accountData['username'],
            'password' => $accountData['password'],
            'server_url' => $accountData['server_url'],
            'max_connections' => $accountData['max_connections'],
            'status' => \App\Enums\ServiceAccountStatus::Active,
            'activated_at' => now(),
            'expires_at' => $accountData['expires_at'],
        ]);

        // 4. Update order status
        $this->order->update([
            'status' => \App\Enums\OrderStatus::Provisioned,
            'provisioned_at' => now(),
        ]);

        // 5. Update subscription status
        $this->order->subscription->update([
            'status' => \App\Enums\SubscriptionStatus::Active,
            'starts_at' => now(),
            'expires_at' => $accountData['expires_at'],
        ]);

        // 6. Add note to WooCommerce order
        $wooCommerceClient->addOrderNote(
            $this->order->woocommerce_order_id,
            "✅ My8K IPTV account provisioned successfully.\n\n" .
            "Account ID: {$accountData['account_id']}\n" .
            "Expires: {$accountData['expires_at']}"
        );

        // 7. Store My8K account ID in WooCommerce subscription
        if ($this->order->subscription->woocommerce_subscription_id) {
            $wooCommerceClient->updateSubscriptionMeta(
                $this->order->subscription->woocommerce_subscription_id,
                [
                    ['key' => '_my8k_account_id', 'value' => $accountData['account_id']],
                    ['key' => '_provisioned_at', 'value' => now()->toIso8601String()],
                    ['key' => '_my8k_username', 'value' => $accountData['username']],
                    ['key' => '_my8k_expires_at', 'value' => $accountData['expires_at']],
                ]
            );
        }

        // 8. Send credentials email to customer
        // (dispatch notification job)
    }

    public function failed(\Throwable $exception): void
    {
        // Mark order as failed
        $this->order->update([
            'status' => \App\Enums\OrderStatus::ProvisioningFailed,
        ]);

        // Add failure note to WooCommerce
        try {
            $client = app(WooCommerceApiClient::class);
            $client->addOrderNote(
                $this->order->woocommerce_order_id,
                "❌ Provisioning failed: {$exception->getMessage()}"
            );
        } catch (\Exception $e) {
            \Log::error('Failed to add WooCommerce note', ['error' => $e->getMessage()]);
        }
    }
}
```

---

## Document Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-01-10 | Product Team | Initial draft based on project requirements |
| 1.1 | 2026-01-10 | Product Team | Updated with UUID primary keys, PHP-backed enums, and WooCommerce bidirectional integration |

---

**END OF DOCUMENT**
