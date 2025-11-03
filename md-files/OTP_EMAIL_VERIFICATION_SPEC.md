# OTP Email Verification Feature Specification

**Project:** Mitsubishi Motors Customer Management System  
**Feature:** Email OTP Verification for New Customer Accounts  
**Date:** 2025-10-21  
**Status:** Specification Document

---

## 1. Executive Summary

This specification outlines the implementation of an OTP (One-Time Password) email verification system to validate customer email addresses during account registration. This feature will enhance security and ensure that customers provide valid, accessible email addresses.

---

## 2. Current System Analysis

### 2.1 Current Registration Workflow

**File:** `pages/create_account.php`

**Current Flow:**
1. Customer fills registration form (name, email, password, confirm password)
2. System validates:
   - Passwords match
   - Email/username doesn't already exist
3. Account is created in `accounts` table with:
   - Username (derived from email)
   - Email
   - PasswordHash
   - Role = 'Customer'
   - FirstName, LastName
   - CreatedAt, UpdatedAt
4. Notifications sent to Admin and SalesAgent
5. User is **automatically logged in** (session created)
6. User redirected to `verification.php` (customer information form)

**Key Observations:**
- No email verification currently exists
- Users can register with any email (even invalid ones)
- Immediate login after registration
- No email confirmation required

### 2.2 Current Email Infrastructure

**Configuration:** `config/email_config.php`
- **Provider:** Gmail SMTP (migrated from Mailgun)
- **Library:** PHPMailer (no Composer required)
- **Settings loaded from:** `.env` file
- **SMTP Details:**
  - Host: smtp.gmail.com
  - Port: 587 (TLS) or 465 (SSL)
  - Authentication: Required
  - Limits: 500 emails/day (free Gmail), 2000/day (Google Workspace)

**Email Service:** `includes/backend/GmailMailer.php`
- Namespace: `\Mitsubishi\Backend\GmailMailer`
- Method: `sendEmail($to, $subject, $body, $options = [])`
- Returns: `['success' => bool, 'message' => string, 'message_id' => string|null, 'error' => string|null]`
- Features:
  - HTML email support
  - Branded email templates
  - CC/BCC support
  - Priority settings
  - Automatic template wrapping

**Email API:** `api/send_email_api.php`
- Function: `sendEmail($email_content)`
- Wrapper around GmailMailer
- Logs emails to `email_logs` table

### 2.3 Database Schema

**Accounts Table** (inferred from code):
```sql
CREATE TABLE accounts (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'SalesAgent', 'Customer') NOT NULL,
    FirstName VARCHAR(255),
    LastName VARCHAR(255),
    ProfileImage VARCHAR(255),
    DateOfBirth DATE,
    Status VARCHAR(50),  -- Used for Customer accounts
    IsDisabled TINYINT DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LastLoginAt TIMESTAMP NULL
);
```

**Email Logs Table** (from `api/send_email_api.php`):
```sql
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    email_type VARCHAR(100) DEFAULT 'general',
    priority VARCHAR(20) DEFAULT 'normal',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    delivery_status ENUM('delivered', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2.4 Related Workflows

**Login Flow** (`pages/login.php`):
- Checks if customer has filled `customer_information`
- If yes → redirect to `customer.php`
- If no → redirect to `verification.php`
- **Auto-create account feature:** Creates account if email not found (security concern)

**Verification Page** (`pages/verification.php`):
- Customer information form (personal details, ID upload)
- Requires customer to be logged in
- Checks if already approved
- Inserts/updates `customer_information` table
- Sets Status = 'Pending'

**Password Reset** (`pages/forgot_password.php`, `pages/reset_password.php`):
- Simple email check (no OTP/token)
- Direct password reset without email verification
- Security vulnerability

---

## 3. Proposed OTP Verification System

### 3.1 Feature Requirements

**Functional Requirements:**
1. Generate 6-digit OTP when customer creates account
2. Send OTP to customer's email address
3. Store OTP with expiration time (10 minutes)
4. Prevent login until email is verified
5. Allow OTP resend with rate limiting (max 3 times, 60-second cooldown)
6. Validate OTP on submission
7. Mark account as email-verified upon successful OTP entry
8. Provide clear error messages for invalid/expired OTPs

**Non-Functional Requirements:**
1. OTP must be cryptographically random
2. OTP must expire after 10 minutes
3. OTP should be single-use (invalidated after successful verification)
4. Email delivery should be asynchronous (non-blocking)
5. System should handle email delivery failures gracefully
6. Rate limiting to prevent abuse

### 3.2 Database Changes

**New Table: `email_verifications`**
```sql
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,  -- Hashed version for security
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    is_used TINYINT DEFAULT 0,
    resend_count INT DEFAULT 0,
    last_resend_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (account_id) REFERENCES accounts(Id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_email (email),
    INDEX idx_otp_hash (otp_hash),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Modify Accounts Table:**
```sql
ALTER TABLE accounts 
ADD COLUMN email_verified TINYINT DEFAULT 0 AFTER Email,
ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email_verified;
```

### 3.3 Modified Registration Workflow

**Updated Flow:**
1. Customer submits registration form
2. System validates input (passwords match, email format, etc.)
3. Check if email/username already exists
4. **NEW:** Create account with `email_verified = 0`
5. **NEW:** Generate 6-digit OTP
6. **NEW:** Store OTP in `email_verifications` table (hashed)
7. **NEW:** Send OTP email to customer
8. **NEW:** Create session but mark as "pending verification"
9. **NEW:** Redirect to OTP verification page
10. **NEW:** Customer enters OTP
11. **NEW:** Validate OTP (check expiration, attempts, hash)
12. **NEW:** Mark email as verified
13. **NEW:** Allow full login
14. Send notifications to Admin/SalesAgent
15. Redirect to `verification.php` (customer info form)

### 3.4 New Files to Create

**1. Database Migration:**
- `includes/database/create_email_verification_table.sql`

**2. OTP Service:**
- `includes/services/OTPService.php`
  - `generateOTP()`: Generate 6-digit code
  - `sendOTP($accountId, $email)`: Send OTP email
  - `verifyOTP($accountId, $otpCode)`: Validate OTP
  - `resendOTP($accountId)`: Resend with rate limiting
  - `cleanupExpiredOTPs()`: Cleanup job

**3. OTP Verification Page:**
- `pages/verify_otp.php`: OTP entry form

**4. Email Template:**
- `includes/email_templates/otp_verification.php`: Branded OTP email

### 3.5 Modified Files

**1. `pages/create_account.php`**
- After account creation, generate and send OTP
- Redirect to `verify_otp.php` instead of `verification.php`
- Store account_id in session as "pending_verification"

**2. `pages/login.php`**
- Check `email_verified` status
- If not verified, redirect to `verify_otp.php`
- Prevent access to dashboard until verified

**3. `pages/verification.php`**
- Add check for email verification
- Redirect to OTP page if not verified

**4. `pages/register_submit.php`**
- Same changes as `create_account.php`

---

## 4. Technical Implementation Details

### 4.1 OTP Generation Algorithm

```php
function generateOTP() {
    // Use cryptographically secure random
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function hashOTP($otp) {
    // Use password_hash for secure storage
    return password_hash($otp, PASSWORD_DEFAULT);
}
```

### 4.2 OTP Email Template

**Subject:** "Verify Your Email - Mitsubishi Motors"

**Content:**
- Welcome message
- 6-digit OTP code (large, prominent)
- Expiration time (10 minutes)
- Security warning (don't share OTP)
- Resend link
- Support contact

### 4.3 Rate Limiting Strategy

**Resend Limits:**
- Max 3 resends per account
- 60-second cooldown between resends
- Track in `email_verifications.resend_count`

**Verification Attempts:**
- Max 5 attempts per OTP
- After 5 failed attempts, require new OTP
- Track in `email_verifications.attempts`

### 4.4 Security Considerations

1. **OTP Storage:** Store hashed OTP, not plaintext
2. **Expiration:** 10-minute validity window
3. **Single-Use:** Mark as used after successful verification
4. **Rate Limiting:** Prevent brute force attacks
5. **IP Tracking:** Log IP address for audit trail
6. **Session Security:** Use separate session flag for verification status
7. **CSRF Protection:** Add CSRF tokens to forms

### 4.5 Error Handling

**Scenarios:**
1. Email delivery failure → Show error, allow retry
2. Expired OTP → Clear message, offer resend
3. Invalid OTP → Increment attempts, show remaining
4. Max attempts reached → Require new OTP generation
5. Max resends reached → Contact support message

---

## 5. User Experience Flow

### 5.1 Happy Path

1. User fills registration form
2. Sees success message: "Account created! Check your email for verification code"
3. Receives email with OTP within seconds
4. Enters 6-digit OTP on verification page
5. Sees success: "Email verified! Redirecting..."
6. Proceeds to customer information form

### 5.2 Resend Flow

1. User doesn't receive email
2. Clicks "Resend OTP" button
3. Waits 60 seconds (countdown timer)
4. Receives new OTP
5. Enters code successfully

### 5.3 Error Recovery

1. User enters wrong OTP
2. Sees: "Invalid code. 4 attempts remaining"
3. Tries again with correct code
4. Verification successful

---

## 6. Testing Requirements

### 6.1 Unit Tests
- OTP generation (randomness, format)
- OTP hashing and verification
- Expiration logic
- Rate limiting

### 6.2 Integration Tests
- Email sending
- Database operations
- Session management
- Workflow transitions

### 6.3 Manual Testing Scenarios
1. Successful registration and verification
2. Expired OTP handling
3. Invalid OTP handling
4. Resend functionality
5. Rate limiting enforcement
6. Email delivery failure
7. Concurrent verification attempts
8. Browser back button behavior

---

## 7. Deployment Plan

### 7.1 Phase 1: Database Setup
1. Create `email_verifications` table
2. Alter `accounts` table (add verification columns)
3. Test on development database

### 7.2 Phase 2: Backend Implementation
1. Create `OTPService.php`
2. Create email template
3. Unit test OTP service

### 7.3 Phase 3: Frontend Implementation
1. Create `verify_otp.php` page
2. Modify `create_account.php`
3. Modify `login.php`
4. Add JavaScript for countdown timer

### 7.4 Phase 4: Testing
1. End-to-end testing
2. Email delivery testing
3. Security testing
4. Performance testing

### 7.5 Phase 5: Production Deployment
1. Database migration
2. Code deployment
3. Monitor email logs
4. Monitor error logs

---

## 8. Maintenance & Monitoring

### 8.1 Cleanup Jobs
- Cron job to delete expired OTPs (older than 24 hours)
- Archive old verification records

### 8.2 Monitoring Metrics
- OTP generation rate
- Email delivery success rate
- Verification success rate
- Average time to verify
- Failed verification attempts

### 8.3 Alerts
- High email delivery failure rate
- Unusual OTP request patterns
- High failed verification rate

---

## 9. Future Enhancements

1. **SMS OTP:** Alternative to email OTP
2. **2FA:** Optional two-factor authentication
3. **Magic Links:** Passwordless login via email
4. **Social Login:** OAuth integration
5. **Email Change Verification:** Re-verify when email changes
6. **Account Recovery:** OTP-based account recovery

---

## 10. Dependencies

### 10.1 Existing Systems
- Gmail SMTP (configured in `.env`)
- PHPMailer library
- MySQL database
- PHP session management

### 10.2 Configuration Required
- Ensure Gmail SMTP credentials are valid
- Verify email sending limits
- Configure timezone (Asia/Manila)

---

## 11. Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Email delivery failure | High | Implement retry logic, show clear error messages |
| Gmail rate limits | Medium | Monitor usage, consider backup email provider |
| User loses access to email | Medium | Provide admin override mechanism |
| OTP brute force | High | Implement rate limiting, account lockout |
| Session hijacking | High | Use secure session settings, HTTPS only |

---

## 12. Success Criteria

1. ✅ 95%+ email delivery success rate
2. ✅ Average verification time < 2 minutes
3. ✅ < 5% support requests related to OTP
4. ✅ Zero security incidents related to verification
5. ✅ 100% of new accounts verified before access

---

**End of Specification Document**

