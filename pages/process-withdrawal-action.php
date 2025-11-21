<?php
/* pages/process-withdrawal-action.php - Process Withdrawal Approval/Rejection */

session_start();
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $withdrawal_id = intval($_POST['withdrawal_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    
    // Validate input
    if (empty($withdrawal_id) || empty($action)) {
        $_SESSION['error_message'] = "Invalid request parameters.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    // Get withdrawal details
    $withdrawalSQL = "SELECT withdrawal_id, status FROM withdrawals WHERE withdrawal_id = ?";
    $stmt = $conn->prepare($withdrawalSQL);
    $stmt->bind_param("i", $withdrawal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = "Withdrawal request not found.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    $withdrawal = $result->fetch_assoc();
    
    // Check status based on action
    if ($action == 'cancel') {
        // Allow cancellation for pending or approved status
        if (!in_array($withdrawal['status'], ['pending', 'approved'])) {
            $_SESSION['error_message'] = "This withdrawal cannot be cancelled.";
            header('Location: ../index.php?p=pending-withdrawals');
            exit();
        }
    } else {
        if ($withdrawal['status'] != 'pending') {
            $_SESSION['error_message'] = "This withdrawal request has already been processed.";
            header('Location: ../index.php?p=pending-withdrawals');
            exit();
        }
    }
    
    if ($action == 'approve') {
        // Approve withdrawal
        $updateSQL = "UPDATE withdrawals SET status = 'approved', processed_by = ?, processed_date = NOW() WHERE withdrawal_id = ?";
        $updateStmt = $conn->prepare($updateSQL);
        $updateStmt->bind_param("ii", $admin_id, $withdrawal_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Withdrawal request approved successfully. You can now upload transfer proof.";
        } else {
            $_SESSION['error_message'] = "Failed to approve withdrawal request.";
        }
        
    } elseif ($action == 'reject') {
        // Reject withdrawal
        $admin_notes = trim($_POST['admin_notes']);
        
        // Set default rejection reason if none provided
        if (empty($admin_notes)) {
            $admin_notes = 'No reason stated';
        }
        
        $updateSQL = "UPDATE withdrawals SET status = 'rejected', admin_notes = ?, processed_by = ?, processed_date = NOW() WHERE withdrawal_id = ?";
        $updateStmt = $conn->prepare($updateSQL);
        $updateStmt->bind_param("sii", $admin_notes, $admin_id, $withdrawal_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Withdrawal request rejected successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to reject withdrawal request.";
        }
    } elseif ($action == 'cancel') {
        // Cancel withdrawal (delete the record)
        $deleteSQL = "DELETE FROM withdrawals WHERE withdrawal_id = ?";
        $deleteStmt = $conn->prepare($deleteSQL);
        $deleteStmt->bind_param("i", $withdrawal_id);
        
        if ($deleteStmt->execute()) {
            $_SESSION['success_message'] = "Withdrawal request cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to cancel withdrawal request.";
        }
        $deleteStmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid action specified.";
    }
    
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

header('Location: ../index.php?p=pending-withdrawals');
exit();
?>
