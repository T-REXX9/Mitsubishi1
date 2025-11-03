# Customer Information Retrieval Investigation

## Issue Description
Customer information is not showing in the accounts.php page for some customers even though the data exists in the database.

## Files Modified

### 1. `pages/main/accounts.php`
**Line 78:** Added debug logging to track what data is being retrieved
```php
error_log("view_customer: accountId={$accountId}, customer=" . json_encode($customer));
```

### 2. `includes/database/customer_operations.php`
**Lines 41-103:** Enhanced the `getCustomerByAccountId()` method with comprehensive logging:
- Logs when query starts
- Logs successful query results with field names
- Logs sample data (firstname, lastname, account_id)
- Logs when fallback queries are used
- Logs detailed error information

## Investigation Steps

### Step 1: Use the Test Script
I've created a diagnostic test script at `test_customer_retrieval.php` that will help identify the exact issue.

**How to use:**
1. Open your browser and navigate to: `http://localhost/Mitsubishi/test_customer_retrieval.php`
2. Enter the account ID of a customer that's not showing information
3. Click "Test Retrieval"
4. Review the detailed output

The test script will show:
- ✅ Whether the account exists in the accounts table
- ✅ Whether customer_information exists for that account_id
- ✅ What data is returned by the getCustomerByAccountId() method
- ✅ Field-by-field validation of expected vs actual data
- ✅ The exact SQL query being executed

### Step 2: Check Error Logs
After viewing a customer's information in accounts.php, check your PHP error log for detailed debugging output.

**Location of error logs:**
- XAMPP: `C:\xampp\apache\logs\error.log` or `C:\xampp\php\logs\php_error_log`
- Check `php.ini` for the `error_log` setting

**What to look for:**
```
getCustomerByAccountId: Starting query for account_id=X
getCustomerByAccountId: First query SUCCESS. Keys: cusID, account_id, agent_id, lastname, firstname, ...
getCustomerByAccountId: Sample data - firstname: John, lastname: Doe, account_id: 5
```

## Potential Root Causes

### 1. **Data Type Mismatch**
The `account_id` column in `customer_information` is `int(11)` and can be NULL. If the value stored doesn't match the account Id, the JOIN will fail.

**Check:**
```sql
SELECT ci.account_id, a.Id 
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE ci.account_id IS NOT NULL;
```

### 2. **NULL account_id Values**
If `account_id` is NULL in the customer_information table, the query won't find the record.

**Check:**
```sql
SELECT * FROM customer_information WHERE account_id IS NULL;
```

### 3. **Case Sensitivity Issues**
While MySQL column names are case-insensitive, PDO returns them in the exact case as defined in the database. The JavaScript expects lowercase field names.

**Expected fields in JavaScript (line 1306 in accounts.php):**
- `firstname` (lowercase)
- `lastname` (lowercase)
- `middlename` (lowercase)
- `birthday`
- `age`
- `gender`
- `civil_status`
- `nationality`
- `mobile_number`
- `employment_status`
- `company_name`
- `position`
- `monthly_income`
- `Status` (capital S)

### 4. **Missing Records**
The customer may have an account but no corresponding customer_information record.

**Check:**
```sql
SELECT a.Id, a.Username, a.Role, ci.cusID
FROM accounts a
LEFT JOIN customer_information ci ON a.Id = ci.account_id
WHERE a.Role = 'Customer' AND ci.cusID IS NULL;
```

## Quick Diagnostic Queries

Run these queries in phpMyAdmin to diagnose the issue:

### Query 1: Find customers without customer_information
```sql
SELECT a.Id, a.Username, a.Email, a.FirstName, a.LastName
FROM accounts a
LEFT JOIN customer_information ci ON a.Id = ci.account_id
WHERE a.Role = 'Customer' AND ci.cusID IS NULL;
```

### Query 2: Check account_id consistency
```sql
SELECT 
    ci.cusID,
    ci.account_id as ci_account_id,
    a.Id as accounts_id,
    a.Username,
    CASE 
        WHEN ci.account_id = a.Id THEN 'MATCH'
        ELSE 'MISMATCH'
    END as status
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id;
```

### Query 3: Find specific problematic customer
Replace `X` with the account ID that's not showing:
```sql
SELECT ci.*, a.Username, a.Email, a.Role
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE ci.account_id = X;
```

## Expected Behavior

When viewing customer information in accounts.php:

1. **If customer_information exists:**
   - Full customer profile should be displayed
   - All fields from the database should be shown
   - Status should reflect the actual Status column value

2. **If customer_information does NOT exist:**
   - A warning message should appear: "This customer has not completed their profile information yet."
   - Basic account information should still be shown (Username, Email, Name, etc.)
   - Status should show "Incomplete Profile"

## Next Steps

1. **Run the test script** for a problematic account ID
2. **Check the error logs** for detailed debugging information
3. **Run the diagnostic SQL queries** to identify data inconsistencies
4. **Report findings** with:
   - The account ID that's not working
   - Output from the test script
   - Relevant error log entries
   - Results from the diagnostic queries

## Files to Monitor

- `d:\xampp\htdocs\Mitsubishi\pages\main\accounts.php` (line 78)
- `d:\xampp\htdocs\Mitsubishi\includes\database\customer_operations.php` (lines 41-103)
- PHP error log file
- Browser console (F12) for JavaScript errors

## Rollback Instructions

If the debugging logs cause issues, you can remove them:

### Remove from accounts.php (line 78):
```php
// Remove this line:
error_log("view_customer: accountId={$accountId}, customer=" . json_encode($customer));
```

### Remove from customer_operations.php:
Remove all `error_log()` calls added to the `getCustomerByAccountId()` method.

