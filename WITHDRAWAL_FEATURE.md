# Profit Withdrawal Feature

## Overview
This feature allows clients to request withdrawal of their investment profits after the project duration has ended. Admins can review, approve/reject requests, and upload transfer proof when the withdrawal is completed.

## Database Setup

### For New Installations
The `withdrawals` table is already included in `asfour-ims.sql`. Just import the full SQL file.

### For Existing Databases
Run the migration file:
```sql
SOURCE create_withdrawals_table.sql;
```

This creates the `withdrawals` table with the following structure:
- `withdrawal_id` - Primary key
- `client_investment_id` - Links to client_investments table
- `client_id` - Links to clients table
- `investment_id` - Links to investments table
- `withdrawal_amount` - Calculated expected profit amount
- `request_date` - When client submitted the request
- `status` - pending / approved / completed / rejected
- `withdrawal_proof` - Filename of uploaded transfer receipt
- `processed_date` - When admin processed the request
- `processed_by` - Admin user ID who processed
- `admin_notes` - Admin notes (rejection reason, etc.)
- `client_notes` - Client bank details and instructions

## Required Directory Structure

Create the uploads directory for withdrawal proofs:
```bash
mkdir uploads/withdrawals
chmod 755 uploads/withdrawals
```

Make sure the web server has write permissions to this directory.

## Client Workflow

### 1. My Investments Page (`c/pages/my-investments.php`)
- Clients see all their investments with a "Withdraw Date" column
- The withdraw date is calculated as: Investment End Date + Project Duration
- Example: Investment ends Dec 31, 2025 + 3 months duration = Withdraw date Apr 01, 2026

### 2. Request Withdrawal Button
The "Request Withdrawal" button appears when:
- The project duration has ended (today >= withdraw date)
- The investment status is "active"
- No withdrawal request exists OR previous request was rejected

Button states:
- **Green "Request Withdrawal"** - Available to request
- **Warning "Withdrawal Pending"** - Request submitted, waiting for admin review
- **Info "Withdrawal Approved"** - Admin approved, processing transfer
- **Success "Withdrawal Completed"** - Transfer completed with proof uploaded
- **Danger "Withdrawal Rejected"** - Can re-request after reviewing rejection reason

### 3. Withdrawal Request Modal
When clicking "Request Withdrawal", client sees:
- Project name
- Expected profit amount (or range if profit is variable)
- Text area to enter bank account details:
  - Bank Name
  - Account Number
  - Account Holder Name
  - IBAN/SWIFT (if applicable)
  - Additional instructions

### 4. Withdrawal History
Below the investments table, clients see their withdrawal history showing:
- Request ID
- Project name
- Investment and withdrawal amounts
- Request date and time
- Current status with color-coded labels
- Actions:
  - **View Proof** (if completed) - Downloads transfer receipt
  - **View Reason** (if rejected) - Shows admin's rejection reason

## Admin Workflow

### 1. Pending Withdrawals Page (`pages/pending-withdrawals.php`)
Access via sidebar: **Pending Withdrawals** (with "New" badge)

Dashboard shows:
- **Statistics Cards**:
  - Pending Requests count + total amount
  - Approved count (awaiting transfer)
  - Completed count + total transferred
  - Total requests all time

- **Withdrawals Table**:
  - Request ID, Client info, Project details
  - Investment amount with profit rate
  - Withdrawal amount
  - Request date/time
  - Status with color-coded labels
  - Action buttons based on status

### 2. View Details
Click "View Details" to see:
- **Client Information**: Name, email, phone
- **Withdrawal Information**: Project, amount, status
- **Client Bank Details**: Full bank account info provided by client
- **Admin Notes**: (if any) Previous processing notes
- **Transfer Proof**: (if completed) Link to download proof

### 3. Approve/Reject Workflow

#### Pending Status
- **Approve Button**: Marks as "approved" - ready for transfer
- **Reject Button**: Opens modal to enter rejection reason
  - Must provide reason (required field)
  - Client will see this reason

#### Approved Status
- **Upload Proof Button**: Opens modal to upload transfer receipt
  - Accept: JPG, PNG, PDF (max 5MB)
  - Optional admin notes field
  - Automatically marks as "completed" on successful upload

#### Completed Status
- **View Proof Button**: Downloads the uploaded transfer receipt
- No further actions needed

### 4. Processing Flow
```
Pending → [Admin Approves] → Approved → [Admin Uploads Proof] → Completed
        ↘ [Admin Rejects] → Rejected (Client can re-request)
```

## File Structure

### Client Files
- `c/pages/my-investments.php` - Main page with withdrawal button and history
- `c/request-withdrawal.php` - Process client withdrawal requests

### Admin Files
- `pages/pending-withdrawals.php` - Admin management page
- `pages/process-withdrawal-action.php` - Handle approve/reject actions
- `pages/process-withdrawal-proof.php` - Handle proof upload

### Database
- `create_withdrawals_table.sql` - Migration for existing databases
- `asfour-ims.sql` - Updated with withdrawals table

### Uploads
- `uploads/withdrawals/` - Store transfer proof files
  - Files named: `withdrawal_{id}_{timestamp}.{ext}`

## Menu Integration

### Admin Sidebar
Added to `partials/sidebar.php`:
```html
<li><a href="index.php?p=pending-withdrawals">
  <i class="fa fa-money"></i> Pending Withdrawals 
  <span class="badge bg-blue">New</span>
</a></li>
```

### Routing
The page routing in `index.php` automatically handles the URL:
- `index.php?p=pending-withdrawals` → loads `pages/pending-withdrawals.php`

## Security Features

### Client-Side Security
- Session validation for logged-in clients
- Ownership verification - clients can only request withdrawals for their own investments
- Date validation - prevents early withdrawal requests
- Duplicate prevention - one active request per investment

### Admin-Side Security
- Session validation for logged-in admins
- Status validation - prevents invalid state transitions
- File upload validation:
  - File type checking (MIME type validation)
  - File size limit (5MB max)
  - Secure filename generation
  - Automatic cleanup on errors

### SQL Injection Prevention
- All queries use prepared statements with parameter binding
- Input sanitization with `trim()` and `intval()`

## Calculation Logic

### Expected Profit Calculation
```php
// For profit range (e.g., 20%-25%)
$profitPercentMin = $investment['profit_percent_min'];
$profitPercentMax = $investment['profit_percent_max'];
$profitPercent = ($profitPercentMin + $profitPercentMax) / 2; // Use average

// For fixed profit (e.g., 20%)
$profitPercent = $investment['profit_percent'];

// Calculate withdrawal amount
$withdrawalAmount = $investment['invested_amount'] * ($profitPercent / 100);
```

### Withdraw Date Calculation
```php
// Start from investment end date
$endDate = new DateTime($investment['end_date']);
$projectStart = clone $endDate;
$projectStart->modify('+1 day'); // Project starts day after investment ends

// Parse duration
if (strpos($investment['duration'], 'month') !== false) {
    $months = intval($investment['duration']); // e.g., "3 months" → 3
} elseif (strpos($investment['duration'], 'year') !== false) {
    $months = intval($investment['duration']) * 12; // e.g., "1 year" → 12
}

// Calculate withdraw date
$withdrawDate = clone $projectStart;
$withdrawDate->modify("+{$months} months");
```

## User Experience

### Client Benefits
✅ Clear visibility of when profits become available  
✅ Simple withdrawal request process  
✅ Track withdrawal status in real-time  
✅ Download transfer proof when completed  
✅ Understand rejection reasons if declined  

### Admin Benefits
✅ Centralized withdrawal management dashboard  
✅ Clear statistics and overview  
✅ Easy approve/reject workflow  
✅ Organized proof upload system  
✅ Complete audit trail with processing history  

## Testing Checklist

### Database Setup
- [ ] Run migration on existing database
- [ ] Verify foreign keys are created
- [ ] Check `uploads/withdrawals/` directory exists with write permissions

### Client Testing
- [ ] Create investment with duration
- [ ] Fast-forward system date OR wait for duration to end
- [ ] Verify "Request Withdrawal" button appears
- [ ] Submit withdrawal request with bank details
- [ ] Check button changes to "Withdrawal Pending"
- [ ] Verify withdrawal appears in history section

### Admin Testing
- [ ] Access Pending Withdrawals page from sidebar
- [ ] Verify statistics cards show correct counts
- [ ] View withdrawal details
- [ ] Approve a withdrawal
- [ ] Upload transfer proof (JPG/PNG/PDF)
- [ ] Verify file uploads to `uploads/withdrawals/`
- [ ] Reject a withdrawal with reason

### Integration Testing
- [ ] Client sees updated status after admin action
- [ ] Client can download proof after completion
- [ ] Client can view rejection reason
- [ ] Client can re-request after rejection
- [ ] Duplicate requests are prevented

## Troubleshooting

### Button Not Appearing
- Check if investment status is "active"
- Verify project duration is set in investments table
- Ensure today's date >= withdraw date
- Check if withdrawal already exists

### Upload Errors
- Verify `uploads/withdrawals/` directory exists
- Check directory permissions (755 or 777)
- Ensure file size < 5MB
- Verify file type is JPG, PNG, or PDF

### Foreign Key Errors
- Ensure all referenced tables exist
- Check that `clients.id`, `investments.id` use correct column names
- Run migration after confirming table structure

## Future Enhancements

Potential improvements:
- Email notifications for withdrawal status changes
- Automatic withdrawal processing via payment gateway integration
- Bulk withdrawal approval for admin
- Withdrawal reports and analytics
- Support for partial withdrawals
- Withdrawal request deadline settings
- Multi-currency support

## Support

For issues or questions:
1. Check this documentation first
2. Review SQL error logs
3. Verify file permissions
4. Check browser console for JavaScript errors
5. Review PHP error logs for server-side issues
