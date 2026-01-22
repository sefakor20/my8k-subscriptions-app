# Production Migration Fix - Foreign Key Column Type Mismatches

## Issue Summary

**Problem**: Foreign key constraints failing in production MySQL with error:
```
SQLSTATE[HY000]: General error: 3780 Referencing column and referenced column in foreign key constraint are incompatible.
```

**Root Cause**: Using `foreignId()` (creates BIGINT) to reference UUID primary keys. MySQL requires foreign key columns to have identical types.

**Why it worked locally**: SQLite is more permissive with foreign key type checking than MySQL.

## Solution Implemented

Changed `foreignId()` to `foreignUuid()` in 4 locations across 3 migration files to match UUID primary keys.

## Changes Made

### 1. reseller_credit_logs Migration
**File**: `database/migrations/2026_01_11_211913_create_reseller_credit_logs_table.php`

**Line 22:**
- **Before**: `$table->foreignId('related_provisioning_log_id')` (BIGINT)
- **After**: `$table->foreignUuid('related_provisioning_log_id')` (UUID)
- **References**: `provisioning_logs.id` (UUID)

### 2. support_tickets Migration
**File**: `database/migrations/2026_01_11_223409_create_support_tickets_table.php`

**Line 17:**
- **Before**: `$table->foreignId('user_id')` (BIGINT)
- **After**: `$table->foreignUuid('user_id')` (UUID)
- **References**: `users.id` (UUID)

**Line 24:**
- **Before**: `$table->foreignId('assigned_to')` (BIGINT)
- **After**: `$table->foreignUuid('assigned_to')` (UUID)
- **References**: `users.id` (UUID)

### 3. support_messages Migration
**File**: `database/migrations/2026_01_11_223530_create_support_messages_table.php`

**Line 18:**
- **Before**: `$table->foreignId('user_id')` (BIGINT)
- **After**: `$table->foreignUuid('user_id')` (UUID)
- **References**: `users.id` (UUID)

## Verification (Local)

✅ All migrations run successfully:
```bash
php artisan migrate:fresh --force
# Result: All 30 migrations completed without errors
```

## Files Modified

```
database/migrations/2026_01_11_211913_create_reseller_credit_logs_table.php
database/migrations/2026_01_11_223409_create_support_tickets_table.php
database/migrations/2026_01_11_223530_create_support_messages_table.php
```

---

## Production Deployment Steps

### Step 1: Check Current State

SSH into production and check which migrations have run:

```bash
cd /home/forge/motv.rcodez.com/current
php artisan migrate:status
```

Check which tables exist:

```bash
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'reseller_credit_logs: ' . (Schema::hasTable('reseller_credit_logs') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'support_tickets: ' . (Schema::hasTable('support_tickets') ? 'EXISTS' : 'MISSING') . PHP_EOL;
echo 'support_messages: ' . (Schema::hasTable('support_messages') ? 'EXISTS' : 'MISSING') . PHP_EOL;
"
```

### Step 2: Clean Up Failed Migrations

Based on the error, `reseller_credit_logs` migration failed. Check if any of these tables exist partially:

```bash
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;

// Drop tables if they exist
Schema::dropIfExists('support_messages');
Schema::dropIfExists('support_tickets');
Schema::dropIfExists('reseller_credit_logs');

echo 'Tables dropped if they existed' . PHP_EOL;
"
```

Remove failed migration records:

```bash
php artisan tinker --execute="
DB::table('migrations')
  ->whereIn('migration', [
    '2026_01_11_211913_create_reseller_credit_logs_table',
    '2026_01_11_223409_create_support_tickets_table',
    '2026_01_11_223530_create_support_messages_table'
  ])
  ->delete();

echo 'Migration records cleaned' . PHP_EOL;
"
```

### Step 3: Deploy Fixed Code

Push changes to production repository:

```bash
git add database/migrations/
git commit -m "fix: Change foreignId to foreignUuid for UUID primary key references

- Fix reseller_credit_logs.related_provisioning_log_id FK type
- Fix support_tickets.user_id and assigned_to FK types
- Fix support_messages.user_id FK type
- MySQL requires FK columns to match referenced column types exactly
- Fixes error 3780: incompatible column types in foreign key constraint
"
git push origin main
```

Deploy via Forge or your deployment method.

### Step 4: Run Migrations

```bash
cd /home/forge/motv.rcodez.com/current
php artisan migrate --force
```

Expected output:
```
Running migrations.

2026_01_11_211913_create_reseller_credit_logs_table ............ DONE
2026_01_11_223409_create_support_tickets_table ................. DONE
2026_01_11_223530_create_support_messages_table ................ DONE
```

### Step 5: Verify Tables Created

Check all three tables exist:

```bash
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
echo 'reseller_credit_logs: ' . (Schema::hasTable('reseller_credit_logs') ? '✓ EXISTS' : '✗ MISSING') . PHP_EOL;
echo 'support_tickets: ' . (Schema::hasTable('support_tickets') ? '✓ EXISTS' : '✗ MISSING') . PHP_EOL;
echo 'support_messages: ' . (Schema::hasTable('support_messages') ? '✓ EXISTS' : '✗ MISSING') . PHP_EOL;
"
```

### Step 6: Verify Foreign Keys (MySQL)

For production MySQL, verify foreign keys were created:

```sql
-- Connect to MySQL
mysql -u [user] -p [database]

-- Check reseller_credit_logs
SHOW CREATE TABLE reseller_credit_logs\G

-- Check support_tickets
SHOW CREATE TABLE support_tickets\G

-- Check support_messages
SHOW CREATE TABLE support_messages\G
```

Look for foreign key constraints like:
```sql
CONSTRAINT `reseller_credit_logs_related_provisioning_log_id_foreign`
  FOREIGN KEY (`related_provisioning_log_id`)
  REFERENCES `provisioning_logs` (`id`)
  ON DELETE SET NULL
```

### Step 7: Test Application

1. Check application logs for errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Test creating records in these tables (if applicable):
   - Create a support ticket
   - Create reseller credit log entry

3. Monitor for any foreign key constraint errors

---

## Important Notes

### UUID vs BIGINT

In MySQL, these are incompatible types for foreign keys:
- **UUID**: Stored as CHAR(36) - e.g., "550e8400-e29b-41d4-a716-446655440000"
- **BIGINT**: Stored as 8-byte integer - e.g., 123456789

### Tables Using UUID Primary Keys

All these tables use UUID primary keys and require `foreignUuid()` for foreign keys:
- `users`
- `plans`
- `subscriptions`
- `service_accounts`
- `orders`
- `provisioning_logs`
- `support_tickets`
- `support_messages`
- `payment_transactions`
- `invoices`
- `plan_changes`
- `coupons`

### Laravel Migration Methods

- `$table->foreignId()` → Creates BIGINT UNSIGNED column
- `$table->foreignUuid()` → Creates CHAR(36) column

Always use `foreignUuid()` when referencing UUID primary keys.

---

## Rollback Plan (If Needed)

If migrations still fail:

1. **Take database backup** before any changes
2. **Capture exact error message** from logs
3. **Check column types** in MySQL:
   ```sql
   DESCRIBE reseller_credit_logs;
   DESCRIBE provisioning_logs;
   ```
4. **Verify UUID format** in referenced tables:
   ```sql
   SELECT id FROM provisioning_logs LIMIT 1;
   ```

---

## Testing Checklist

Before deploying to production:

- [x] Changed all 4 `foreignId()` to `foreignUuid()`
- [x] Tested locally with `migrate:fresh`
- [x] Verified all migrations run without errors
- [x] Code formatted with Pint

After deploying to production:

- [ ] Migrations complete successfully
- [ ] All three tables exist
- [ ] Foreign key constraints created
- [ ] No errors in Laravel logs
- [ ] Application functions normally

---

## Prevention for Future

To prevent this issue in the future:

1. **Always test with MySQL locally** (not just SQLite) before deploying
2. **Use `foreignUuid()` consistently** for all UUID foreign keys
3. **Review migration PR** for foreign key type compatibility
4. **Add to CI/CD**: Run migrations against MySQL in test environment

---

## Related Issues

This fix is in addition to the previous circular dependency fix from `MIGRATION_FIX_DEPLOYMENT.md`. Both issues need to be deployed together.

---

**Document Version**: 1.0
**Date**: 2026-01-22
**Author**: Claude (Migration Type Fix)
