# Quick Installation - Withdrawal Feature

## Step 1: Database Setup

### Option A: New Installation
If setting up a fresh database, just import the main SQL file:
```sql
SOURCE asfour-ims.sql;
```

### Option B: Existing Database
If you already have the system running, run the migration:
```sql
USE asfour-ims;
SOURCE create_withdrawals_table.sql;
```

## Step 2: Create Upload Directory

### Windows (XAMPP)
```powershell
# Navigate to your project folder
cd C:\xampp\htdocs\asfour-ims-v1.1

# Create directory
New-Item -ItemType Directory -Path "uploads\withdrawals" -Force
```

### Linux/Mac
```bash
cd /path/to/asfour-ims-v1.1
mkdir -p uploads/withdrawals
chmod 755 uploads/withdrawals
```

## Step 3: Verify Files

Check that these new files exist:
- ✅ `c/request-withdrawal.php` - Client withdrawal request handler
- ✅ `pages/pending-withdrawals.php` - Admin management page
- ✅ `pages/process-withdrawal-action.php` - Approve/reject handler
- ✅ `pages/process-withdrawal-proof.php` - Proof upload handler
- ✅ `create_withdrawals_table.sql` - Database migration
- ✅ `WITHDRAWAL_FEATURE.md` - Full documentation

Modified files:
- ✅ `c/pages/my-investments.php` - Added withdrawal button and history
- ✅ `partials/sidebar.php` - Added menu item
- ✅ `asfour-ims.sql` - Added withdrawals table

## Step 4: Test the Feature

### As Client:
1. Login to client portal
2. Go to "My Investments"
3. Look for investments where withdraw date has passed
4. Click "Request Withdrawal"
5. Enter bank details and submit

### As Admin:
1. Login to admin panel
2. Click "Pending Withdrawals" in sidebar
3. View the withdrawal request
4. Click "Approve"
5. Click "Upload Proof"
6. Select transfer receipt (JPG/PNG/PDF)
7. Submit

### Verify:
1. Client should see status change to "Completed"
2. Client can click "View Proof" to download receipt
3. Withdrawal appears in client's history section

## Step 5: Production Considerations

### Security
- [ ] Set proper file permissions on `uploads/withdrawals/` (755 recommended)
- [ ] Consider adding `.htaccess` to prevent direct file access
- [ ] Review upload size limits in php.ini if needed

### Backup
- [ ] Backup database before running migration
- [ ] Backup modified files before deployment

### Monitoring
- [ ] Check error logs after first few withdrawals
- [ ] Monitor upload directory disk space
- [ ] Test with different file types (JPG, PNG, PDF)

## Common Issues

### "Table already exists" error
If you see this when running the migration:
- The table might already be created
- Check: `SHOW TABLES LIKE 'withdrawals';`
- If exists, skip migration or drop table first (backup first!)

### Upload fails
- Check directory exists: `uploads/withdrawals/`
- Check permissions: `chmod 755 uploads/withdrawals`
- Check file size in php.ini: `upload_max_filesize = 5M`

### Button not showing
- Verify investment has a duration set
- Check that project duration has ended
- Ensure investment status is 'active'

## Need Help?

See full documentation: `WITHDRAWAL_FEATURE.md`
