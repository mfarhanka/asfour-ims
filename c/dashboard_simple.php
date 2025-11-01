<?php
/* c/dashboard_simple.php - Simple Client Dashboard */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

// Include config
require_once __DIR__ . '/../config.php';

$client_id = $_SESSION['client_id'];

try {
    // Get client information
    $clientSQL = "SELECT name, username, email FROM clients WHERE id = ?";
    $clientStmt = $conn->prepare($clientSQL);
    $clientStmt->bind_param("i", $client_id);
    $clientStmt->execute();
    $clientInfo = $clientStmt->get_result()->fetch_assoc();

    // Get client's investment statistics
    $statsSQL = "SELECT 
        COUNT(DISTINCT ci.investment_id) as total_projects_invested,
        COALESCE(SUM(ci.invested_amount), 0) as total_invested_amount,
        COUNT(ci.id) as total_investments
    FROM client_investments ci
    WHERE ci.client_id = ?";

    $statsStmt = $conn->prepare($statsSQL);
    $statsStmt->bind_param("i", $client_id);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Dashboard - Simple Version</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #007bff; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 14px; }
        .nav-menu { margin: 20px 0; }
        .nav-menu a { display: inline-block; margin: 5px 10px 5px 0; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .nav-menu a:hover { background: #0056b3; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?= htmlspecialchars($clientInfo['name'] ?? $_SESSION['client_name'] ?? 'Client') ?></h1>
            <p>Client Portal Dashboard</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="nav-menu">
            <a href="available-projects.php">Browse Projects</a>
            <a href="my-investments.php">My Investments</a>
            <a href="dashboard.php">Full Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>

        <h2>Investment Overview</h2>
        
        <?php if (isset($stats)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_projects_invested'] ?? 0) ?></div>
                    <div class="stat-label">Projects Invested</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?= number_format($stats['total_invested_amount'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Invested</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_investments'] ?? 0) ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
            </div>
        <?php else: ?>
            <div class="error">Unable to load investment statistics.</div>
        <?php endif; ?>

        <h2>Account Information</h2>
        
        <?php if (isset($clientInfo)): ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($clientInfo['name'] ?? 'N/A') ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars($clientInfo['username'] ?? 'N/A') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($clientInfo['email'] ?? 'N/A') ?></p>
        <?php else: ?>
            <div class="error">Unable to load account information.</div>
        <?php endif; ?>

        <h2>Quick Actions</h2>
        <div class="nav-menu">
            <a href="available-projects.php">View Available Projects</a>
            <a href="my-investments.php">View My Investments</a>
            <a href="dashboard_debug.php">Debug Information</a>
        </div>

        <hr>
        <p><small>Simple Dashboard Version | <a href="dashboard.php">Switch to Full Dashboard</a></small></p>
    </div>
</body>
</html>