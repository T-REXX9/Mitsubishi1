# Forgot Password Security Enhancement - Implementation Specification

**Document Version:** 1.0  
**Created:** 2025-10-22  
**Status:** 📋 Planning Phase  
**Priority:** 🔴 CRITICAL SECURITY FIX

---

## 📋 Executive Summary

This document provides a comprehensive specification for implementing secure password reset functionality in the Mitsubishi Motors Customer Management System. The current implementation has a **critical security vulnerability** that allows unauthorized password resets without email verification. This enhancement will leverage the existing OTP infrastructure to implement industry-standard secure password reset.

---

## 🚨 Current Security Vulnerability

### **Critical Issue: Unauthenticated Password Reset**

**Severity:** CRITICAL  
**CVSS Score:** 9.1 (Critical)  
**Attack Vector:** Network  
**Complexity:** Low  
**Privileges Required:** None

### **Current Workflow (INSECURE)**

```
User → forgot_password.php
  ↓ (Enter email)
  ↓ (Email exists check)
  ↓ (Display button with email in GET parameter)
User → reset_password.php?email=victim@example.com
  ↓ (Enter new password)
  ↓ (Direct password update - NO VERIFICATION)
  ✗ Account compromised
```

### **Vulnerability Details**

1. **No Email Verification**: System does not send verification email/OTP
2. **Email Exposure**: Email passed via GET parameter (visible in URL, browser history, server logs)
3. **No Token/OTP**: No cryptographic token or OTP required
4. **Direct Database Update**: Password updated immediately without ownership verification
5. **Attack Scenario**: 
   - Attacker knows victim's email
   - Attacker visits `forgot_password.php`
   - Attacker enters victim's email
   - Attacker clicks "Go to Reset Password"
   - Attacker sets new password
   - Victim's account is compromised

---

## ✅ Proposed Secure Workflow

### **New Workflow (SECURE)**

```
User → forgot_password.php
  ↓ (Enter email)
  ↓ (Email exists check)
  ↓ (Generate OTP, send to email)
  ↓ (Set session: pending_password_reset_user_id, pending_password_reset_email)
User → verify_reset_otp.php
  ↓ (Enter 6-digit OTP)
  ↓ (Verify OTP against database)
  ↓ (OTP valid: set session: password_reset_verified)
User → reset_password.php
  ↓ (Check session: password_reset_verified)
  ↓ (Enter new password)
  ↓ (Update password in database)
  ↓ (Clear all reset sessions)
  ✓ Password reset complete
```

---

## 📁 Files to Modify

### **1. pages/forgot_password.php**

**Current State:**
- Lines 7-24: Email check and button display
- No OTP generation
- No email sending

**Required Changes:**
- **Line 15-20**: Replace button display with OTP generation
- **Add**: OTPService integration
- **Add**: Session variable setting
- **Add**: Redirect to `verify_reset_otp.php`

**New Logic:**
```php
if ($account) {
    // Generate and send OTP
    require_once(dirname(__DIR__) . '/includes/services/OTPService.php');
    $otpService = new \Mitsubishi\Services\OTPService($connect);
    $otpResult = $otpService->sendOTP($account['Id'], $email);
    
    if ($otpResult['success']) {
        $_SESSION['pending_password_reset_user_id'] = $account['Id'];
        $_SESSION['pending_password_reset_email'] = $email;
        header("Location: verify_reset_otp.php");
        exit;
    } else {
        $reset_error = "Failed to send verification code. Please try again.";
    }
}
```

**Session Variables Set:**
- `$_SESSION['pending_password_reset_user_id']` - Account ID requesting reset
- `$_SESSION['pending_password_reset_email']` - Email address

---

### **2. pages/verify_reset_otp.php** (NEW FILE)

**Purpose:** OTP verification page for password reset

**Based On:** `pages/verify_otp.php` (existing email verification)

**Key Differences:**
- Different session variables
- Different success redirect
- Different page title/messaging
- No account creation notifications

**Session Requirements:**
- **Input**: `$_SESSION['pending_password_reset_user_id']`
- **Input**: `$_SESSION['pending_password_reset_email']`
- **Output**: `$_SESSION['password_reset_verified']` (timestamp)

**Features:**
- 6-digit OTP input field
- Real-time validation (digits only)
- Resend button with 60-second countdown
- Error/success message display
- Attempt tracking (max 5 attempts)
- Session-based security

**Form Actions:**

1. **Verify OTP** (`POST: verify_otp`)
   - Validates OTP format (6 digits)
   - Calls `OTPService::verifyOTP()`
   - On success:
     - Set `$_SESSION['password_reset_verified'] = time()`
     - Keep user ID and email in session
     - Redirect to `reset_password.php`
   - On failure:
     - Show error with remaining attempts

2. **Resend OTP** (`POST: resend_otp`)
   - Calls `OTPService::resendOTP()`
   - Starts 60-second countdown timer
   - Shows success/error message

**Redirects:**
- Success → `reset_password.php`
- No session → `login.php`
- Already logged in → Appropriate dashboard

**UI Elements:**
- Logo and branding
- Email display (masked: u***@example.com)
- OTP input (6-digit, centered, large font)
- Verify button
- Resend button with countdown
- Expiration notice (10 minutes)
- Security warning
- Back to login link

---

### **3. pages/reset_password.php**

**Current State:**
- Lines 4: Gets email from GET parameter (INSECURE)
- Lines 8-33: Direct password update without verification

**Required Changes:**

**A. Session Validation (NEW - Lines 4-20)**
```php
session_start();

// Check if password reset was verified
if (!isset($_SESSION['password_reset_verified']) || 
    !isset($_SESSION['pending_password_reset_user_id']) || 
    !isset($_SESSION['pending_password_reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

// Check if verification is still valid (10 minutes)
$verificationTime = $_SESSION['password_reset_verified'];
if (time() - $verificationTime > 600) { // 10 minutes
    unset($_SESSION['password_reset_verified']);
    unset($_SESSION['pending_password_reset_user_id']);
    unset($_SESSION['pending_password_reset_email']);
    header("Location: forgot_password.php?error=expired");
    exit;
}

$accountId = $_SESSION['pending_password_reset_user_id'];
$email = $_SESSION['pending_password_reset_email'];
```

**B. Password Update Logic (Modified - Lines 8-33)**
- Remove GET parameter usage
- Use session variables instead
- Add session cleanup after successful reset

**C. Success Handler (Modified - Line 29)**
```php
if ($account) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $update = $connect->prepare("UPDATE accounts SET PasswordHash = ? WHERE Id = ?");
    $update->execute([$hash, $accountId]);
    
    // Clear all password reset sessions
    unset($_SESSION['password_reset_verified']);
    unset($_SESSION['pending_password_reset_user_id']);
    unset($_SESSION['pending_password_reset_email']);
    
    $reset_success = "Password has been reset successfully. <a href='login.php' style='color:#ffd700;'>Log in</a>";
}
```

**D. UI Changes**
- Remove email from GET parameter
- Display email from session (masked)
- Add session expiration warning
- Update page title to reflect secure process

---

## 📧 Email Template

### **Option 1: Reuse Existing Template**

**File:** `includes/email_templates/otp_verification.php`

**Current Usage:** Email verification during registration

**Modification:** Add context parameter to differentiate

```php
function getOTPEmailTemplate($otp, $context = 'registration')
{
    if ($context === 'password_reset') {
        $title = 'Password Reset Request';
        $message = 'You have requested to reset your password. Use the code below to proceed:';
        $warning = '⚠️ If you did not request this, please ignore this email and contact support immediately.';
    } else {
        $title = 'Verify Your Email';
        $message = 'Thank you for creating an account. Use the code below to verify your email:';
        $warning = '🔒 Never share this code with anyone';
    }
    // ... rest of template
}
```

### **Option 2: Create New Template (RECOMMENDED)**

**File:** `includes/email_templates/password_reset_otp.php` (NEW)

**Function:** `getPasswordResetOTPTemplate($otp, $email)`

**Content:**
- Subject: "Password Reset Request - Mitsubishi Motors"
- Mitsubishi Motors branding
- Clear "Password Reset" heading
- Large, prominent OTP display
- Expiration notice (10 minutes)
- Security warning: "If you didn't request this, ignore this email"
- Support contact information
- Mobile-responsive design

**Key Differences from Registration Template:**
- Different subject line
- Different heading and messaging
- Stronger security warning
- No "Welcome" message
- Emphasis on "ignore if not requested"

---

## 🔧 OTPService Integration

### **Existing Service (NO CHANGES REQUIRED)**

**File:** `includes/services/OTPService.php`

**Namespace:** `\Mitsubishi\Services\OTPService`

**Why No Changes?**
The existing OTPService is **context-agnostic** and can be used for both email verification and password reset without modification. The service handles:
- OTP generation (6-digit cryptographic)
- OTP storage (hashed in database)
- OTP verification (timing-attack resistant)
- Rate limiting (attempts, resends, cooldown)
- Email sending via GmailMailer

**Methods Used:**

1. **`sendOTP($accountId, $email)`**
   - Generates 6-digit OTP
   - Stores in `email_verifications` table
   - Sends email via GmailMailer
   - Returns: `['success' => bool, 'message' => string, 'otp_id' => int|null]`

2. **`verifyOTP($accountId, $otpCode)`**
   - Retrieves latest unused OTP for account
   - Checks expiration (10 minutes)
   - Checks attempt limit (5 attempts)
   - Verifies OTP hash
   - Marks OTP as used
   - Returns: `['success' => bool, 'message' => string]`

3. **`resendOTP($accountId, $email)`**
   - Checks resend limit (3 resends)
   - Checks cooldown period (60 seconds)
   - Generates new OTP
   - Invalidates old OTPs
   - Returns: `['success' => bool, 'message' => string]`

**Database Table:** `email_verifications`

The same table is used for both email verification and password reset. The context is determined by the session variables, not the database records.

**Note:** The `email_verified` and `email_verified_at` columns in the `accounts` table are **NOT** updated during password reset (only during email verification).

---

## 🗄️ Database Schema

### **No Database Changes Required**

The existing `email_verifications` table supports both email verification and password reset:

```sql
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,           -- Plain OTP (for email)
    otp_hash VARCHAR(255) NOT NULL,         -- Hashed OTP (for verification)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,          -- 10 minutes from creation
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,                 -- Max 5
    max_attempts INT DEFAULT 5,
    is_used TINYINT DEFAULT 0,
    resend_count INT DEFAULT 0,             -- Max 3
    last_resend_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_account_id (account_id),
    INDEX idx_email (email),
    INDEX idx_otp_hash (otp_hash),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Why No Changes?**
- OTP records are temporary (10-minute expiration)
- Same security requirements for both use cases
- Context is maintained via session variables
- Cleanup job removes old records regardless of context

---

## 🔐 Session Management

### **Session Variables Overview**

The implementation uses **different session variables** for email verification vs. password reset to prevent conflicts and ensure security.

### **Email Verification Sessions** (Existing)

Used during account registration:

```php
$_SESSION['pending_verification_user_id']  // Account ID awaiting email verification
$_SESSION['pending_verification_email']    // Email to verify
```

**Lifecycle:**
1. Set in `create_account.php` after account creation
2. Checked in `verify_otp.php` for access control
3. Cleared in `verify_otp.php` after successful verification
4. Replaced with full session (`user_id`, `user_role`, etc.)

### **Password Reset Sessions** (NEW)

Used during password reset:

```php
$_SESSION['pending_password_reset_user_id']  // Account ID requesting password reset
$_SESSION['pending_password_reset_email']    // Email address
$_SESSION['password_reset_verified']         // Timestamp of OTP verification
```

**Lifecycle:**
1. Set in `forgot_password.php` after email validation
2. Checked in `verify_reset_otp.php` for access control
3. `password_reset_verified` set in `verify_reset_otp.php` after OTP verification
4. All checked in `reset_password.php` before allowing password change
5. All cleared in `reset_password.php` after successful password reset

### **Full User Sessions** (Existing)

Used after successful login:

```php
$_SESSION['user_id']      // Account ID
$_SESSION['user_role']    // Role: Customer, Admin, SalesAgent
$_SESSION['user_email']   // Email address
$_SESSION['username']     // Username
```

**Set in:**
- `login.php` after successful login
- `verify_otp.php` after email verification (for new accounts)

**Cleared in:**
- `logout.php` (full session destruction)

### **Session Security Measures**

1. **Separation of Concerns**
   - Different session variables for different workflows
   - Prevents cross-workflow attacks

2. **Time-Based Expiration**
   - `password_reset_verified` timestamp checked (10-minute validity)
   - Prevents replay attacks

3. **Session Validation**
   - All three password reset sessions must exist
   - Timestamp must be recent
   - Redirects to start if invalid

4. **Session Cleanup**
   - All reset sessions cleared after successful password change
   - Prevents reuse of verification

5. **No Logged-In User Conflict**
   - Password reset sessions are separate from user sessions
   - Logged-in users can still reset password (edge case)

---

## 🔄 Complete User Workflows

### **Workflow 1: New User Registration (Existing - No Changes)**

```
1. User fills registration form (create_account.php)
2. System creates account with email_verified = 0
3. OTPService generates 6-digit OTP
4. OTP stored in email_verifications table (hashed)
5. Email sent with OTP code
6. Session set: pending_verification_user_id, pending_verification_email
7. User redirected to verify_otp.php
8. User enters OTP code
9. System verifies OTP:
   ├─ Valid → Mark email_verified = 1, create full session, redirect to verification.php
   └─ Invalid → Show error, decrement attempts
10. User completes customer information form
11. User gains full access to system
```

**Files Involved:**
- `pages/create_account.php`
- `pages/verify_otp.php`
- `includes/services/OTPService.php`
- `includes/email_templates/otp_verification.php`

**Session Flow:**
```
pending_verification_* → (OTP verified) → user_id, user_role, etc.
```

---

### **Workflow 2: Forgot Password (NEW - Secure Implementation)**

```
1. User clicks "Forgot Password" on login page
2. User enters email on forgot_password.php
3. System checks if email exists in accounts table
4. If exists:
   ├─ Generate 6-digit OTP via OTPService
   ├─ Store OTP in email_verifications table (hashed)
   ├─ Send OTP email (password reset template)
   ├─ Set session: pending_password_reset_user_id, pending_password_reset_email
   └─ Redirect to verify_reset_otp.php
5. User enters OTP code on verify_reset_otp.php
6. System verifies OTP:
   ├─ Valid:
   │  ├─ Set session: password_reset_verified = current timestamp
   │  └─ Redirect to reset_password.php
   └─ Invalid:
      ├─ Show error with remaining attempts
      └─ If max attempts reached, require new OTP
7. User enters new password on reset_password.php
8. System validates:
   ├─ Check all three session variables exist
   ├─ Check password_reset_verified timestamp (< 10 minutes)
   └─ Validate password strength (min 6 characters, match confirmation)
9. System updates password in database
10. Clear all password reset sessions
11. Show success message with login link
12. User logs in with new password
```

**Files Involved:**
- `pages/forgot_password.php` (MODIFIED)
- `pages/verify_reset_otp.php` (NEW)
- `pages/reset_password.php` (MODIFIED)
- `includes/services/OTPService.php` (NO CHANGES)
- `includes/email_templates/password_reset_otp.php` (NEW - RECOMMENDED)

**Session Flow:**
```
pending_password_reset_* → (OTP verified) → password_reset_verified → (password changed) → cleared
```

---

### **Workflow 3: Login with Unverified Email (Existing - No Changes)**

```
1. User attempts login
2. Password verified successfully
3. System checks email_verified status
4. If unverified (email_verified = 0):
   ├─ Set session: pending_verification_user_id, pending_verification_email
   ├─ Generate new OTP via OTPService
   ├─ Send OTP email
   └─ Redirect to verify_otp.php
5. User verifies OTP
6. Login completes with full session
```

**Files Involved:**
- `pages/login.php`
- `pages/verify_otp.php`
- `includes/services/OTPService.php`

---

## 🎨 User Interface Design

### **1. forgot_password.php (MODIFIED)**

**Current UI:**
- Email input field
- "Send Reset Link" button (misleading - doesn't send link)
- "Go to Reset Password" button (appears after email check)

**New UI:**
- Email input field
- "Send Verification Code" button (updated text)
- Success message: "Verification code sent to your email"
- Loading state during OTP generation
- Error messages for invalid email or send failures

**Visual Changes:**
- Update button text from "Send Reset Link" to "Send Verification Code"
- Remove the "Go to Reset Password" button entirely
- Add loading spinner during OTP send
- Add success message with email masking (u***@example.com)

---

### **2. verify_reset_otp.php (NEW)**

**Based On:** `pages/verify_otp.php`

**UI Elements:**

**Header:**
- Mitsubishi logo
- Title: "Reset Your Password"
- Subtitle: "We've sent a 6-digit code to:"
- Email display (masked): `u***@example.com`

**OTP Input:**
- Large, centered input field
- 6-digit numeric only
- Letter-spacing for readability
- Auto-focus on page load
- Real-time validation (digits only)
- Placeholder: "000000"

**Buttons:**
- Primary: "Verify Code" (red Mitsubishi color)
- Secondary: "Resend Code" (gold color)
- Tertiary: "Back to Login" (link)

**Messages:**
- Error messages (red background)
- Success messages (green background)
- Info messages (gold background)

**Countdown Timer:**
- Displays after resend click
- "Wait X seconds before resending"
- Disables resend button during countdown

**Security Notices:**
- "⏱️ Code expires in 10 minutes"
- "🔒 Never share this code with anyone"
- "⚠️ If you didn't request this, contact support"

**Styling:**
- Match existing `verify_otp.php` design
- Mitsubishi brand colors (red, gold, black)
- Responsive design (mobile-friendly)
- Background: Mitsubishi background image

---

### **3. reset_password.php (MODIFIED)**

**Current UI:**
- Email from GET parameter (visible in URL)
- New password field
- Confirm password field
- "Reset Password" button

**New UI:**
- Email display from session (masked, read-only)
- Session expiration warning
- New password field with strength indicator
- Confirm password field
- Show/hide password toggles
- "Reset Password" button
- "Cancel" button (returns to login)

**Visual Changes:**
- Add email display at top (masked, not editable)
- Add session timer: "You have X minutes to complete this"
- Add password strength indicator
- Keep existing show/hide password checkboxes
- Add cancel button for user to abort

**Security Indicators:**
- Session expiration countdown
- Password strength meter (weak/medium/strong)
- Visual confirmation when passwords match

---

## 🔒 Security Features

### **1. OTP Security**

**Generation:**
- Cryptographically secure random number generation
- Uses `random_int(100000, 999999)` (PHP 7+)
- Fallback to `mt_rand()` if unavailable
- 6-digit numeric code (1 in 1,000,000 combinations)

**Storage:**
- Plain OTP stored temporarily for email sending
- Hashed using `password_hash($otp, PASSWORD_DEFAULT)` (bcrypt)
- Verification uses `password_verify()` for timing-attack resistance
- Plain OTP never logged or exposed in URLs

**Expiration:**
- 10-minute validity from generation
- Checked on every verification attempt
- Expired OTPs cannot be verified

### **2. Rate Limiting**

**Verification Attempts:**
- Maximum 5 attempts per OTP
- Counter incremented on each failed attempt
- Exceeded attempts require new OTP generation

**Resend Requests:**
- Maximum 3 resends per account
- 60-second cooldown between resends
- Prevents OTP flooding attacks

**Cooldown Enforcement:**
- Client-side countdown timer (UX)
- Server-side validation (security)
- Timestamp-based calculation

### **3. Session Security**

**Separation:**
- Password reset sessions separate from user sessions
- Different variable names prevent conflicts
- No interference with logged-in users

**Time-Based Validation:**
- `password_reset_verified` timestamp checked
- 10-minute window to complete password reset
- Prevents stale session reuse

**Cleanup:**
- All reset sessions cleared after successful reset
- Prevents replay attacks
- Forces new OTP for subsequent resets

### **4. Email Security**

**No Sensitive Data in URLs:**
- Email never passed via GET parameters
- All data in server-side sessions
- URLs are safe to share/log

**Email Masking:**
- Display format: `u***@example.com`
- Prevents shoulder surfing
- Confirms email without full exposure

**Sender Verification:**
- Emails sent from verified Mitsubishi domain
- SPF/DKIM/DMARC configured (assumed)
- Professional branding reduces phishing risk

### **5. Audit Trail**

**Logged Information:**
- IP address of requester
- User agent (browser/device)
- Timestamp of OTP generation
- Timestamp of verification attempts
- Timestamp of successful verification

**Database Records:**
- All OTP attempts stored in `email_verifications` table
- Queryable for security analysis
- Retention policy: 24 hours (via cleanup job)

### **6. Attack Prevention**

**Brute Force:**
- 5 attempt limit per OTP
- 10-minute OTP expiration
- 60-second resend cooldown
- Makes brute force impractical (1M combinations, 5 attempts)

**Enumeration:**
- Same response for valid/invalid emails (timing-safe)
- No indication whether email exists
- Prevents account enumeration

**Replay Attacks:**
- OTPs marked as used after verification
- Session timestamp prevents reuse
- Old sessions automatically expire

**Man-in-the-Middle:**
- OTP sent via email (separate channel)
- HTTPS enforced (assumed)
- Session cookies with HttpOnly flag

**Phishing:**
- Professional email template
- Clear sender identification
- Warning about not sharing OTP
- Short expiration window

---

## 📊 Error Handling

### **Error Scenarios**

| Scenario | Error Message | User Action | System Action |
|----------|--------------|-------------|---------------|
| Email not found | "No account found with that email address." | Re-enter email or create account | No OTP sent |
| OTP send failure | "Failed to send verification code. Please try again." | Retry | Log error, no session set |
| Invalid OTP format | "OTP must be 6 digits." | Re-enter OTP | No verification attempt |
| Wrong OTP | "Invalid OTP code. X attempts remaining." | Re-enter OTP | Increment attempts |
| Expired OTP | "OTP has expired. Please request a new one." | Click resend | Generate new OTP |
| Max attempts | "Maximum attempts reached. Please request a new code." | Click resend | Require new OTP |
| Max resends | "Maximum resend limit reached. Please contact support." | Contact support | Block further resends |
| Cooldown active | "Please wait X seconds before requesting a new OTP." | Wait | Enforce cooldown |
| Session expired | "Your session has expired. Please start over." | Return to forgot password | Clear sessions |
| Password mismatch | "Passwords do not match." | Re-enter passwords | No database update |
| Weak password | "Password must be at least 6 characters." | Enter stronger password | No database update |
| No session | "Invalid request. Please start the password reset process again." | Return to forgot password | Redirect |

### **Success Messages**

| Scenario | Success Message | Next Action |
|----------|----------------|-------------|
| OTP sent | "Verification code sent to your email" | Redirect to verify_reset_otp.php |
| OTP verified | "Code verified successfully!" | Redirect to reset_password.php |
| OTP resent | "New verification code sent to your email" | Show countdown timer |
| Password reset | "Password has been reset successfully. Log in" | Show login link |

### **Logging Strategy**

**Error Logs:**
```php
error_log("Password Reset - OTP Send Error: " . $e->getMessage());
error_log("Password Reset - OTP Verify Error: " . $e->getMessage());
error_log("Password Reset - Session Expired: User ID " . $accountId);
```

**Security Logs:**
```php
error_log("Password Reset - Max Attempts: Account ID " . $accountId . ", IP " . $ipAddress);
error_log("Password Reset - Max Resends: Account ID " . $accountId . ", IP " . $ipAddress);
```

**Success Logs:**
```php
error_log("Password Reset - Success: Account ID " . $accountId . ", IP " . $ipAddress);
```

---

## 🧪 Testing Checklist

### **Functional Testing**

- [ ] User can request password reset with valid email
- [ ] User receives OTP email within 1 minute
- [ ] OTP email contains correct 6-digit code
- [ ] OTP email has correct subject and branding
- [ ] User can verify OTP successfully
- [ ] User can set new password after OTP verification
- [ ] User can log in with new password
- [ ] Invalid email shows appropriate error
- [ ] Invalid OTP shows error with remaining attempts
- [ ] Expired OTP (>10 min) shows expiration error
- [ ] Max attempts (5) blocks further verification
- [ ] Resend generates new OTP
- [ ] Resend cooldown (60s) enforces waiting period
- [ ] Max resends (3) blocks further resend requests
- [ ] Session expiration (10 min) redirects to start
- [ ] Password validation works (min 6 chars, match)
- [ ] Success message shows after password reset
- [ ] All sessions cleared after successful reset

### **Security Testing**

- [ ] Email not exposed in URL
- [ ] Cannot access reset_password.php without OTP verification
- [ ] Cannot reuse verified session after password reset
- [ ] Cannot bypass OTP verification
- [ ] OTP stored as hash in database
- [ ] Rate limiting enforced server-side
- [ ] Session timeout enforced
- [ ] No account enumeration possible
- [ ] Brute force attacks prevented
- [ ] Replay attacks prevented
- [ ] Old OTPs invalidated when new one generated

### **UI/UX Testing**

- [ ] Email masking displays correctly
- [ ] OTP input accepts only digits
- [ ] Countdown timer displays correctly
- [ ] Resend button disabled during countdown
- [ ] Error messages clear and helpful
- [ ] Success messages clear and encouraging
- [ ] Mobile responsive design works
- [ ] Back button navigation handled correctly
- [ ] Browser refresh doesn't break flow
- [ ] Multiple tabs don't cause issues

### **Integration Testing**

- [ ] OTPService integration works correctly
- [ ] GmailMailer sends emails successfully
- [ ] Database operations complete successfully
- [ ] Session management works across pages
- [ ] Existing login flow not affected
- [ ] Existing registration flow not affected
- [ ] Email verification flow not affected

### **Edge Cases**

- [ ] User already logged in requests password reset
- [ ] User closes browser during reset process
- [ ] User opens reset link in multiple tabs
- [ ] User requests multiple resets simultaneously
- [ ] Network failure during OTP send
- [ ] Database connection failure
- [ ] Email service unavailable
- [ ] User enters OTP from old email
- [ ] User tries to reset non-existent account
- [ ] User tries to reset disabled account

---

## 📈 Monitoring & Maintenance

### **Metrics to Track**

**Usage Metrics:**
- Password reset requests per day
- OTP verification success rate
- Average time to complete reset
- Resend request frequency

**Security Metrics:**
- Failed OTP attempts per account
- Max attempts reached count
- Max resends reached count
- Session expiration count
- IP addresses with multiple failed attempts

**Email Metrics:**
- OTP email delivery success rate
- Email delivery time (latency)
- Bounce rate
- Spam complaints

### **Database Queries**

**Password Reset Success Rate (Last 7 Days):**
```sql
SELECT
    COUNT(*) as total_resets,
    SUM(CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN attempts >= max_attempts THEN 1 ELSE 0 END) as max_attempts_reached,
    SUM(CASE WHEN expires_at < NOW() AND verified_at IS NULL THEN 1 ELSE 0 END) as expired
FROM email_verifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

**Recent Password Resets:**
```sql
SELECT
    ev.account_id,
    a.Email,
    ev.created_at,
    ev.verified_at,
    ev.attempts,
    ev.resend_count,
    ev.ip_address
FROM email_verifications ev
JOIN accounts a ON ev.account_id = a.Id
WHERE ev.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY ev.created_at DESC;
```

**Suspicious Activity (Multiple Failed Attempts):**
```sql
SELECT
    ip_address,
    COUNT(*) as attempt_count,
    COUNT(DISTINCT account_id) as accounts_targeted,
    MIN(created_at) as first_attempt,
    MAX(created_at) as last_attempt
FROM email_verifications
WHERE attempts >= max_attempts
  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address
HAVING attempt_count > 5
ORDER BY attempt_count DESC;
```

### **Cleanup Jobs**

**Daily Cleanup (Cron Job):**
```php
// File: includes/cron/cleanup_expired_otps.php
require_once dirname(__DIR__) . '/services/OTPService.php';
require_once dirname(__DIR__) . '/database/db_conn.php';

$otpService = new \Mitsubishi\Services\OTPService($connect);
$deletedCount = $otpService->cleanupExpiredOTPs();

error_log("OTP Cleanup: Deleted $deletedCount expired records");
```

**Recommended Schedule:**
```bash
# Run daily at 2 AM
0 2 * * * /usr/bin/php /path/to/includes/cron/cleanup_expired_otps.php
```

---

## 🚀 Implementation Plan

### **Phase 1: Preparation (Day 1)**

**Tasks:**
1. ✅ Review current implementation
2. ✅ Document security vulnerabilities
3. ✅ Create implementation specification
4. ⬜ Review specification with team
5. ⬜ Get approval for changes

**Deliverables:**
- This specification document
- Security vulnerability report
- Implementation approval

---

### **Phase 2: Email Template (Day 1-2)**

**Tasks:**
1. ⬜ Create `password_reset_otp.php` email template
2. ⬜ Test email rendering in multiple clients
3. ⬜ Verify branding and messaging
4. ⬜ Test email delivery

**Files Created:**
- `includes/email_templates/password_reset_otp.php`

**Testing:**
- Gmail, Outlook, Yahoo, Apple Mail
- Desktop and mobile views
- Spam filter testing

---

### **Phase 3: OTP Verification Page (Day 2-3)**

**Tasks:**
1. ⬜ Create `verify_reset_otp.php` based on `verify_otp.php`
2. ⬜ Update session variable names
3. ⬜ Update UI messaging for password reset context
4. ⬜ Implement session validation
5. ⬜ Test OTP verification flow
6. ⬜ Test resend functionality
7. ⬜ Test error handling

**Files Created:**
- `pages/verify_reset_otp.php`

**Testing:**
- Valid OTP verification
- Invalid OTP handling
- Expired OTP handling
- Resend functionality
- Rate limiting
- Session management

---

### **Phase 4: Modify Forgot Password Page (Day 3)**

**Tasks:**
1. ⬜ Modify `forgot_password.php` to use OTPService
2. ⬜ Remove insecure button display
3. ⬜ Add OTP generation and sending
4. ⬜ Set password reset session variables
5. ⬜ Update UI messaging
6. ⬜ Test email validation
7. ⬜ Test OTP sending

**Files Modified:**
- `pages/forgot_password.php`

**Testing:**
- Valid email handling
- Invalid email handling
- OTP generation
- Email sending
- Session setting
- Redirect flow

---

### **Phase 5: Modify Reset Password Page (Day 3-4)**

**Tasks:**
1. ⬜ Add session validation to `reset_password.php`
2. ⬜ Remove GET parameter usage
3. ⬜ Add session expiration check
4. ⬜ Update password update logic
5. ⬜ Add session cleanup
6. ⬜ Update UI to show masked email
7. ⬜ Test password reset flow

**Files Modified:**
- `pages/reset_password.php`

**Testing:**
- Session validation
- Session expiration
- Password validation
- Password update
- Session cleanup
- Success flow

---

### **Phase 6: Integration Testing (Day 4-5)**

**Tasks:**
1. ⬜ Test complete password reset flow
2. ⬜ Test all error scenarios
3. ⬜ Test edge cases
4. ⬜ Verify no impact on existing flows
5. ⬜ Security testing
6. ⬜ Performance testing
7. ⬜ Cross-browser testing

**Testing Scope:**
- End-to-end password reset
- Email verification flow (ensure no regression)
- Login flow (ensure no regression)
- All error scenarios from checklist
- All edge cases from checklist

---

### **Phase 7: Documentation & Deployment (Day 5)**

**Tasks:**
1. ⬜ Update user documentation
2. ⬜ Update admin documentation
3. ⬜ Create deployment checklist
4. ⬜ Backup database
5. ⬜ Deploy to staging
6. ⬜ Final testing on staging
7. ⬜ Deploy to production
8. ⬜ Monitor for issues

**Deliverables:**
- Updated documentation
- Deployment checklist
- Rollback plan
- Monitoring dashboard

---

## 📝 Implementation Summary

### **Files to Create (2 files)**

1. **`pages/verify_reset_otp.php`** - OTP verification page for password reset
2. **`includes/email_templates/password_reset_otp.php`** - Email template for password reset OTP

### **Files to Modify (2 files)**

1. **`pages/forgot_password.php`** - Add OTP generation and sending
2. **`pages/reset_password.php`** - Add session validation and security

### **Files NOT Modified (Reused)**

1. **`includes/services/OTPService.php`** - No changes required
2. **`includes/backend/GmailMailer.php`** - No changes required
3. **`includes/database/create_email_verification_table.sql`** - No changes required

### **Total Effort Estimate**

- **Development:** 3-4 days
- **Testing:** 1-2 days
- **Documentation:** 0.5 days
- **Deployment:** 0.5 days
- **Total:** 5-7 days

### **Risk Assessment**

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Email delivery failure | Medium | High | Test with multiple providers, implement retry logic |
| Session conflicts | Low | Medium | Use distinct session variable names |
| User confusion | Medium | Low | Clear UI messaging, help text |
| Regression in existing flows | Low | High | Comprehensive integration testing |
| Database performance | Low | Low | Existing table already indexed |

---

## 🎯 Success Criteria

### **Functional Requirements**

✅ Users can reset password securely with OTP verification
✅ OTP sent via email within 1 minute
✅ OTP expires after 10 minutes
✅ Rate limiting prevents abuse
✅ Session management prevents unauthorized access
✅ All error scenarios handled gracefully
✅ Existing flows (login, registration) not affected

### **Security Requirements**

✅ No email exposure in URLs
✅ OTP stored as hash in database
✅ Session-based access control
✅ Time-based expiration enforced
✅ Brute force attacks prevented
✅ Replay attacks prevented
✅ Account enumeration prevented

### **User Experience Requirements**

✅ Clear, intuitive UI
✅ Helpful error messages
✅ Mobile-responsive design
✅ Professional email template
✅ Fast response times (<2 seconds)
✅ Accessible design (WCAG 2.1 AA)

---

## 📞 Support & Troubleshooting

### **Common Issues**

**Issue:** OTP email not received
**Solution:**
1. Check spam/junk folder
2. Verify email address is correct
3. Check `email_logs` table for delivery status
4. Verify Gmail SMTP credentials in `.env`
5. Check Gmail daily sending limit

**Issue:** OTP verification fails
**Solution:**
1. Check OTP hasn't expired (10 minutes)
2. Verify attempts < 5
3. Check `email_verifications` table for record
4. Ensure OTP code matches exactly (6 digits)

**Issue:** Session expired error
**Solution:**
1. Complete password reset within 10 minutes
2. Request new OTP if expired
3. Don't close browser during process

**Issue:** Resend button disabled
**Solution:**
1. Wait 60 seconds after last resend
2. Check resend count < 3
3. Request new password reset if max resends reached

---

## 📚 References

### **Related Documentation**

- `otp_spec.md` - OTP Email Verification Specification
- `OTP_IMPLEMENTATION_SUMMARY.md` - OTP Implementation Summary
- `OTP_EMAIL_VERIFICATION_SPEC.md` - Email Verification Specification

### **External Resources**

- [OWASP Password Reset Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html)
- [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/)
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)

### **Security Standards**

- OWASP Top 10 (A07:2021 - Identification and Authentication Failures)
- CWE-640: Weak Password Recovery Mechanism
- PCI DSS Requirement 8.2.3 (Password Reset)

---

**Document Version:** 1.0
**Last Updated:** 2025-10-22
**Maintained By:** Mitsubishi Motors Development Team
**Status:** 📋 Ready for Implementation
