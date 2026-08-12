# 🚀 INSTALLATION INSTRUCTIONS

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependencies)
- OxaPay API account (for cryptocurrency deposits)

---

## Step 1: Extract Files

Extract the `dollar-tree-platform` folder to your web server's document root:

```bash
# For XAMPP
C:/xampp/htdocs/dollar-tree-platform

# For WAMP
C:/wamp64/www/dollar-tree-platform

# For Linux
/var/www/html/dollar-tree-platform
```

---

## Step 2: Create Database

1. Open phpMyAdmin or MySQL command line
2. Create a new database:

```sql
CREATE DATABASE dollar_tree_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Import the schema:

```bash
mysql -u root -p dollar_tree_db < database/schema.sql
```

Or use phpMyAdmin:
- Select `dollar_tree_db` database
- Click "Import" tab
- Choose `database/schema.sql`
- Click "Go"

---

## Step 3: Configure Database Connection

Edit `config.php` (lines 36-40):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dollar_tree_db');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_CHARSET', 'utf8mb4');
```

---

## Step 4: Set Up OxaPay (Optional)

1. Sign up at https://oxapay.com
2. Get your merchant API key
3. In admin panel, go to Settings
4. Enter your OxaPay API key
5. Enable cryptocurrency deposits

**Note:** Without OxaPay, deposits won't work. For testing, you can manually add deposits via admin panel.

---

## Step 5: Configure Web Server

### Apache (.htaccess already included)

Make sure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### Nginx

Add to your site configuration:

```nginx
location /dollar-tree-platform {
    try_files $uri $uri/ /dollar-tree-platform/index.php?$query_string;
}
```

---

## Step 6: Set Permissions

```bash
# Linux/Mac
chmod 755 /var/www/html/dollar-tree-platform
chmod 777 /var/www/html/dollar-tree-platform/logs

# Create logs directory if not exists
mkdir -p /var/www/html/dollar-tree-platform/logs
```

---

## Step 7: Access the Platform

### User Interface
```
http://localhost/dollar-tree-platform/user/register.php
http://localhost/dollar-tree-platform/user/login.php
```

### Admin Panel
```
http://localhost/dollar-tree-platform/admin/login.php
```

**Default Admin Credentials:**
- Username: `admin`
- Email: `admin@dollartree.local`
- Password: `admin123`

**⚠️ CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN!**

---

## Step 8: Test the System

### Create Test User Account

1. Visit registration page
2. Register with email: `test@example.com`
3. Password: `test123`
4. Leave invitation code blank (or use a referral code if testing referrals)

### Test Admin Functions

1. Login to admin panel
2. Navigate to "Users" to see registered users
3. Go to "SVIP Settings" to configure tier levels
4. Check "Settings" for platform configuration

---

## Step 9: Configure Settings

In Admin Panel > Settings, configure:

- **OxaPay API Key**: Your merchant API key
- **OxaPay Enabled**: 1 (to enable)
- **Min Deposit Amount**: 2 (USDT)
- **Min Withdrawal Amount**: 9 (USDT)
- **Withdrawal Fee Percentage**: 0
- **Daily Withdrawal Limit**: 1
- **Auto Approve Withdrawals**: 0 (manual approval)
- **Withdrawal Processing Time**: 24 (hours)

---

## Common Issues & Solutions

### Issue 1: Database Connection Failed
**Solution:** Check `config.php` database credentials match your MySQL setup

### Issue 2: 404 Errors on All Pages
**Solution:** Enable mod_rewrite in Apache or configure Nginx properly

### Issue 3: Deposits Not Working
**Solution:** 
- Check OxaPay API key is correct
- Verify callback URL is accessible from internet (use ngrok for local testing)
- Check logs in `/logs/` directory

### Issue 4: Cannot Write to Logs
**Solution:** 
```bash
chmod 777 /path/to/dollar-tree-platform/logs
```

### Issue 5: Sessions Not Working
**Solution:** Check PHP session.save_path is writable

---

## Testing Deposits Without OxaPay

For testing purposes, you can manually add deposits via admin panel:

1. Login to admin panel
2. Go to "Deposits" > "Add Manual Deposit"
3. Select user, enter amount, mark as completed
4. User balance will update automatically

---

## Security Recommendations

1. **Change Admin Password Immediately**
2. **Use HTTPS in Production** (configure SSL certificate)
3. **Restrict Admin Panel** (IP whitelist in .htaccess)
4. **Enable PHP Error Logging** (not display_errors)
5. **Regular Database Backups**
6. **Update OxaPay API Keys** regularly
7. **Monitor Activity Logs** for suspicious activity

---

## File Structure Overview

```
dollar-tree-platform/
├── api/                      # API endpoints
│   ├── register_user.php
│   ├── login_user.php
│   ├── get_deposit_address.php
│   ├── deposit_callback.php
│   ├── submit_withdrawal.php
│   └── complete_task.php
├── admin/                    # Admin panel (create these)
├── user/                     # User pages (create these)
├── assets/                   # CSS, JS, Images (create these)
├── languages/                # Translations
│   └── en.php
├── database/                 # Database schema
│   └── schema.sql
├── logs/                     # Application logs (auto-created)
├── config.php                # Main configuration
└── README.md                 # Documentation
```

---

## Next Steps

After installation:

1. Review the complete README.md for system architecture
2. Customize the design in `/assets/css/`
3. Add more language files in `/languages/`
4. Create user interface pages in `/user/`
5. Create admin interface pages in `/admin/`
6. Test all functionality thoroughly

---

## Support

For issues:
1. Check `/logs/` directory for error logs
2. Review `/logs/app_YYYY-MM-DD.log` for application logs
3. Check PHP error logs
4. Verify database connections

---

## ⚠️ LEGAL DISCLAIMER

This platform is for EDUCATIONAL PURPOSES ONLY. Do not use for real investment operations. Cryptocurrency investment platforms of this nature may be illegal in your jurisdiction. Consult legal counsel before deploying any similar system.

---

## Development Roadmap

To complete the platform, you still need to create:

### User Pages (HTML/JS/CSS):
- [ ] user/register.php - Registration form
- [ ] user/login.php - Login form  
- [ ] user/dashboard.php - Main dashboard
- [ ] user/tasks.php - Daily tasks page
- [ ] user/team.php - Referral management
- [ ] user/vip.php - VIP levels display
- [ ] user/recharge.php - Deposit page
- [ ] user/withdraw.php - Withdrawal page
- [ ] user/profile.php - User settings

### Admin Pages:
- [ ] admin/login.php - Admin login
- [ ] admin/dashboard.php - Admin dashboard
- [ ] admin/users.php - User management
- [ ] admin/deposits.php - Deposit approval
- [ ] admin/withdrawals.php - Withdrawal approval
- [ ] admin/settings.php - Platform settings

### Remaining APIs:
- [ ] api/get_user_info.php
- [ ] api/get_team_info.php
- [ ] api/get_svip_levels.php
- [ ] api/change_language.php
- [ ] api/change_password.php

These can be built following the same patterns as the existing API files.

---

Installation Complete! 🎉
