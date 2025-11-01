-- Fix existing investments that have remaining_amount = 0
-- This updates records created before the fix in invest.php

USE `asfour-ims`;

-- Update investments where remaining_amount is 0 but should equal invested_amount
UPDATE `client_investments` 
SET `remaining_amount` = `invested_amount`,
    `total_paid` = 0.00,
    `is_fully_paid` = 0
WHERE `remaining_amount` = 0 
  AND `total_paid` = 0 
  AND `status` IN ('pending', 'approved', 'payment_pending', 'payment_partial');

-- Verify the fix
SELECT id, client_id, investment_id, invested_amount, total_paid, remaining_amount, is_fully_paid, status
FROM client_investments
WHERE status IN ('pending', 'approved', 'payment_pending', 'payment_partial')
ORDER BY id DESC
LIMIT 10;
