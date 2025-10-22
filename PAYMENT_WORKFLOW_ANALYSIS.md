# Payment Management Workflow Analysis

## Executive Summary
The payment management system has **3 critical issues** that prevent proper functionality:

1. **Customer Name Display Issue**: Wrong table join causing incorrect customer names in agent view
2. **Total Paid/Remaining Balance Always 0.00**: Payment history query doesn't calculate cumulative totals
3. **Payment Schedule Balance Field Missing**: The `balance` field doesn't exist in payment_schedule table

---

## Issue #1: Customer Name Display in Agent View

### Problem
When a customer submits a payment, the agent view displays the wrong customer name.

### Root Cause
In `includes/backend/payment_backend.php` (lines 201-204):
```php
CONCAT(ci.firstname, ' ', ci.lastname) as customer_name
FROM payment_history ph
INNER JOIN orders o ON ph.order_id = o.order_id
LEFT JOIN customer_information ci ON ph.customer_id = ci.cusID  // ❌ WRONG JOIN
```

**The Issue**: 
- `payment_history.customer_id` stores the `cusID` from `customer_information` table
- But the join is using `ci.cusID` which is correct
- However, there's a data inconsistency issue

**Actually, looking deeper**:
- In `includes/backend/order_backend.php` line 841, when submitting payment:
  ```php
  $customer_id,  // This is cusID from customer_information
  ```
- The `customer_id` variable comes from line 504 in `getPaymentHistory()`:
  ```php
  $customer_id = $customer['cusID'];
  ```

**The Real Problem**: The join is actually correct, but there may be:
1. Missing customer_information records
2. NULL firstname/lastname values
3. Data inconsistency between tables

### Solution
Need to add fallback to accounts table:
```php
COALESCE(
    CONCAT(ci.firstname, ' ', ci.lastname),
    CONCAT(a.FirstName, ' ', a.LastName),
    a.Username,
    'Customer #' || ph.customer_id
) as customer_name
FROM payment_history ph
INNER JOIN orders o ON ph.order_id = o.order_id
LEFT JOIN customer_information ci ON ph.customer_id = ci.cusID
LEFT JOIN accounts a ON ci.account_id = a.Id
```

---

## Issue #2: Total Paid and Remaining Balance Display 0.00

### Problem
In the customer view of order amortization, the payment shows as "Confirmed" but:
- Total Paid: ₱0.00
- Remaining Balance: ₱0.00

### Root Cause
In `includes/backend/order_backend.php` (lines 527-537), the `getPaymentHistory()` function:
```php
$sql = "SELECT 
            ph.*,
            CONCAT(processor.FirstName, ' ', processor.LastName) as processed_by_name
        FROM payment_history ph
        LEFT JOIN accounts processor ON ph.processed_by = processor.Id
        WHERE ph.order_id = ?
        ORDER BY ph.payment_date DESC";
```

**The Issue**: This query only returns individual payment records. It does NOT calculate:
- `total_paid` (cumulative sum of all confirmed payments up to this point)
- `remaining_balance` (order total - total_paid)

### Where It's Used
In `pages/order_details.php` (lines 1422-1423):
```javascript
<td>₱${parseFloat(payment.total_paid || 0).toLocaleString(...)}</td>
<td>₱${parseFloat(payment.remaining_balance || 0).toLocaleString(...)}</td>
```

The frontend expects `payment.total_paid` and `payment.remaining_balance` but the query doesn't provide them.

### Solution
Modify the query to calculate running totals:
```php
$sql = "SELECT 
            ph.*,
            CONCAT(processor.FirstName, ' ', processor.LastName) as processed_by_name,
            (SELECT COALESCE(SUM(ph2.amount_paid), 0) 
             FROM payment_history ph2 
             WHERE ph2.order_id = ph.order_id 
             AND ph2.status = 'Confirmed'
             AND ph2.payment_date <= ph.payment_date) as total_paid,
            (SELECT o.total_price - COALESCE(SUM(ph3.amount_paid), 0)
             FROM orders o
             LEFT JOIN payment_history ph3 ON ph3.order_id = o.order_id 
                AND ph3.status = 'Confirmed'
                AND ph3.payment_date <= ph.payment_date
             WHERE o.order_id = ph.order_id) as remaining_balance
        FROM payment_history ph
        LEFT JOIN accounts processor ON ph.processed_by = processor.Id
        WHERE ph.order_id = ?
        ORDER BY ph.payment_date DESC";
```

---

## Issue #3: Payment Schedule Balance Field Missing

### Problem
In `includes/backend/payment_backend.php` (lines 399, 410, 418), the code references:
```php
$remaining_amount >= $nextPayment['balance']
$remaining_amount -= $nextPayment['balance'];
$new_balance = $nextPayment['balance'] - $remaining_amount;
```

But in `includes/database/create_order_tables.sql`, the `payment_schedule` table has:
- `amount_due` (line 50)
- `amount_paid` (line 51)
- **NO `balance` field**

### Root Cause
The code assumes a `balance` field exists, but it doesn't. The balance should be calculated as:
```
balance = amount_due - amount_paid
```

### Solution Option 1: Add balance field to table
```sql
ALTER TABLE payment_schedule ADD COLUMN balance DECIMAL(12, 2) 
GENERATED ALWAYS AS (amount_due - amount_paid) STORED;
```

### Solution Option 2: Calculate balance in code (RECOMMENDED)
Modify `updatePaymentSchedule()` function to calculate balance:
```php
// Instead of: $nextPayment['balance']
// Use: ($nextPayment['amount_due'] - $nextPayment['amount_paid'])

$balance = $nextPayment['amount_due'] - $nextPayment['amount_paid'];
if ($remaining_amount >= $balance) {
    // Full payment
    $updateSql = "UPDATE payment_schedule 
                  SET amount_paid = amount_due, 
                      status = 'Paid',
                      updated_at = NOW()
                  WHERE id = ?";
    $remaining_amount -= $balance;
} else {
    // Partial payment
    $new_amount_paid = $nextPayment['amount_paid'] + $remaining_amount;
    $updateSql = "UPDATE payment_schedule 
                  SET amount_paid = ?, 
                      status = 'Partial',
                      updated_at = NOW()
                  WHERE id = ?";
}
```

---

## Data Flow Analysis

### Customer Submits Payment
1. **File**: `includes/backend/order_backend.php` → `submitPayment()`
2. **Action**: Inserts into `payment_history` with status='Pending'
3. **Data**: 
   - `customer_id` = cusID from customer_information
   - `order_id` = order ID
   - `amount_paid` = payment amount
   - `status` = 'Pending'

### Agent/Admin Views Pending Payments
1. **File**: `includes/backend/payment_backend.php` → `getAgentPayments()`
2. **Query**: Joins payment_history → orders → customer_information
3. **Issue**: Customer name may be NULL if customer_information is incomplete

### Agent/Admin Approves Payment
1. **File**: `includes/backend/payment_backend.php` → `processPayment()`
2. **Action**: 
   - Updates payment_history status to 'Confirmed'
   - Calls `updatePaymentSchedule()`
3. **Issue**: updatePaymentSchedule() references non-existent 'balance' field

### Customer Views Payment History
1. **File**: `includes/backend/order_backend.php` → `getPaymentHistory()`
2. **Query**: Returns payment_history records
3. **Issue**: Doesn't calculate total_paid and remaining_balance

---

## Recommended Fix Priority

### Priority 1: Fix Payment Schedule Balance (CRITICAL)
This breaks payment approval functionality completely.

### Priority 2: Fix Total Paid/Remaining Balance Display (HIGH)
This causes customer confusion and support issues.

### Priority 3: Fix Customer Name Display (MEDIUM)
This causes agent confusion but doesn't break functionality.

---

## Files That Need Changes

1. `includes/backend/payment_backend.php`
   - Fix `updatePaymentSchedule()` to calculate balance instead of using field
   - Fix `applyRemainingAmount()` to calculate balance
   - Fix `getAgentPayments()` to add fallback for customer name

2. `includes/backend/order_backend.php`
   - Fix `getPaymentHistory()` to calculate total_paid and remaining_balance

3. `includes/api/payment_approval_api.php`
   - Fix `approvePayment()` to calculate balance (lines 401, 412)
   - Fix customer name joins to add fallback

---

## Testing Checklist

- [ ] Customer can submit payment
- [ ] Payment appears in agent pending list with correct customer name
- [ ] Agent can approve payment without errors
- [ ] Payment schedule updates correctly after approval
- [ ] Customer sees correct total_paid in payment history
- [ ] Customer sees correct remaining_balance in payment history
- [ ] Amortization table shows correct values

