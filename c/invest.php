<?php
/* c/invest.php - Process Investment Submission */

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
    header("Location: available-projects.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$investment_id = isset($_POST['investment_id']) ? intval($_POST['investment_id']) : 0;
$investment_amount = isset($_POST['investment_amount']) ? floatval($_POST['investment_amount']) : 0;
$investment_date = isset($_POST['investment_date']) ? $_POST['investment_date'] : date('Y-m-d');

// Validation
$errors = [];

// Validate investment ID
if ($investment_id <= 0) {
    $errors[] = "Invalid project selected.";
}

// Validate investment amount (minimum $100)
if ($investment_amount < 100) {
    $errors[] = "Minimum investment amount is $100.";
}

// Validate investment date
if (!strtotime($investment_date)) {
    $errors[] = "Invalid investment date.";
}

// Check if investment project exists and is available
if (empty($errors)) {
    $checkProjectSQL = "SELECT id, title, total_goal, start_date, end_date 
                       FROM investments 
                       WHERE id = ? AND (start_date > NOW() OR (start_date <= NOW() AND end_date >= NOW()))";
    $stmt = $conn->prepare($checkProjectSQL);
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();
    
    // Use bind_result for compatibility
    $stmt->bind_result($proj_id, $proj_title, $proj_goal, $proj_start, $proj_end);
    
    if ($stmt->fetch()) {
        $project = [
            'id' => $proj_id,
            'title' => $proj_title,
            'total_goal' => $proj_goal,
            'start_date' => $proj_start,
            'end_date' => $proj_end
        ];
        $stmt->close();
    } else {
        $stmt->close();
        $errors[] = "Selected project is not available for investment.";
    }
}

// Check if client has already invested in this project
if (empty($errors)) {
    $checkExistingSQL = "SELECT id FROM client_investments WHERE client_id = ? AND investment_id = ?";
    $stmt = $conn->prepare($checkExistingSQL);
    $stmt->bind_param("ii", $client_id, $investment_id);
    $stmt->execute();
    
    // Use bind_result for compatibility
    $stmt->bind_result($existing_id);
    
    if ($stmt->fetch()) {
        $errors[] = "You have already invested in this project.";
    }
    $stmt->close();
}

// If no errors, process the investment
if (empty($errors)) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert investment record with 'pending' status
        $insertSQL = "INSERT INTO client_investments 
                     (client_id, investment_id, invested_amount, investment_date, status, created_at) 
                     VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($insertSQL);
        $stmt->bind_param("iids", $client_id, $investment_id, $investment_amount, $investment_date);
        
        if ($stmt->execute()) {
            $investment_record_id = $conn->insert_id;
            
            // Commit transaction
            $conn->commit();
            
            // Set success message
            $_SESSION['success_message'] = "Investment submitted successfully! Your investment of $" . number_format($investment_amount, 2) . " in '" . htmlspecialchars($project['title']) . "' is now pending approval.";
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
            
        } else {
            throw new Exception("Failed to process investment: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $_SESSION['error_messages'] = $errors;
    header("Location: available-projects.php");
    exit();
}
?>