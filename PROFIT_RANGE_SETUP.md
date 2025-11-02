# Profit Range Feature Setup Guide

## Step 1: Run the SQL Migration

Open your MySQL/MariaDB database (phpMyAdmin or command line) and run:

```sql
source add_profit_range.sql;
```

Or manually execute the SQL in `add_profit_range.sql`

This will:
- Add `profit_percent_min` column
- Add `profit_percent_max` column  
- Update existing investments to use the range format

## Step 2: Test the Feature

### Adding a New Investment with Range:
1. Go to Admin → Investment Management
2. Click "Add Investment"
3. Fill in the form:
   - Title: "Test Project"
   - Total Goal: 50000
   - **Select "Range" option**
   - Minimum Profit Percent: 20
   - Maximum Profit Percent: 25
   - Set start and end dates
4. Save

### Result:
- The investment list will show: **20.0% - 25.0%**
- Client view will show: **20% - 25%**
- Expected profit will show range: **$200.00 - $250.00** (for $1000 investment)

### Adding a Fixed Profit:
1. Select "Fixed Percentage" option
2. Enter single profit value: 20
3. Result shows: **20.0%**

## Step 3: Verify Display

Check these pages to see profit ranges:
- ✅ Admin: Investment List (Profit % column)
- ✅ Admin: Client Investment (Profit % column)
- ✅ Admin: Pending Investments (Expected Profit column)
- ✅ Admin: Verify Payments (Expected Profit display)
- ✅ Client: Available Projects (Profit Rate display)
- ✅ Client: My Investments (Profit Rate & Expected Profit columns)
- ✅ Client: Payment History Modal (Profit Rate & Expected Profit)
- ✅ Client: Investment Modal (Expected Profit range in summary)

## Example Display Formats:

### Fixed Profit (20%):
- Profit Rate: **20.0%**
- Expected Profit: **$200.00**

### Range Profit (20% - 25%):
- Profit Rate: **20.0% - 25.0%**
- Expected Profit: **$200.00 - $250.00**

## Troubleshooting:

### If columns don't exist:
Run the SQL migration first: `source add_profit_range.sql;`

### If forms don't show range options:
The investment-list.php file has been updated with:
- Radio buttons for "Fixed Percentage" vs "Range"
- Conditional display of single field or min/max fields
- JavaScript toggle function

### If existing investments show NULL:
The migration script automatically updates existing investments to use their current profit_percent as both min and max.
