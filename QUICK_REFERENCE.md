# Quick Reference - Customer Info Not Showing

## 🚀 Quick Start (3 Steps)

### 1️⃣ Run Test Script
```
http://localhost/Mitsubishi/test_customer_retrieval.php?account_id=X
```
Replace `X` with the account ID that's not showing information.

### 2️⃣ Check Browser Console
1. Open accounts.php
2. Press `F12`
3. Click Console tab
4. Click "View" on the problematic customer
5. Look for debug output

### 3️⃣ Check Error Logs
**Windows/XAMPP:**
```
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

## 🔍 What to Look For

### In Test Script Output:
- ✅ Green boxes = Good
- ⚠️ Yellow boxes = Warning (might be normal)
- ❌ Red boxes = Problem found

### In Browser Console:
```javascript
Customer data received: {success: true, data: {...}}
Customer data keys: ["cusID", "account_id", "firstname", ...]
Sample fields: {firstname: "John", lastname: "Doe", ...}
```

### In Error Logs:
```
getCustomerByAccountId: Starting query for account_id=X
getCustomerByAccountId: First query SUCCESS. Keys: cusID, account_id, ...
```

## 🐛 Common Problems

| Problem | Quick Fix |
|---------|-----------|
| **No customer_information record** | Normal - customer hasn't completed profile |
| **account_id is NULL** | Run: `UPDATE customer_information SET account_id = X WHERE cusID = Y` |
| **account_id mismatch** | Verify account_id matches accounts.Id |
| **Fields show "N/A"** | Check field name case in browser console |

## 💉 Quick SQL Fixes

### Find customers without customer_information:
```sql
SELECT a.Id, a.Username, a.Email
FROM accounts a
LEFT JOIN customer_information ci ON a.Id = ci.account_id
WHERE a.Role = 'Customer' AND ci.cusID IS NULL;
```

### Fix NULL account_id (if you know the correct mapping):
```sql
-- First, verify the mapping
SELECT ci.cusID, ci.firstname, ci.lastname, a.Id, a.Username
FROM customer_information ci
CROSS JOIN accounts a
WHERE ci.account_id IS NULL 
  AND a.Role = 'Customer'
  AND CONCAT(a.FirstName, ' ', a.LastName) LIKE CONCAT('%', ci.firstname, '%');

-- Then update (replace X and Y with actual values)
UPDATE customer_information 
SET account_id = X 
WHERE cusID = Y;
```

### Check specific customer (replace X):
```sql
SELECT ci.*, a.Username, a.Email, a.Role
FROM customer_information ci
LEFT JOIN accounts a ON ci.account_id = a.Id
WHERE ci.account_id = X;
```

## 📁 Files Modified

1. `includes/database/customer_operations.php` - Added logging
2. `pages/main/accounts.php` - Added logging (server & client)
3. `test_customer_retrieval.php` - NEW diagnostic tool

## 🔄 Quick Rollback

If you need to remove debugging:

```bash
# Remove these lines from pages/main/accounts.php:
# Line 78: error_log("view_customer: ...")
# Lines 1172-1182: console.log statements

# Remove error_log() calls from:
# includes/database/customer_operations.php (getCustomerByAccountId method)

# Delete test files:
# test_customer_retrieval.php
# CUSTOMER_INFO_INVESTIGATION.md
# INVESTIGATION_SUMMARY.md
# QUICK_REFERENCE.md
```

## 📞 Need Help?

Provide these 4 things:
1. Account ID that's not working
2. Screenshot of test script output
3. Browser console logs (F12)
4. PHP error log entries

## 🎯 Expected Behavior

### When customer_information EXISTS:
- Full profile displayed
- All fields populated
- Status shows actual value (Pending/Approved/Rejected)

### When customer_information DOES NOT EXIST:
- Warning message: "This customer has not completed their profile information yet."
- Basic account info shown (Username, Email, Name)
- Status shows "Incomplete Profile"

## ⚡ Pro Tips

1. **Always check test script first** - It shows everything in one place
2. **Browser console is your friend** - Shows exactly what data the frontend receives
3. **Error logs tell the truth** - Server-side issues show up here
4. **account_id is critical** - If this is wrong/NULL, nothing works

## 🔗 Related Files

- `pages/main/accounts.php` - Main accounts management page
- `includes/database/customer_operations.php` - Customer data operations
- `includes/database/accounts_operations.php` - Account data operations
- `test_customer_retrieval.php` - Diagnostic tool

---

**Last Updated:** 2025-10-24
**Version:** 1.0

