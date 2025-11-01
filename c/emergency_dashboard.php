<?php
/* c/emergency_dashboard.php - Emergency Dashboard with Minimal Dependencies */

// Start with basic error handling
ini_set('display_errors', 0); // Hide errors from users
error_reporting(E_ALL);

session_start();

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

// Basic HTML with inline CSS - no external dependencies
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Dashboard - Emergency Mode</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .header { background: #007bff; color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .welcome { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .nav-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; display: inline-block; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .alert { padding: 15px; border-radius: 4px; margin: 10px 0; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #007bff; margin-bottom: 5px; }
        .stat-label { color: #666; }
        .footer { margin-top: 40px; padding: 20px; text-align: center; color: #666; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Client Portal - Emergency Mode</h1>
            <p>Welcome, <?= htmlspecialchars($_SESSION['client_name'] ?? 'Client') ?></p>
        </div>
    </div>

    <div class="container">
        <div class="welcome">
            <h2>Dashboard Status</h2>
            <div class="alert alert-info">
                <strong>Emergency Mode Active:</strong> The full dashboard is temporarily unavailable. 
                This simplified version provides basic navigation while the issue is resolved.
            </div>
        </div>

        <div class="nav-buttons">
            <a href="available-projects.php" class="btn btn-success">Browse Available Projects</a>
            <a href="my-investments.php" class="btn">View My Investments</a>
            <a href="dashboard.php" class="btn btn-warning">Try Full Dashboard</a>
            <a href="dashboard_debug.php" class="btn btn-warning">Debug Information</a>
            <a href="dashboard_simple.php" class="btn btn-warning">Simple Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <?php
        // Try to get basic stats safely
        try {
            require_once __DIR__ . '/../config.php';
            
            if (isset($conn) && $conn instanceof mysqli) {
                $client_id = $_SESSION['client_id'];
                
                // Simple query without complex joins
                $quickStatsSQL = "SELECT COUNT(*) as investment_count FROM client_investments WHERE client_id = ?";
                $quickStmt = $conn->prepare($quickStatsSQL);
                
                if ($quickStmt) {
                    $quickStmt->bind_param("i", $client_id);
                    $quickStmt->execute();
                    $quickResult = $quickStmt->get_result()->fetch_assoc();
                    $investmentCount = $quickResult['investment_count'] ?? 0;
                } else {
                    $investmentCount = 'N/A';
                }
            } else {
                $investmentCount = 'N/A';
            }
        } catch (Exception $e) {
            $investmentCount = 'Error';
        }
        ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $investmentCount ?></div>
                <div class="stat-label">Total Investments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Active</div>
                <div class="stat-label">Account Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= date('M Y') ?></div>
                <div class="stat-label">Current Period</div>
            </div>
        </div>

        <div class="welcome">
            <h3>Quick Actions</h3>
            <p>Use the buttons above to navigate to different sections of the client portal.</p>
            
            <div class="alert alert-warning">
                <strong>Technical Note:</strong> If you continue experiencing issues, please contact support. 
                You can also try accessing the <a href="dashboard_simple.php">Simple Dashboard</a> which uses 
                minimal resources and should work reliably.
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> Asfour Investment Management System</p>
            <p><small>Emergency Dashboard Mode | Session ID: <?= substr(session_id(), 0, 8) ?></small></p>
        </div>
    </div>
</body>
</html>