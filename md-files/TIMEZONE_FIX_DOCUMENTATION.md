# Timezone Fix Documentation

## Problem Description

### Issue
Records in the database were being created with timestamps that appeared **1 day late** or **1 day advance** from the actual time.

### Symptoms
- Customer registration dates showing incorrect dates
- Order timestamps appearing on wrong days
- Payment history showing future or past submission times
- Daily reports including wrong data
- "Today's registrations" count showing incorrect numbers

### Example
If a customer registered at **2:00 PM on January 15, 2025 (Philippine Time)**:
- **Before Fix**: Database might show `2025-01-15 06:00:00` (UTC time, 8 hours behind)
- **After Fix**: Database correctly shows `2025-01-15 14:00:00` (Philippine time)

Depending on the time of day, this 8-hour difference could make records appear to be from the previous day or next day.

---

## Root Cause Analysis

### The Problem
There was a **timezone mismatch** between PHP and MySQL:

1. **PHP Timezone**: Set to `Asia/Manila` (UTC+8) in `includes/init.php`
   ```php
   date_default_timezone_set('Asia/Manila');
   ```

2. **MySQL Timezone**: NOT set, defaulting to server timezone (likely UTC or system default)
   - When using `NOW()`, `CURRENT_TIMESTAMP`, or `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
   - MySQL was using a different timezone than PHP

3. **Result**: 
   - PHP functions like `date()` returned Philippine time
   - MySQL functions like `NOW()` returned UTC or server time
   - This created an 8-hour (or more) discrepancy

### Why This Matters
Many database operations use MySQL's built-in date functions:
```sql
-- These all use MySQL's timezone, not PHP's
INSERT INTO orders (..., created_at) VALUES (..., NOW())
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
WHERE DATE(created_at) = CURDATE()
```

---

## The Fix

### What Was Changed
**File**: `includes/database/db_conn.php`

**Added** the following line after database connection is established:
```php
// Set MySQL timezone to match PHP timezone (Asia/Manila = UTC+8)
// This ensures NOW(), CURRENT_TIMESTAMP, and other MySQL date functions use the correct timezone
$connect->exec("SET time_zone = '+08:00'");
```

### Location in Code
```php
// Set MySQL session variables for large data handling
$connect->exec("SET SESSION wait_timeout = 300");
$connect->exec("SET SESSION interactive_timeout = 300");
$connect->exec("SET SESSION net_read_timeout = 60");
$connect->exec("SET SESSION net_write_timeout = 60");

// ✅ NEW: Set MySQL timezone to match PHP timezone
$connect->exec("SET time_zone = '+08:00'");
```

---

## What This Fixes

### ✅ Affected Areas

1. **Customer Account Registration**
   - `accounts.CreatedAt` now uses Philippine time
   - `customer_information.created_at` matches actual registration time

2. **Order Management**
   - `orders.created_at` and `orders.order_date` show correct Philippine time
   - Order history displays accurate timestamps

3. **Payment History**
   - `payment_history.payment_date` reflects actual payment time
   - `payment_history.created_at` matches submission time

4. **Loan Applications**
   - `loan_applications.application_date` shows correct time
   - `loan_applications.created_at` accurate

5. **PMS Records**
   - `car_pms_records.created_at` and `pms_date` correct
   - Service history timestamps accurate

6. **Deliveries**
   - `deliveries.delivery_date` shows correct date
   - Inventory tracking timestamps accurate

7. **Admin Actions**
   - `admin_actions.created_at` reflects actual action time
   - Audit trail timestamps correct

8. **Reports and Analytics**
   - Daily reports now filter correctly
   - "Today's registrations" count accurate
   - Monthly summaries show correct data

---

## Testing the Fix

### Run the Test Script
1. Open your browser
2. Navigate to: `http://localhost/Mitsubishi/test_scripts/test_timezone_fix.php`
3. Verify all tests pass:
   - ✅ PHP Timezone: Asia/Manila
   - ✅ MySQL Timezone: +08:00
   - ✅ Synchronization: Difference < 2 seconds
   - ✅ Database Insert: Timestamps match

### Manual Verification
```sql
-- Check MySQL timezone
SELECT @@session.time_zone, @@global.time_zone, NOW();

-- Should return:
-- session_time_zone: +08:00
-- global_time_zone: SYSTEM (or UTC)
-- NOW(): Current Philippine time
```

---

## Important Notes

### ⚠️ Existing Records
- This fix applies to **NEW records** created after the fix
- **Existing records** with incorrect timestamps are NOT automatically corrected
- If historical data accuracy is critical, you may need to:
  1. Identify affected records
  2. Adjust timestamps by adding/subtracting the timezone offset
  3. Update records manually or via migration script

### ✅ Going Forward
- All new database connections will use Asia/Manila timezone
- PHP and MySQL timestamps will be synchronized
- No more date discrepancies in new records

### 🔄 Session-Based Setting
- The timezone is set per database connection session
- Each new connection (page load, API call) sets the timezone
- This ensures consistency across all database operations

---

## Related Files

### Modified Files
- `includes/database/db_conn.php` - Added MySQL timezone setting

### Test Files
- `test_scripts/test_timezone_fix.php` - Verification script

### Configuration Files
- `includes/init.php` - PHP timezone setting (unchanged)
- `api/datetime.php` - Date/time API for Philippine time

---

## Technical Details

### Timezone Offset
- **Philippine Standard Time (PST/PHT)**: UTC+8
- **No Daylight Saving Time**: Philippines doesn't observe DST
- **Constant Offset**: Always +08:00 year-round

### MySQL Timezone Formats
```sql
-- All equivalent for Philippine time:
SET time_zone = '+08:00';          -- Offset format (used in fix)
SET time_zone = 'Asia/Manila';     -- Named timezone (requires timezone tables)
SET time_zone = 'Asia/Singapore';  -- Also UTC+8, no DST
```

We use `+08:00` because:
- Works without MySQL timezone tables
- Explicit and clear
- No dependency on server timezone configuration

### Scope of Setting
```php
$connect->exec("SET time_zone = '+08:00'");
```
- Sets timezone for the **current session** only
- Does NOT change global MySQL timezone
- Does NOT affect other applications using the same MySQL server
- Automatically applied to every new connection

---

## Troubleshooting

### If timestamps are still wrong:

1. **Clear PHP session cache**
   ```bash
   # Restart your web server
   # Or clear browser cookies/session
   ```

2. **Verify database connection**
   ```php
   // Check if db_conn.php is being included
   // Check for any errors in error logs
   ```

3. **Check MySQL timezone tables**
   ```sql
   -- If using named timezones, ensure tables are populated
   SELECT * FROM mysql.time_zone_name LIMIT 5;
   ```

4. **Verify server timezone**
   ```bash
   # Check system timezone
   date
   timedatectl  # On Linux
   ```

---

## API Endpoint for Date/Time

A new API endpoint has been created to provide current Philippine time:

**Endpoint**: `api/datetime.php`

**Usage**:
```javascript
// Get current date and time
fetch('api/datetime.php')
  .then(response => response.json())
  .then(data => console.log(data));

// Get only date
fetch('api/datetime.php?action=date')
  .then(response => response.json())
  .then(data => console.log(data));

// Get only time
fetch('api/datetime.php?action=time')
  .then(response => response.json())
  .then(data => console.log(data));

// Get timestamp
fetch('api/datetime.php?action=timestamp')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Response Example**:
```json
{
  "success": true,
  "data": {
    "datetime": "2025-01-15 14:30:45",
    "date": "2025-01-15",
    "time": "14:30:45",
    "time_12hr": "02:30:45 PM",
    "timestamp": 1736926245,
    "timezone": "Asia/Manila (PST/PHT)",
    "timezone_offset": "+08:00",
    "day_of_week": "Wednesday",
    "month": "January",
    "year": "2025"
  }
}
```

---

## Summary

✅ **Problem**: PHP and MySQL timezones were not synchronized  
✅ **Solution**: Set MySQL session timezone to match PHP timezone (+08:00)  
✅ **Result**: All new records will have correct Philippine time timestamps  
✅ **Impact**: Fixes date discrepancies in registrations, orders, payments, and reports  

**Date Fixed**: 2025-01-19  
**Files Modified**: 1 (`includes/database/db_conn.php`)  
**Files Created**: 2 (`api/datetime.php`, `test_scripts/test_timezone_fix.php`)

