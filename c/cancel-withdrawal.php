<?php
/* c/cancel-withdrawal.php - Client Cancel Withdrawal Request */

session_start();
require_once '../config.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $withdrawal_id = intval($_POST['withdrawal_id']);
    
    // Validate input
    if (empty($withdrawal_id)) {
        $_SESSION['error_message'] = "Invalid withdrawal ID.";
        header('Location: my-investments.php');
        exit();
    }
    
    // Verify ownership and check if cancellable
    $checkSQL = "SELECT w.withdrawal_id, w.status, w.client_id 
                 FROM withdrawals w
                 WHERE w.withdrawal_id = ? AND w.client_id = ?";
    
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("ii", $withdrawal_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = "Withdrawal request not found or you don't have permission.";
        header('Location: my-investments.php');
        exit();
    }
    
    $withdrawal = $result->fetch_assoc();
    
    // Only allow cancellation for pending or approved status
    if (!in_array($withdrawal['status'], ['pending', 'approved'])) {
        $_SESSION['error_message'] = "This withdrawal cannot be cancelled. Status: " . $withdrawal['status'];
        header('Location: my-investments.php');
        exit();
    }
    
    // Delete the withdrawal request
    $deleteSQL = "DELETE FROM withdrawals WHERE withdrawal_id = ? AND client_id = ?";
    $deleteStmt = $conn->prepare($deleteSQL);
    $deleteStmt->bind_param("ii", $withdrawal_id, $client_id);
    
    if ($deleteStmt->execute()) {
        $_SESSION['success_message'] = "Withdrawal request cancelled successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to cancel withdrawal request. Please try again.";
    }
    
    $deleteStmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

header('Location: my-investments.php');
exit();
?>
