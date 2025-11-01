# Investment Verification Process - Implementation Guide

## Overview
This system implements a 3-stage verification process for investment requests with payment proof verification.

## Workflow Stages

### Stage 1: Investment Request Submission (Client)
**Status: `pending`**

1. Client browses available projects on `c/pages/available-projects.php`
2. Client clicks "Invest Now" or "Invest Again" (multiple investments allowed)
3. Client fills investment amount and date in modal
4. System creates investment record with status `pending`
5. Client sees "Pending Approval" in My Investments page

### Stage 2: Investment Approval (Admin)
**Status: `pending` → `approved`**

**Admin Page:** `pages/pending-investments.php`

1. Admin reviews pending investment requests
2. Admin can see:
   - Client details (name, email, phone)
   - Project details (title, goal, profit rate)
   - Investment amount and expected profit
3. Admin actions:
   - **Approve:** Sets status to `approved`, records `approved_at` and `approved_by`
   - **Reject:** Sets status to `rejected`, records `rejection_reason`

### Stage 3: Payment Proof Upload (Client)
**Status: `approved` → `payment_pending`**

**Client Page:** `c/pages/my-investments.php`
**Upload Handler:** `c/upload-payment-proof.php`

1. Client sees "Upload Payment" button for approved investments
2. Client uploads payment proof (JPG, PNG, or PDF, max 5MB)
3. Optional: Client adds payment notes
4. System stores file in `uploads/payments/` directory
5. Investment status changes to `payment_pending`
6. Records `payment_proof_uploaded_at` timestamp

### Stage 4: Payment Verification (Admin)
**Status: `payment_pending` → `active`**

**Admin Page:** `pages/verify-payments.php`

1. Admin reviews uploaded payment proofs
2. Admin can view payment proof in modal or new tab
3. Admin actions:
   - **Verify & Activate:** Sets status to `active`, records `payment_verified_at` and `payment_verified_by`
   - **Reject Payment:** Reverts to `approved` status, clears payment proof, client can re-upload

### Stage 5: Investment Active
**Status: `active`**

1. Investment is now fully activated
2. Counts toward project funding
3. Client can view in My Investments with "Active" status

## Database Schema Updates

### New Columns in `client_investments` Table

```sql
- payment_proof VARCHAR(255) - Payment proof filename
- payment_proof_uploaded_at TIMESTAMP - When client uploaded payment
- approved_at TIMESTAMP - When admin approved the request
- approved_by INT(11) - Admin ID who approved (FK to admins.id)
- payment_verified_at TIMESTAMP - When payment was verified
- payment_verified_by INT(11) - Admin ID who verified payment (FK to admins.id)
- rejection_reason TEXT - Reason for rejection if status is rejected
```

### Updated Status Enum
```sql
ENUM('pending', 'approved', 'payment_pending', 'rejected', 'active', 'completed')
```

## File Structure

### SQL Migration
- `add_payment_verification.sql` - Run this to add new fields to database

### Admin Pages
- `pages/pending-investments.php` - Review and approve/reject investment requests
- `pages/verify-payments.php` - Verify payment proofs and activate investments

### Client Pages
- `c/pages/available-projects.php` - Browse and invest in projects (updated)
- `c/pages/my-investments.php` - View investments and upload payment proofs (updated)
- `c/upload-payment-proof.php` - Handle payment proof file uploads

### Navigation
- `partials/sidebar.php` - Added menu items for pending approvals and payment verification

## Directory Structure

### Upload Directories (Auto-created)
```
uploads/
  agreements/     - Investment agreement documents
  payments/       - Payment proof files from clients
```

## Installation Steps

1. **Run SQL Migration**
   ```sql
   -- Execute the SQL script
   source add_payment_verification.sql;
   ```

2. **Verify Database Changes**
   ```sql
   SHOW COLUMNS FROM client_investments;
   ```

3. **Create Upload Directories** (automatically created by PHP, but can be created manually)
   ```bash
   mkdir -p uploads/payments
   chmod 755 uploads/payments
   ```

4. **Access New Admin Pages**
   - Pending Approvals: Admin Dashboard → "Pending Approvals"
   - Payment Verification: Admin Dashboard → "Verify Payments"

5. **Test the Workflow**
   - Login as client → Invest in a project
   - Login as admin → Approve the investment
   - Login as client → Upload payment proof
   - Login as admin → Verify and activate investment

## Status Transitions

```
pending → approved (by admin approval)
pending → rejected (by admin rejection)
approved → payment_pending (after client uploads payment)
payment_pending → active (by admin payment verification)
payment_pending → approved (by admin payment rejection - client can re-upload)
active → completed (when project ends)
```

## Security Features

1. **File Upload Validation**
   - File type validation (only JPG, PNG, PDF)
   - File size limit (5MB maximum)
   - Unique filename generation
   - MIME type checking

2. **Access Control**
   - Client can only upload payment for their own investments
   - Client can only upload for approved investments
   - Admin authentication required for approval/verification

3. **Database Integrity**
   - Foreign key constraints for approved_by and payment_verified_by
   - Transaction handling for data consistency
   - Status validation before state changes

## Notification Opportunities (Future Enhancement)

Consider adding email notifications for:
- Client: Investment approved (ready to upload payment)
- Admin: New payment proof uploaded (needs verification)
- Client: Payment verified (investment now active)
- Client: Payment rejected (needs to re-upload)
- Client: Investment request rejected

## Troubleshooting

### Upload Directory Permission Issues
```bash
chmod 755 uploads/payments
chown www-data:www-data uploads/payments  # For Linux/Apache
```

### Large File Upload Issues
Update `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Payment Proof Not Displaying
- Check file path in database matches actual file location
- Verify web server has read permissions on uploads directory
- Check browser console for 404 errors

## Summary

This implementation provides:
✅ 3-stage verification process (Request → Approval → Payment → Verification)
✅ Multiple investments per project allowed
✅ File upload with validation
✅ Admin approval workflow
✅ Payment proof verification
✅ Audit trail (who approved, who verified, when)
✅ Rejection with reasons
✅ Client can re-upload rejected payments

The system ensures that investments are properly verified before being activated, providing security and accountability for both clients and administrators.
