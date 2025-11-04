<?php
/* c/request-withdrawal.php - Process Client Withdrawal Requests */

session_start();
require_once '../config.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $investment_id = intval($_POST['investment_id']);
    $client_notes = trim($_POST['client_notes']);
    
    // Validate input
    if (empty($investment_id) || empty($client_notes)) {
        $_SESSION['error_message'] = "Please provide all required information.";
        header('Location: my-investments.php');
        exit();
    }
    
    // Get investment details and verify ownership
    $investmentSQL = "SELECT 
        ci.id as client_investment_id,
        ci.invested_amount,
        ci.status as investment_status,
        i.id as investment_id,
        i.title as project_title,
        i.profit_percent,
        i.profit_percent_min,
        i.profit_percent_max,
        i.duration,
        i.end_date
    FROM client_investments ci
    JOIN investments i ON ci.investment_id = i.id
    WHERE ci.id = ? AND ci.client_id = ? AND ci.status = 'active'";
    
    $stmt = $conn->prepare($investmentSQL);
    $stmt->bind_param("ii", $investment_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = "Investment not found or not eligible for withdrawal.";
        header('Location: my-investments.php');
        exit();
    }
    
    $investment = $result->fetch_assoc();
    
    // Verify project duration has ended
    if ($investment['duration'] && $investment['end_date']) {
        $endDate = new DateTime($investment['end_date']);
        $projectStart = clone $endDate;
        $projectStart->modify('+1 day');
        
        $months = 0;
        if (strpos($investment['duration'], 'month') !== false) {
            $months = intval($investment['duration']);
        } elseif (strpos($investment['duration'], 'year') !== false) {
            $months = intval($investment['duration']) * 12;
        }
        
        if ($months > 0) {
            $withdrawDate = clone $projectStart;
            $withdrawDate->modify("+{$months} months");
            $today = new DateTime();
            
            if ($today < $withdrawDate) {
                $_SESSION['error_message'] = "Withdrawal not available yet. Project duration ends on " . $withdrawDate->format('M d, Y');
                header('Location: my-investments.php');
                exit();
            }
        }
    } else {
        $_SESSION['error_message'] = "Project duration not set for this investment.";
        header('Location: my-investments.php');
        exit();
    }
    
    // Check if withdrawal already requested
    $checkSQL = "SELECT withdrawal_id, status FROM withdrawals WHERE client_investment_id = ? AND status NOT IN ('rejected')";
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param("i", $investment_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $_SESSION['error_message'] = "A withdrawal request already exists for this investment.";
        header('Location: my-investments.php');
        exit();
    }
    
    // Calculate expected profit
    $profitPercentMin = $investment['profit_percent_min'] ?? $investment['profit_percent'];
    $profitPercentMax = $investment['profit_percent_max'] ?? $investment['profit_percent'];
    
    // Use average for withdrawal amount if range
    $profitPercent = ($profitPercentMin + $profitPercentMax) / 2;
    $withdrawalAmount = $investment['invested_amount'] * ($profitPercent / 100);
    
    // Insert withdrawal request
    $insertSQL = "INSERT INTO withdrawals 
        (client_investment_id, client_id, investment_id, withdrawal_amount, client_notes, status, request_date) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    
    $insertStmt = $conn->prepare($insertSQL);
    $insertStmt->bind_param("iiids", 
        $investment_id, 
        $client_id, 
        $investment['investment_id'],
        $withdrawalAmount, 
        $client_notes
    );
    
    if ($insertStmt->execute()) {
        $_SESSION['success_message'] = "Withdrawal request submitted successfully! Admin will review and process your request.";
    } else {
        $_SESSION['error_message'] = "Failed to submit withdrawal request. Please try again.";
    }
    
    $insertStmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

header('Location: my-investments.php');
exit();
?>
