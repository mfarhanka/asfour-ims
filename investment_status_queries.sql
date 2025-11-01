-- Quick Reference Queries for Investment Verification Process

-- 1. Check all pending investment requests (waiting for admin approval)
SELECT 
    ci.id,
    c.name as client_name,
    i.title as project,
    ci.invested_amount,
    ci.created_at
FROM client_investments ci
JOIN clients c ON ci.client_id = c.id
JOIN investments i ON ci.investment_id = i.id
WHERE ci.status = 'pending'
ORDER BY ci.created_at ASC;

-- 2. Check all approved investments awaiting payment upload
SELECT 
    ci.id,
    c.name as client_name,
    i.title as project,
    ci.invested_amount,
    ci.approved_at,
    CASE 
        WHEN ci.payment_proof IS NULL THEN 'Awaiting Payment Upload'
        ELSE 'Has Payment Proof'
    END as payment_status
FROM client_investments ci
JOIN clients c ON ci.client_id = c.id
JOIN investments i ON ci.investment_id = i.id
WHERE ci.status = 'approved'
ORDER BY ci.approved_at ASC;

-- 3. Check all payment proofs pending verification
SELECT 
    ci.id,
    c.name as client_name,
    i.title as project,
    ci.invested_amount,
    ci.payment_proof,
    ci.payment_proof_uploaded_at,
    TIMESTAMPDIFF(HOUR, ci.payment_proof_uploaded_at, NOW()) as hours_waiting
FROM client_investments ci
JOIN clients c ON ci.client_id = c.id
JOIN investments i ON ci.investment_id = i.id
WHERE ci.status = 'payment_pending' AND ci.payment_proof IS NOT NULL
ORDER BY ci.payment_proof_uploaded_at ASC;

-- 4. Check all active investments
SELECT 
    ci.id,
    c.name as client_name,
    i.title as project,
    ci.invested_amount,
    ci.payment_verified_at,
    a.name as verified_by_admin
FROM client_investments ci
JOIN clients c ON ci.client_id = c.id
JOIN investments i ON ci.investment_id = i.id
LEFT JOIN admins a ON ci.payment_verified_by = a.id
WHERE ci.status = 'active'
ORDER BY ci.payment_verified_at DESC;

-- 5. Check rejected investments with reasons
SELECT 
    ci.id,
    c.name as client_name,
    i.title as project,
    ci.invested_amount,
    ci.rejection_reason,
    ci.created_at
FROM client_investments ci
JOIN clients c ON ci.client_id = c.id
JOIN investments i ON ci.investment_id = i.id
WHERE ci.status = 'rejected'
ORDER BY ci.created_at DESC;

-- 6. Investment status summary (dashboard stats)
SELECT 
    status,
    COUNT(*) as count,
    SUM(invested_amount) as total_amount
FROM client_investments
GROUP BY status
ORDER BY 
    CASE status
        WHEN 'pending' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'payment_pending' THEN 3
        WHEN 'active' THEN 4
        WHEN 'completed' THEN 5
        WHEN 'rejected' THEN 6
    END;

-- 7. Admin approval performance
SELECT 
    a.name as admin_name,
    COUNT(ci.id) as approvals_count,
    SUM(ci.invested_amount) as total_approved_amount,
    MIN(ci.approved_at) as first_approval,
    MAX(ci.approved_at) as last_approval
FROM admins a
LEFT JOIN client_investments ci ON a.id = ci.approved_by
WHERE ci.approved_by IS NOT NULL
GROUP BY a.id, a.name
ORDER BY approvals_count DESC;

-- 8. Admin payment verification performance
SELECT 
    a.name as admin_name,
    COUNT(ci.id) as verifications_count,
    SUM(ci.invested_amount) as total_verified_amount,
    MIN(ci.payment_verified_at) as first_verification,
    MAX(ci.payment_verified_at) as last_verification
FROM admins a
LEFT JOIN client_investments ci ON a.id = ci.payment_verified_by
WHERE ci.payment_verified_by IS NOT NULL
GROUP BY a.id, a.name
ORDER BY verifications_count DESC;

-- 9. Average processing time by stage
SELECT 
    'Approval Time (pending to approved)' as stage,
    ROUND(AVG(TIMESTAMPDIFF(HOUR, ci.created_at, ci.approved_at)), 2) as avg_hours
FROM client_investments ci
WHERE ci.approved_at IS NOT NULL
UNION ALL
SELECT 
    'Payment Upload Time (approved to payment uploaded)' as stage,
    ROUND(AVG(TIMESTAMPDIFF(HOUR, ci.approved_at, ci.payment_proof_uploaded_at)), 2) as avg_hours
FROM client_investments ci
WHERE ci.payment_proof_uploaded_at IS NOT NULL
UNION ALL
SELECT 
    'Payment Verification Time (payment uploaded to verified)' as stage,
    ROUND(AVG(TIMESTAMPDIFF(HOUR, ci.payment_proof_uploaded_at, ci.payment_verified_at)), 2) as avg_hours
FROM client_investments ci
WHERE ci.payment_verified_at IS NOT NULL;

-- 10. Client investment activity
SELECT 
    c.name as client_name,
    c.email,
    COUNT(ci.id) as total_investments,
    SUM(CASE WHEN ci.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN ci.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN ci.status = 'payment_pending' THEN 1 ELSE 0 END) as payment_pending_count,
    SUM(CASE WHEN ci.status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN ci.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(ci.invested_amount) as total_invested
FROM clients c
LEFT JOIN client_investments ci ON c.id = ci.client_id
GROUP BY c.id, c.name, c.email
HAVING total_investments > 0
ORDER BY total_invested DESC;
