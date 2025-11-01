# Environment Configuration Guide
# Copy this file to .env.example for reference

## IMPORTANT: How to Switch Between Local and Server Environments

### Method 1: Manual Toggle (Recommended for beginners)
Edit config.php and change the following line:

```php
// For Local Development (XAMPP/WAMP/MAMP)
define('IS_LOCAL_ENVIRONMENT', true);

// For Production Server
define('IS_LOCAL_ENVIRONMENT', false);
```

### Method 2: Auto-Detection (Advanced)
Uncomment this line in config.php to auto-detect environment:

```php
define('IS_LOCAL_ENVIRONMENT', ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1'));
```

## Local Development Settings
- Host: localhost
- Username: root  
- Password: (empty)
- Database: asfour-ims
- Port: 3306
- Base URL: http://localhost/asfour-ims-v1.1/
- Error Reporting: Enabled

## Production Server Settings (Update these in config.php)
- Host: your_server_host
- Username: your_server_username
- Password: your_server_password
- Database: your_server_database
- Port: 3306
- Base URL: https://yourdomain.com/
- Error Reporting: Disabled (for security)

## Environment Features
✅ Automatic database configuration switching
✅ Different error reporting levels
✅ Environment-specific upload paths
✅ Timezone configuration
✅ Debug function (local only)
✅ Security settings per environment

## Quick Setup Instructions

### For Local Development:
1. Set `IS_LOCAL_ENVIRONMENT = true` in config.php
2. Ensure XAMPP/WAMP is running
3. Import asfour-ims.sql to your local MySQL
4. Access: http://localhost/asfour-ims-v1.1/

### For Production Server:
1. Set `IS_LOCAL_ENVIRONMENT = false` in config.php
2. Update server database credentials in config.php
3. Update BASE_URL and UPLOAD_PATH
4. Upload files to server
5. Import database to production server
6. Test all functionality

## Security Notes
- Never commit production credentials to version control
- Use environment variables for sensitive data in production
- Enable SSL/HTTPS in production
- Regularly backup production database
- Monitor error logs in production

## Troubleshooting
- Database connection issues: Check credentials and server status
- File upload problems: Verify upload path permissions
- Session issues: Check session configuration
- Timezone problems: Verify timezone settings match server location