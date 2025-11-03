# OTP Email Verification - File Structure & Implementation Reference

**Last Updated:** 2025-10-22  
**Status:** ✅ Implemented  
**Version:** 1.0

---

## 📋 Overview

This document provides a comprehensive reference for the OTP (One-Time Password) email verification system implemented in the Mitsubishi Motors Customer Management System. The system verifies customer email addresses during account registration using a secure, time-limited OTP sent via email.

---

## 📁 File Structure

### **1. Database Schema**
```
includes/database/
└── create_email_verification_table.sql
```

**Purpose:** Database migration script  
**Creates:**
- `email_verifications` table for storing OTP codes
- `email_verified` column in `accounts` table (TINYINT, default 0)
- `email_verified_at` column in `accounts` table (TIMESTAMP NULL)
- Grandfather clause to mark existing accounts as verified

**Key Features:**
- Conditional column addition (won't fail if columns exist)
- Indexes for performance optimization
- InnoDB engine with utf8mb4 charset

---

### **2. Core Service Layer**
```
includes/services/
└── OTPService.php
```

**Namespace:** `\Mitsubishi\Services\OTPService`

**Class:** `OTPService`

**Dependencies:**
- PDO database connection
- `\Mitsubishi\Backend\GmailMailer` for email sending
- `includes/email_templates/otp_verification.php` for email template

**Public Methods:**

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `__construct()` | `$pdo = null` | - | Initialize service with database connection |
| `generateOTP()` | - | `string` | Generate cryptographically secure 6-digit OTP |
| `sendOTP()` | `$accountId, $email` | `array` | Generate, store, and send OTP via email |
| `verifyOTP()` | `$accountId, $otpCode` | `array` | Verify OTP code and mark email as verified |
| `resendOTP()` | `$accountId, $email` | `array` | Resend OTP with rate limiting |
| `cleanupExpiredOTPs()` | - | `int` | Delete expired OTPs (for cron jobs) |
| `isEmailVerified()` | `$accountId` | `bool` | Check if account email is verified |

**Constants:**
```php
const OTP_LENGTH = 6;                    // 6-digit OTP
const OTP_EXPIRY_MINUTES = 10;           // 10 minutes expiration
const MAX_ATTEMPTS = 5;                  // Max verification attempts
const MAX_RESENDS = 3;                   // Max resend requests
const RESEND_COOLDOWN_SECONDS = 60;      // 60 seconds between resends
```

**Return Format:**
```php
[
    'success' => bool,
    'message' => string,
    'otp_id' => int|null  // Only for sendOTP()
]
```

---

### **3. Email Template**
```
includes/email_templates/
└── otp_verification.php
```

**Function:** `getOTPEmailTemplate($otp)`

**Parameters:**
- `$otp` (string) - 6-digit OTP code

**Returns:** HTML email content

**Features:**
- Mitsubishi Motors branding
- Large, prominent OTP display
- Expiration notice (10 minutes)
- Security warnings
- Mobile-responsive design
- Professional styling with gradient backgrounds

**Email Subject:** "Verify Your Email - Mitsubishi Motors"

---

### **4. User Interface**
```
pages/
└── verify_otp.php
```

**Purpose:** OTP verification page

**Session Requirements:**
- `$_SESSION['pending_verification_user_id']` - Account ID awaiting verification
- `$_SESSION['pending_verification_email']` - Email address to verify

**Features:**
- 6-digit OTP input field with auto-formatting
- Real-time validation (digits only)
- Resend button with 60-second countdown timer
- Error/success message display
- Attempt tracking
- Session-based security

**Form Actions:**
1. **Verify OTP** (`POST: verify_otp`)
   - Validates OTP format
   - Calls `OTPService::verifyOTP()`
   - On success: Creates full session, redirects to `verification.php`
   - On failure: Shows error with remaining attempts

2. **Resend OTP** (`POST: resend_otp`)
   - Calls `OTPService::resendOTP()`
   - Starts 60-second countdown timer
   - Shows success/error message

**Redirects:**
- Success → `verification.php` (customer information form)
- No session → `login.php`
- Already logged in → `customer.php`

---

### **5. Modified Registration Flow**
```
pages/
├── create_account.php      (Primary registration)
└── register_submit.php     (Alternative registration)
```

#### **create_account.php**
**Changes Made:**
- Line 33: Added `email_verified = 0` to INSERT statement
- Lines 40-56: Replaced auto-login with OTP flow
  - Generates OTP via `OTPService::sendOTP()`
  - Sets pending verification session
  - Redirects to `verify_otp.php`

**Old Flow:**
```
Register → Create Account → Auto-login → verification.php
```

**New Flow:**
```
Register → Create Account → Send OTP → verify_otp.php → Verify → verification.php
```

#### **register_submit.php**
**Changes Made:**
- Line 26: Added `email_verified` column (0 for customers, 1 for others)
- Lines 32-47: Added OTP flow for customer accounts
- Non-customer accounts bypass OTP verification

---

### **6. Modified Login Flow**
```
pages/
└── login.php
```

**Changes Made:**
- Lines 47-58: Added email verification check after password verification
- Checks `email_verified` column for customers
- If unverified:
  - Sets pending verification session
  - Resends OTP automatically
  - Redirects to `verify_otp.php`

**Flow:**
```
Login → Password OK → Check email_verified
  ├─ Verified → Continue normal login
  └─ Unverified → Resend OTP → verify_otp.php
```

---

### **7. Modified Customer Information Page**
```
pages/
└── verification.php
```

**Changes Made:**
- Lines 11-24: Added email verification check
- Prevents access to customer information form if email not verified
- Redirects unverified users to `verify_otp.php`

**Protection Flow:**
```
verification.php → Check email_verified
  ├─ Verified → Show customer info form
  └─ Unverified → Redirect to verify_otp.php
```

---

## 🗄️ Database Schema

### **Table: `email_verifications`**

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT | Primary key |
| `account_id` | INT | Reference to accounts.Id |
| `email` | VARCHAR(255) | Email address |
| `otp_code` | VARCHAR(6) | Plain OTP (for email sending) |
| `otp_hash` | VARCHAR(255) | Hashed OTP (for verification) |
| `created_at` | TIMESTAMP | OTP creation time |
| `expires_at` | TIMESTAMP | OTP expiration time (created_at + 10 min) |
| `verified_at` | TIMESTAMP NULL | Verification timestamp |
| `attempts` | INT | Number of verification attempts (max 5) |
| `max_attempts` | INT | Maximum allowed attempts (default 5) |
| `is_used` | TINYINT | 1 if OTP has been used/verified |
| `resend_count` | INT | Number of times OTP was resent (max 3) |
| `last_resend_at` | TIMESTAMP NULL | Last resend timestamp |
| `ip_address` | VARCHAR(45) | Client IP address |
| `user_agent` | TEXT | Client user agent |

**Indexes:**
- `idx_account_id` on `account_id`
- `idx_email` on `email`
- `idx_otp_hash` on `otp_hash`
- `idx_expires_at` on `expires_at`

### **Table: `accounts` (Modified)**

**New Columns:**
| Column | Type | Description |
|--------|------|-------------|
| `email_verified` | TINYINT DEFAULT 0 | 0 = unverified, 1 = verified |
| `email_verified_at` | TIMESTAMP NULL | Timestamp of email verification |

---

## 🔄 Complete User Flow

### **New User Registration**
```
1. User fills registration form (create_account.php)
2. System creates account with email_verified = 0
3. OTPService generates 6-digit OTP
4. OTP stored in email_verifications table (hashed)
5. Email sent with OTP code
6. User redirected to verify_otp.php
7. User enters OTP code
8. System verifies OTP:
   ├─ Valid → Mark email_verified = 1, create session, redirect to verification.php
   └─ Invalid → Show error, decrement attempts
9. User completes customer information form
10. User gains full access to system
```

### **Returning User (Unverified)**
```
1. User attempts login
2. Password verified successfully
3. System checks email_verified status
4. If unverified:
   ├─ Generate new OTP
   ├─ Send email
   └─ Redirect to verify_otp.php
5. User verifies OTP
6. Login completes
```

### **OTP Resend Flow**
```
1. User clicks "Resend Code" button
2. System checks:
   ├─ Resend count < 3 → Continue
   └─ Resend count >= 3 → Show error
3. System checks cooldown:
   ├─ Last resend > 60 seconds ago → Continue
   └─ Last resend < 60 seconds ago → Show countdown
4. Generate new OTP
5. Invalidate old OTPs
6. Send new email
7. Increment resend_count
8. Start 60-second countdown timer
```

---

## 🔒 Security Features

### **OTP Generation**
- Uses `random_int(100000, 999999)` for cryptographic security
- Fallback to `mt_rand()` if `random_int()` unavailable
- 6-digit numeric code (1 in 1,000,000 chance)

### **OTP Storage**
- Plain OTP stored temporarily for email sending
- Hashed using `password_hash($otp, PASSWORD_DEFAULT)` (bcrypt)
- Verification uses `password_verify()` for timing-attack resistance

### **Rate Limiting**
- **Verification Attempts:** Max 5 per OTP
- **Resend Requests:** Max 3 per account
- **Resend Cooldown:** 60 seconds between requests
- **Expiration:** 10 minutes from generation

### **Session Security**
- Pending verification uses separate session variables
- Full session only created after email verification
- Session variables cleared after successful verification

### **Audit Trail**
- IP address logging
- User agent logging
- Timestamp tracking (created, verified, resent)
- Attempt counting

---

## 🛠️ Configuration

### **Email Settings**
Located in `.env` file:
```env
GMAIL_EMAIL=your-email@gmail.com
GMAIL_PASSWORD=your-app-password
GMAIL_FROM_NAME=Mitsubishi Motors
```

### **OTP Settings**
Located in `includes/services/OTPService.php`:
```php
const OTP_LENGTH = 6;                    // Change OTP length
const OTP_EXPIRY_MINUTES = 10;           // Change expiration time
const MAX_ATTEMPTS = 5;                  // Change max attempts
const MAX_RESENDS = 3;                   // Change max resends
const RESEND_COOLDOWN_SECONDS = 60;      // Change cooldown period
```

---

## 🧪 Testing Checklist

- [ ] New user registration sends OTP email
- [ ] OTP email contains correct 6-digit code
- [ ] Valid OTP verifies successfully
- [ ] Invalid OTP shows error message
- [ ] Expired OTP (>10 min) shows expiration error
- [ ] Max attempts (5) blocks further verification
- [ ] Resend generates new OTP
- [ ] Resend cooldown (60s) enforces waiting period
- [ ] Max resends (3) blocks further resend requests
- [ ] Unverified user login redirects to OTP page
- [ ] Verified user login works normally
- [ ] Existing accounts marked as verified (grandfather clause)
- [ ] Email verification required before accessing customer info
- [ ] Session management works correctly
- [ ] Database records created properly

---

## 📊 Monitoring & Maintenance

### **Database Cleanup**
Run periodically to remove old OTP records:
```php
$otpService = new \Mitsubishi\Services\OTPService($connect);
$deletedCount = $otpService->cleanupExpiredOTPs();
```

Recommended: Daily cron job to delete OTPs older than 24 hours

### **Email Delivery Monitoring**
Check `email_logs` table for delivery status:
```sql
SELECT * FROM email_logs 
WHERE subject LIKE '%Verify Your Email%' 
ORDER BY created_at DESC 
LIMIT 100;
```

### **Verification Success Rate**
```sql
SELECT 
    COUNT(*) as total_otps,
    SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN attempts >= max_attempts THEN 1 ELSE 0 END) as max_attempts_reached,
    SUM(CASE WHEN expires_at < NOW() AND verified_at IS NULL THEN 1 ELSE 0 END) as expired
FROM email_verifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

---

## 🚨 Troubleshooting

### **OTP Email Not Received**
1. Check Gmail SMTP credentials in `.env`
2. Check `email_logs` table for errors
3. Verify Gmail daily sending limit (500/day free, 2000/day Workspace)
4. Check spam/junk folder
5. Verify email address is valid

### **Foreign Key Error During Migration**
- SQL file has been updated to remove foreign key constraint
- Relationship maintained through application logic
- Re-run migration with updated SQL file

### **OTP Verification Fails**
1. Check OTP hasn't expired (10 minutes)
2. Verify attempts < 5
3. Check `email_verifications` table for record
4. Ensure OTP code matches exactly (6 digits)

### **Resend Button Disabled**
1. Check resend_count < 3
2. Verify 60 seconds have passed since last resend
3. Check JavaScript countdown timer

---

## 📝 Future Enhancements

### **Potential Improvements**
- [ ] SMS OTP as alternative verification method
- [ ] Admin panel to manually verify accounts
- [ ] Email verification reminder notifications
- [ ] Multi-language support for OTP emails
- [ ] QR code verification option
- [ ] Backup verification codes
- [ ] Account recovery via OTP
- [ ] Two-factor authentication (2FA) integration

---

## 📞 Support

For issues or questions regarding the OTP email verification system:
- Check this documentation first
- Review error logs in `includes/logs/`
- Check database tables: `email_verifications`, `email_logs`
- Contact development team

---

**Document Version:** 1.0  
**Implementation Date:** 2025-10-22  
**Maintained By:** Mitsubishi Motors Development Team

