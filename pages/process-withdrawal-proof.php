<?php
/* pages/process-withdrawal-proof.php - Upload Transfer Proof and Complete Withdrawal */

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
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // Validate input
    if (empty($withdrawal_id)) {
        $_SESSION['error_message'] = "Invalid withdrawal ID.";
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
    
    if ($withdrawal['status'] != 'approved') {
        $_SESSION['error_message'] = "Only approved withdrawals can receive proof upload.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    // Handle file upload
    if (!isset($_FILES['withdrawal_proof']) || $_FILES['withdrawal_proof']['error'] == UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Please upload a transfer proof file.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    $file = $_FILES['withdrawal_proof'];
    
    // Validate file
    if ($file['error'] != UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "File upload error. Please try again.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "File size exceeds 5MB limit.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
        header('Location: ../index.php?p=pending-withdrawals');
        exit();
    }
    
    // Create upload directory if not exists
    $uploadDir = '../uploads/withdrawals/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'withdrawal_' . $withdrawal_id . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update withdrawal record
        $updateSQL = "UPDATE withdrawals 
                      SET status = 'completed', 
                          withdrawal_proof = ?, 
                          admin_notes = ?,
                          processed_by = ?,
                          processed_date = NOW() 
                      WHERE withdrawal_id = ?";
        
        $updateStmt = $conn->prepare($updateSQL);
        $updateStmt->bind_param("ssii", $filename, $admin_notes, $admin_id, $withdrawal_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Transfer proof uploaded successfully! Withdrawal marked as completed.";
        } else {
            // Delete uploaded file if database update fails
            unlink($uploadPath);
            $_SESSION['error_message'] = "Failed to update withdrawal record.";
        }
        
        $updateStmt->close();
    } else {
        $_SESSION['error_message'] = "Failed to upload file. Please try again.";
    }
    
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

header('Location: ../index.php?p=pending-withdrawals');
exit();
?>
