# OTP Email Verification - Implementation Summary

## Quick Overview

This document provides a quick reference for implementing OTP email verification for customer account registration in the Mitsubishi Motors system.

---

## Current System State

### Registration Flow (BEFORE OTP)
```
User fills form → Validate → Create account → Auto login → Redirect to verification.php
```

**Issues:**
- ❌ No email validation
- ❌ Users can register with fake emails
- ❌ Immediate access without verification
- ❌ Security vulnerability

### Email Infrastructure (READY TO USE)
- ✅ Gmail SMTP configured
- ✅ PHPMailer library available
- ✅ Email service: `includes/backend/GmailMailer.php`
- ✅ Email API: `api/send_email_api.php`
- ✅ Branded email templates available

---

## Proposed Flow (AFTER OTP)

```
User fills form 
  → Validate 
  → Create account (email_verified=0) 
  → Generate OTP 
  → Send OTP email 
  → Redirect to verify_otp.php 
  → User enters OTP 
  → Validate OTP 
  → Mark email_verified=1 
  → Allow login 
  → Redirect to verification.php
```

---

## Files to Create

### 1. Database Migration
**File:** `includes/database/create_email_verification_table.sql`
```sql
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    is_used TINYINT DEFAULT 0,
    resend_count INT DEFAULT 0,
    last_resend_at TIMESTAMP NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(Id) ON DELETE CASCADE
);

ALTER TABLE accounts 
ADD COLUMN email_verified TINYINT DEFAULT 0 AFTER Email,
ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email_verified;
```

### 2. OTP Service Class
**File:** `includes/services/OTPService.php`

**Key Methods:**
- `generateOTP()` - Generate 6-digit code
- `sendOTP($accountId, $email)` - Send OTP email
- `verifyOTP($accountId, $otpCode)` - Validate OTP
- `resendOTP($accountId)` - Resend with rate limiting
- `cleanupExpiredOTPs()` - Cleanup job

### 3. OTP Verification Page
**File:** `pages/verify_otp.php`

**Features:**
- OTP input form (6-digit)
- Resend button with countdown
- Error messages
- Success redirect
- Session validation

### 4. Email Template
**File:** `includes/email_templates/otp_verification.php`

**Content:**
- Welcome message
- Large OTP code display
- Expiration notice (10 minutes)
- Security warning
- Support contact

---

## Files to Modify

### 1. `pages/create_account.php`
**Changes:**
```php
// After account creation (line ~35)
// OLD:
$_SESSION['user_id'] = $newUserId;
header("Location: verification.php");

// NEW:
require_once(dirname(__DIR__) . '/includes/services/OTPService.php');
$otpService = new OTPService();
$otpResult = $otpService->sendOTP($newUserId, $email);

if ($otpResult['success']) {
    $_SESSION['pending_verification_user_id'] = $newUserId;
    $_SESSION['pending_verification_email'] = $email;
    header("Location: verify_otp.php");
} else {
    $register_error = "Failed to send verification email. Please try again.";
}
```

### 2. `pages/login.php`
**Changes:**
```php
// After password verification (line ~45)
// Add email verification check
if ($account && password_verify($password, $account['PasswordHash'])) {
    // NEW: Check email verification
    if ($account['email_verified'] == 0) {
        $_SESSION['pending_verification_user_id'] = $account['Id'];
        $_SESSION['pending_verification_email'] = $account['Email'];
        header("Location: verify_otp.php");
        exit;
    }
    
    // Existing login logic...
}
```

### 3. `pages/verification.php`
**Changes:**
```php
// At the top, after session check (line ~5)
// NEW: Check email verification
$stmt = $connect->prepare("SELECT email_verified FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account || $account['email_verified'] == 0) {
    header("Location: verify_otp.php");
    exit;
}
```

### 4. `pages/register_submit.php`
**Changes:** Same as `create_account.php`

---

## Key Features

### OTP Generation
- **Format:** 6-digit numeric code
- **Algorithm:** Cryptographically secure random (`random_int()`)
- **Storage:** Hashed using `password_hash()`
- **Expiration:** 10 minutes

### Rate Limiting
- **Resend:** Max 3 times, 60-second cooldown
- **Attempts:** Max 5 per OTP
- **Tracking:** Stored in database

### Security
- ✅ OTP stored as hash (not plaintext)
- ✅ Single-use OTPs
- ✅ Time-based expiration
- ✅ Attempt limiting
- ✅ IP address logging
- ✅ Session-based verification state

---

## Implementation Checklist

### Phase 1: Database
- [ ] Create `email_verifications` table
- [ ] Alter `accounts` table
- [ ] Test database changes
- [ ] Create indexes

### Phase 2: Backend
- [ ] Create `OTPService.php`
- [ ] Implement OTP generation
- [ ] Implement OTP validation
- [ ] Implement rate limiting
- [ ] Create email template
- [ ] Test email sending

### Phase 3: Frontend
- [ ] Create `verify_otp.php`
- [ ] Add OTP input form
- [ ] Add resend functionality
- [ ] Add countdown timer
- [ ] Add error handling
- [ ] Style the page

### Phase 4: Integration
- [ ] Modify `create_account.php`
- [ ] Modify `login.php`
- [ ] Modify `verification.php`
- [ ] Modify `register_submit.php`
- [ ] Update session handling

### Phase 5: Testing
- [ ] Test successful verification
- [ ] Test expired OTP
- [ ] Test invalid OTP
- [ ] Test resend functionality
- [ ] Test rate limiting
- [ ] Test email delivery
- [ ] Test edge cases

### Phase 6: Deployment
- [ ] Backup database
- [ ] Run migrations
- [ ] Deploy code
- [ ] Monitor logs
- [ ] Test in production

---

## Configuration Required

### Environment Variables (.env)
```env
# Already configured
GMAIL_EMAIL=your-email@gmail.com
GMAIL_PASSWORD=your-app-password
GMAIL_FROM_NAME=Mitsubishi Motors

# OTP Settings (add these)
OTP_EXPIRY_MINUTES=10
OTP_MAX_ATTEMPTS=5
OTP_MAX_RESENDS=3
OTP_RESEND_COOLDOWN_SECONDS=60
```

---

## Testing Scenarios

### Happy Path
1. ✅ User registers → receives OTP → enters correct code → verified

### Error Scenarios
1. ❌ Expired OTP → clear message, resend option
2. ❌ Invalid OTP → show attempts remaining
3. ❌ Max attempts → require new OTP
4. ❌ Max resends → contact support
5. ❌ Email delivery failure → retry option

### Edge Cases
1. 🔄 User refreshes page
2. 🔄 User goes back in browser
3. 🔄 Multiple tabs open
4. 🔄 Session expires
5. 🔄 Concurrent verification attempts

---

## Monitoring & Maintenance

### Metrics to Track
- OTP generation rate
- Email delivery success rate
- Verification success rate
- Average verification time
- Failed attempts per user

### Cleanup Jobs
```sql
-- Delete expired OTPs (run daily)
DELETE FROM email_verifications 
WHERE expires_at < NOW() - INTERVAL 24 HOUR;
```

### Logs to Monitor
- Email sending errors
- OTP validation failures
- Rate limit violations
- Unusual patterns

---

## Support & Troubleshooting

### Common Issues

**Issue:** User doesn't receive email
- Check spam folder
- Verify Gmail SMTP credentials
- Check email logs table
- Resend OTP

**Issue:** OTP expired
- Generate new OTP
- Check system time/timezone

**Issue:** Too many failed attempts
- Admin can reset verification status
- User can request new OTP

**Issue:** Email delivery fails
- Check Gmail sending limits (500/day)
- Verify SMTP settings
- Check error logs

---

## Security Best Practices

1. ✅ Always use HTTPS
2. ✅ Store OTPs as hashes
3. ✅ Implement rate limiting
4. ✅ Log all verification attempts
5. ✅ Use secure session settings
6. ✅ Validate all inputs
7. ✅ Add CSRF protection
8. ✅ Monitor for abuse patterns

---

## Next Steps

1. Review this specification with the team
2. Estimate implementation time
3. Set up development environment
4. Create database migrations
5. Implement OTP service
6. Build verification page
7. Integrate with registration flow
8. Test thoroughly
9. Deploy to production
10. Monitor and iterate

---

**For detailed technical specifications, see:** `OTP_EMAIL_VERIFICATION_SPEC.md`

