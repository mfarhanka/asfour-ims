<?php
/* c/upload-payment-proof.php - Handle payment proof upload from clients */

session_start();

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include '../config.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$investment_id = isset($_POST['investment_id']) ? intval($_POST['investment_id']) : 0;
$payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
$payment_date = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
$payment_notes = isset($_POST['payment_notes']) ? trim($_POST['payment_notes']) : '';

// Validation
$errors = [];

// Validate payment amount
if ($payment_amount <= 0) {
    $errors[] = "Payment amount must be greater than zero.";
}

// Validate payment date
if (!strtotime($payment_date)) {
    $errors[] = "Invalid payment date.";
}

// Validate investment ID and ownership
if ($investment_id <= 0) {
    $errors[] = "Invalid investment ID.";
} else {
    // Check if investment belongs to client and is eligible for payment
    $checkSQL = "SELECT ci.id, ci.status, ci.invested_amount, ci.remaining_amount, i.title 
                 FROM client_investments ci
                 JOIN investments i ON ci.investment_id = i.id
                 WHERE ci.id = ? AND ci.client_id = ? 
                 AND ci.status IN ('approved', 'payment_partial')";
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("ii", $investment_id, $client_id);
    $stmt->execute();
    
    $stmt->bind_result($inv_id, $inv_status, $invested_amount, $remaining_amount, $inv_title);
    
    if (!$stmt->fetch()) {
        $errors[] = "Investment not found or not eligible for payment upload.";
    } else {
        $investment_title = $inv_title;
        
        // Check if payment amount doesn't exceed remaining amount
        if ($payment_amount > $remaining_amount) {
            $errors[] = "Payment amount ($" . number_format($payment_amount, 2) . ") cannot exceed remaining amount ($" . number_format($remaining_amount, 2) . ").";
        }
    }
    $stmt->close();
}

// Handle file upload
$paymentProofFilename = null;
if (empty($errors) && isset($_FILES['payment_proof_file'])) {
    $file = $_FILES['payment_proof_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error. Please try again.";
    } else {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and PDF files are allowed.";
        }
        
        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds 5MB limit.";
        }
        
        // If validations pass, upload the file
        if (empty($errors)) {
            $uploadDir = __DIR__ . '/../uploads/payments/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $paymentProofFilename = 'payment_' . $client_id . '_' . $investment_id . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $paymentProofFilename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $errors[] = "Failed to save uploaded file. Please try again.";
                $paymentProofFilename = null;
            }
        }
    }
} else if (empty($errors)) {
    $errors[] = "No file uploaded. Please select a payment proof file.";
}

// Insert payment transaction if no errors
if (empty($errors) && $paymentProofFilename) {
    try {
        $conn->begin_transaction();
        
        // Insert payment transaction record
        $insertSQL = "INSERT INTO payment_transactions 
                     (client_investment_id, payment_amount, payment_proof, payment_date, payment_notes, status, uploaded_at) 
                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($insertSQL);
        $stmt->bind_param("idsss", $investment_id, $payment_amount, $paymentProofFilename, $payment_date, $payment_notes);
        
        if ($stmt->execute()) {
            $conn->commit();
            
            $_SESSION['success_message'] = "Payment of $" . number_format($payment_amount, 2) . " uploaded successfully! Your payment is now pending verification by our team.";
            header("Location: dashboard.php?page=my-investments");
            exit();
        } else {
            throw new Exception("Failed to insert payment transaction.");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Delete uploaded file if database insert failed
        if ($paymentProofFilename && file_exists($uploadDir . $paymentProofFilename)) {
            unlink($uploadDir . $paymentProofFilename);
        }
        
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header("Location: dashboard.php?page=my-investments");
    exit();
}
?>
