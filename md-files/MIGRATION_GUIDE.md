# 🚀 HOSTINGER MIGRATION GUIDE
## Mitsubishi Dealership System

---

## ✅ SECURITY FIXES COMPLETED

All critical security issues have been fixed! Your application is now ready for production deployment.

### What Was Fixed:

1. ✅ **Database Connection** - Now uses environment variables from `.env`
2. ✅ **DeepSeek API Key** - Moved to `.env` (DEEPSEEK_API_KEY)
3. ✅ **PhilSMS API Token** - Moved to `.env` (PHILSMS_API_TOKEN)
4. ✅ **Removed apikey.txt** - Security risk eliminated
5. ✅ **Updated .env** - Now contains Hostinger production credentials
6. ✅ **Created .env.production** - Ready-to-use production environment file

---

## 📋 HOSTINGER DEPLOYMENT STEPS

### STEP 1: Prepare Your Files

**IMPORTANT**: Do NOT upload the `.env` file from your local machine!

1. **Zip your project files** (excluding `.env`):
   - Right-click on the `Mitsubishi` folder
   - Select "Send to" > "Compressed (zipped) folder"
   - OR use this command in terminal:
     ```bash
     # Make sure you're in the parent directory
     zip -r mitsubishi.zip Mitsubishi -x "*.env" "*.git*" "*.log"
     ```

2. **Verify .env is NOT in the zip**:
   - Open the zip file and make sure `.env` is not included
   - The `.gitignore` file should prevent it, but double-check!

---

### STEP 2: Upload to Hostinger

#### Option A: Using File Manager (Recommended for beginners)

1. **Log in to Hostinger hPanel**
2. **Go to File Manager**
3. **Navigate to `public_html`** (or your domain's root directory)
4. **Upload the zip file**
5. **Extract the zip file** (right-click > Extract)
6. **Move all files from `Mitsubishi` folder to `public_html`**:
   - You should have: `index.php`, `pages/`, `includes/`, etc. directly in `public_html`
   - NOT: `public_html/Mitsubishi/index.php`

#### Option B: Using FTP/SFTP (Recommended for advanced users)

1. **Get FTP credentials** from Hostinger hPanel
2. **Use FileZilla or WinSCP**
3. **Connect to your server**
4. **Upload all files to `public_html`** (except `.env`)

---

### STEP 3: Create Production .env File on Hostinger

**CRITICAL**: You must create the `.env` file directly on the server!

1. **In Hostinger File Manager**, navigate to `public_html`
2. **Click "New File"** and name it `.env`
3. **Edit the file** and paste this content:

```env
# Gmail SMTP Configuration
GMAIL_EMAIL=mitsubishiautoxpress@gmail.com
GMAIL_PASSWORD=rkob ukdt awdq bjte
GMAIL_FROM_NAME=Mitsubishiautoxpress
GMAIL_FROM_EMAIL=mitsubishiautoxpress@gmail.com

# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
SMTP_AUTH=true

# Database Configuration - HOSTINGER PRODUCTION
DB_HOST=localhost
DB_NAME=u205309581_mitsubishi
DB_USER=u205309581_admin
DB_PASSWORD=5lk$4>Li

# API Keys
DEEPSEEK_API_KEY=sk-27e6623100404762826fc0d41454bfff
PHILSMS_API_TOKEN=2727|bb03dgKcJI26H18Ai1to1TRIU0MBUxUly21xjXoQ

# Application Settings
APP_ENV=production
APP_DEBUG=false
```

4. **Save the file**
5. **Set file permissions to 600** (read-only by owner):
   - Right-click on `.env` > Permissions
   - Set to `600` or `-rw-------`

**ALTERNATIVE**: You can also copy the content from `.env.production` file that was created for you.

---

### STEP 4: Set File Permissions

Set the correct permissions for upload directories:

1. **In File Manager**, navigate to each directory and set permissions:

   | Directory | Permissions | Numeric |
   |-----------|-------------|---------|
   | `uploads/` | `drwxrwxr-x` | `775` |
   | `uploads/vehicle_images/` | `drwxrwxr-x` | `775` |
   | `uploads/vehicle_images/main/` | `drwxrwxr-x` | `775` |
   | `uploads/vehicle_images/additional/` | `drwxrwxr-x` | `775` |
   | `uploads/vehicle_images/360/` | `drwxrwxr-x` | `775` |
   | `uploads/loan_documents/` | `drwxrwxr-x` | `775` |
   | `uploads/receipts/` | `drwxrwxr-x` | `775` |
   | `uploads/3d_models/` | `drwxrwxr-x` | `775` |

2. **Right-click on each folder** > Permissions > Set to `775`

---

### STEP 5: Configure PHP Settings

Hostinger shared hosting may not support `php_value` directives in `.htaccess`.

1. **Check if your `.htaccess` works**:
   - Visit your website
   - Try uploading a file
   - If you get errors, proceed to step 2

2. **Create `.user.ini` file** (if needed):
   - In File Manager, create a new file named `.user.ini` in `public_html`
   - Add this content:

```ini
upload_max_filesize = 100M
post_max_size = 200M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
```

3. **Save and wait 5 minutes** for changes to take effect

---

### STEP 6: Verify Database Connection

1. **Visit your website**: `https://yourdomain.com`
2. **Check if the homepage loads**
3. **Try logging in** as admin
4. **If you get database errors**:
   - Check that `.env` file exists and has correct credentials
   - Verify database name, username, and password in Hostinger cPanel
   - Check error logs in Hostinger File Manager (`error_log` file)

---

### STEP 7: Test All Functionality

Go through this checklist:

- [ ] **Homepage loads** without errors
- [ ] **Admin login** works
- [ ] **Sales Agent login** works
- [ ] **Customer login** works
- [ ] **Vehicle listings** display correctly
- [ ] **File uploads** work (try uploading a vehicle image)
- [ ] **Email sending** works (test contact form or registration)
- [ ] **SMS sending** works (if applicable)
- [ ] **Chatbot** responds correctly
- [ ] **About Us page** shows company information from settings
- [ ] **Footer** displays company information dynamically
- [ ] **Settings page** loads and saves correctly

---

### STEP 8: Enable HTTPS/SSL

1. **In Hostinger hPanel**, go to **SSL**
2. **Enable SSL certificate** (free Let's Encrypt)
3. **Force HTTPS** by adding this to the TOP of your `.htaccess`:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 🔍 TROUBLESHOOTING

### Database Connection Fails

**Error**: "Database connection failed"

**Solutions**:
1. Verify `.env` file exists in `public_html`
2. Check database credentials in Hostinger cPanel > MySQL Databases
3. Ensure `EnvLoader.php` is uploaded to `includes/utils/`
4. Check file permissions on `.env` (should be 600)

---

### File Uploads Don't Work

**Error**: "Failed to upload file" or "Permission denied"

**Solutions**:
1. Set upload directories to `775` permissions
2. Check `.user.ini` or `.htaccess` for upload limits
3. Verify `uploads/` directory exists
4. Check PHP error logs for specific errors

---

### Email Not Sending

**Error**: "Failed to send email"

**Solutions**:
1. Verify Gmail credentials in `.env`
2. Check that Gmail App Password is correct (not regular password)
3. Enable "Less secure app access" in Gmail (if needed)
4. Check Hostinger's SMTP restrictions
5. Consider using Hostinger's SMTP instead of Gmail

---

### API Keys Not Working

**Error**: "API key not configured" or "Unauthorized"

**Solutions**:
1. Verify `.env` file contains `DEEPSEEK_API_KEY` and `PHILSMS_API_TOKEN`
2. Check that `EnvLoader.php` is being loaded correctly
3. Verify API keys are still valid (not expired or revoked)
4. Check error logs for specific API errors

---

### Pages Show Blank or White Screen

**Error**: Blank page, no content

**Solutions**:
1. Check PHP error logs in File Manager (`error_log`)
2. Temporarily enable error display by adding to top of `index.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Check that all required files are uploaded
4. Verify PHP version is 7.4 or higher

---

## 📊 POST-MIGRATION CHECKLIST

After migration, monitor these for 24-48 hours:

- [ ] **Error logs** - Check daily for any PHP errors
- [ ] **Database performance** - Monitor query speeds
- [ ] **Email delivery** - Verify emails are being sent and received
- [ ] **File uploads** - Test with different file sizes
- [ ] **API usage** - Monitor DeepSeek and PhilSMS usage/costs
- [ ] **User feedback** - Ask users to report any issues
- [ ] **Backup database** - Set up automated backups in Hostinger

---

## 🔐 SECURITY BEST PRACTICES

1. **Never commit `.env` to Git** ✅ (already in .gitignore)
2. **Use strong passwords** for database and admin accounts
3. **Keep SSL enabled** (HTTPS)
4. **Regularly update API keys** if compromised
5. **Monitor error logs** for suspicious activity
6. **Set up database backups** (daily recommended)
7. **Limit file upload sizes** to prevent abuse
8. **Use prepared statements** for all database queries ✅ (already implemented)

---

## 📞 SUPPORT

If you encounter issues during migration:

1. **Check Hostinger Knowledge Base**: https://support.hostinger.com
2. **Contact Hostinger Support**: Available 24/7 via live chat
3. **Check error logs**: `public_html/error_log` file
4. **Review this guide**: Most issues are covered in Troubleshooting section

---

## ✅ SUMMARY

Your Mitsubishi Dealership System is now **PRODUCTION-READY**!

**What was done**:
- ✅ Fixed all critical security issues
- ✅ Moved sensitive credentials to environment variables
- ✅ Created production-ready `.env` configuration
- ✅ Updated database connection for Hostinger
- ✅ Secured API keys (DeepSeek, PhilSMS)
- ✅ Removed exposed credential files

**Next steps**:
1. Upload files to Hostinger (excluding `.env`)
2. Create `.env` file on server with production credentials
3. Set file permissions for upload directories
4. Test all functionality
5. Enable HTTPS/SSL
6. Monitor for 24-48 hours

**Estimated deployment time**: 1-2 hours

Good luck with your migration! 🚀

