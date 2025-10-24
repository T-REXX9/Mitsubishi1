# Customer Information Retrieval - Investigation Summary

## 🔍 Problem Statement
Customer information is not displaying in `accounts.php` for some customers, even though the data exists in the `customer_information` table in the database.

## ✅ Changes Made

### 1. Enhanced Server-Side Logging
**File:** `includes/database/customer_operations.php`
**Method:** `getCustomerByAccountId()`
**Lines:** 41-103

Added comprehensive logging to track:
- Query execution start
- Successful query results with all field names
- Sample data values (firstname, lastname, account_id)
- Fallback query execution when JOIN fails
- Detailed error messages and stack traces

### 2. Added Request Logging
**File:** `pages/main/accounts.php`
**Action:** `view_customer`
**Line:** 78

Added logging to track what data is being sent to the client:
```php
error_log("view_customer: accountId={$accountId}, customer=" . json_encode($customer));
```

### 3. Enhanced Client-Side Debugging
**File:** `pages/main/accounts.php`
**Function:** `viewCustomerInfo()`
**Lines:** 1169-1207

Added console logging to track:
- Complete data object received from server
- All field names (keys) in the data
- Sample field values (firstname, lastname, account_id, Status)

### 4. Created Diagnostic Test Script
**File:** `test_customer_retrieval.php`

A comprehensive diagnostic tool that:
- Tests account existence in `accounts` table
- Tests customer_information record existence
- Tests the `getCustomerByAccountId()` method
- Validates all expected fields
- Shows the exact SQL query being executed
- Provides troubleshooting guidance

## 🧪 How to Diagnose the Issue

### Step 1: Use the Test Script
1. Open browser: `http://localhost/Mitsubishi/test_customer_retrieval.php`
2. Enter the account ID of a customer that's not showing information
3. Click "Test Retrieval"
4. Review the detailed diagnostic output

### Step 2: Check Browser Console
1. Open the accounts.php page
2. Press F12 to open Developer Tools
3. Go to the Console tab
4. Click "View" on a customer that's not showing information
5. Look for the debug logs:
   ```
   Customer data received: {success: true, data: {...}}
   Customer data keys: ["cusID", "account_id", "firstname", ...]
   Sample fields: {firstname: "John", lastname: "Doe", ...}
   ```

### Step 3: Check PHP Error Logs
**Location:** 
- `C:\xampp\apache\logs\error.log` (Apache errors)
- `C:\xampp\php\logs\php_error_log` (PHP errors)
- Check `php.ini` for `error_log` setting

**Look for:**
```
getCustomerByAccountId: Starting query for account_id=X
getCustomerByAccountId: First query SUCCESS. Keys: cusID, account_id, ...
getCustomerByAccountId: Sample data - firstname: John, lastname: Doe, ...
view_customer: accountId=X, customer={"cusID":1,"account_id":5,...}
```

## 🔎 Common Issues and Solutions

### Issue 1: No customer_information Record
**Symptom:** Test script shows "No customer_information record found"
**Cause:** Customer hasn't completed their profile
**Expected Behavior:** Should show "Incomplete Profile" message with basic account info
**Solution:** This is normal - the code handles this case

### Issue 2: account_id Mismatch
**Symptom:** customer_information exists but JOIN returns no results
**Cause:** The `account_id` in customer_information doesn't match `Id` in accounts table
**Diagnostic Query:**
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
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE ci.account_id IS NOT NULL;
```
**Solution:** Update the account_id in customer_information to match the correct account Id

### Issue 3: NULL account_id
**Symptom:** customer_information record exists but account_id is NULL
**Diagnostic Query:**
```sql
SELECT * FROM customer_information WHERE account_id IS NULL;
```
**Solution:** Update the records to have the correct account_id

### Issue 4: Field Name Case Mismatch
**Symptom:** Data is retrieved but fields show as "N/A" in the modal
**Cause:** JavaScript expects lowercase field names but database returns different case
**Check:** Look at browser console logs for "Customer data keys"
**Solution:** Ensure database columns match expected names (all lowercase except 'Status')

### Issue 5: Data Type Issues
**Symptom:** Query fails or returns unexpected results
**Cause:** account_id stored as string instead of integer
**Check:** Look for error logs mentioning type conversion
**Solution:** Ensure account_id is stored as INT in database

## 📊 Expected Database Schema

Based on your schema, the `customer_information` table should have:

| Column | Type | Nullable | Expected in Code |
|--------|------|----------|------------------|
| cusID | int(11) | No | ✓ |
| account_id | int(11) | Yes | ✓ (critical for JOIN) |
| agent_id | int(11) | Yes | ✓ |
| lastname | varchar(100) | Yes | ✓ (lowercase) |
| firstname | varchar(100) | Yes | ✓ (lowercase) |
| middlename | varchar(100) | Yes | ✓ (lowercase) |
| suffix | varchar(20) | Yes | ✓ (lowercase) |
| nationality | varchar(100) | Yes | ✓ (lowercase) |
| birthday | date | Yes | ✓ |
| age | int(11) | Yes | ✓ |
| gender | varchar(50) | Yes | ✓ (lowercase) |
| civil_status | varchar(50) | Yes | ✓ (lowercase) |
| mobile_number | varchar(20) | Yes | ✓ (lowercase) |
| employment_status | varchar(100) | Yes | ✓ (lowercase) |
| company_name | varchar(255) | Yes | ✓ (lowercase) |
| position | varchar(100) | Yes | ✓ (lowercase) |
| monthly_income | decimal(12,2) | Yes | ✓ (lowercase) |
| valid_id_type | varchar(100) | Yes | ✓ (lowercase) |
| Status | enum('Pending','Approved','Rejected') | Yes | ✓ (capital S) |
| customer_type | enum('Handled','Walk In') | No | ✓ (lowercase) |

## 🛠️ Quick Diagnostic SQL Queries

### Find customers without customer_information
```sql
SELECT a.Id, a.Username, a.Email, a.FirstName, a.LastName, a.Role
FROM accounts a
LEFT JOIN customer_information ci ON a.Id = ci.account_id
WHERE a.Role = 'Customer' AND ci.cusID IS NULL;
```

### Check specific customer (replace X with account ID)
```sql
SELECT ci.*, a.Username, a.Email, a.Role
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE ci.account_id = X;
```

### Verify account_id integrity
```sql
SELECT 
    COUNT(*) as total_records,
    COUNT(account_id) as non_null_account_ids,
    COUNT(DISTINCT account_id) as unique_account_ids
FROM customer_information;
```

### Find orphaned customer_information records
```sql
SELECT ci.*
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE a.Id IS NULL AND ci.account_id IS NOT NULL;
```

## 📝 Next Steps

1. **Run the test script** (`test_customer_retrieval.php`) with a problematic account ID
2. **Check browser console** (F12) when viewing customer info
3. **Check PHP error logs** for detailed server-side information
4. **Run diagnostic SQL queries** to identify data issues
5. **Report findings** with:
   - Account ID that's not working
   - Output from test script
   - Browser console logs
   - PHP error log entries
   - Results from SQL queries

## 🔄 Rollback Instructions

If you need to remove the debugging code:

### Remove from `pages/main/accounts.php`:
1. Line 78: Remove the `error_log()` call
2. Lines 1172-1182: Remove the console.log statements

### Remove from `includes/database/customer_operations.php`:
Remove all `error_log()` calls from the `getCustomerByAccountId()` method (lines 46, 59-62, 66, 74-75, 80, 85, 91-92, 96-97)

### Delete test files:
- `test_customer_retrieval.php`
- `CUSTOMER_INFO_INVESTIGATION.md`
- `INVESTIGATION_SUMMARY.md`

## 📞 Support

If the issue persists after following these steps, please provide:
1. Screenshot of test script output
2. Browser console logs
3. Relevant PHP error log entries
4. Results from the diagnostic SQL queries
5. The specific account ID that's experiencing the issue

