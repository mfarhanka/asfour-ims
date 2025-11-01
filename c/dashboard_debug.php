<?php
/* c/dashboard_debug.php - Debug version of Client Dashboard */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Dashboard Debug Information</h2>";

try {
    session_start();
    echo "✅ Session started<br>";
    
    // Check session data
    echo "<h3>Session Data:</h3>";
    if (isset($_SESSION['client_id'])) {
        echo "Client ID: " . $_SESSION['client_id'] . "<br>";
        echo "Client Username: " . ($_SESSION['client_username'] ?? 'not set') . "<br>";
        echo "Client Name: " . ($_SESSION['client_name'] ?? 'not set') . "<br>";
        echo "User Type: " . ($_SESSION['user_type'] ?? 'not set') . "<br>";
    } else {
        echo "❌ No client session found<br>";
        echo "<a href='login.php'>Login</a><br>";
        exit();
    }
    
    // Check config
    echo "<h3>Configuration Test:</h3>";
    $config_path = __DIR__ . '/../config.php';
    if (file_exists($config_path)) {
        echo "✅ Config file exists<br>";
        require_once $config_path;
        echo "✅ Config file loaded<br>";
    } else {
        echo "❌ Config file not found at: " . $config_path . "<br>";
        exit();
    }
    
    // Check database connection
    echo "<h3>Database Test:</h3>";
    if (isset($conn) && $conn instanceof mysqli) {
        echo "✅ Database connection object exists<br>";
        if ($conn->ping()) {
            echo "✅ Database connection is active<br>";
        } else {
            echo "❌ Database connection is not active<br>";
            exit();
        }
    } else {
        echo "❌ Database connection not available<br>";
        exit();
    }
    
    // Test client query
    echo "<h3>Client Data Test:</h3>";
    $client_id = $_SESSION['client_id'];
    $clientSQL = "SELECT name, username, email FROM clients WHERE id = ?";
    $clientStmt = $conn->prepare($clientSQL);
    
    if ($clientStmt) {
        echo "✅ Client query prepared<br>";
        $clientStmt->bind_param("i", $client_id);
        $clientStmt->execute();
        $clientInfo = $clientStmt->get_result()->fetch_assoc();
        
        if ($clientInfo) {
            echo "✅ Client data retrieved<br>";
            echo "Name: " . ($clientInfo['name'] ?? 'not set') . "<br>";
            echo "Username: " . ($clientInfo['username'] ?? 'not set') . "<br>";
            echo "Email: " . ($clientInfo['email'] ?? 'not set') . "<br>";
        } else {
            echo "❌ No client data found for ID: " . $client_id . "<br>";
        }
    } else {
        echo "❌ Failed to prepare client query: " . $conn->error . "<br>";
    }
    
    // Test investment stats query
    echo "<h3>Investment Stats Test:</h3>";
    $statsSQL = "SELECT 
        COUNT(DISTINCT ci.investment_id) as total_projects_invested,
        COALESCE(SUM(ci.invested_amount), 0) as total_invested_amount,
        COUNT(ci.id) as total_investments
    FROM client_investments ci
    WHERE ci.client_id = ?";
    
    $statsStmt = $conn->prepare($statsSQL);
    if ($statsStmt) {
        echo "✅ Stats query prepared<br>";
        $statsStmt->bind_param("i", $client_id);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        
        if ($stats) {
            echo "✅ Stats data retrieved<br>";
            echo "Total Projects: " . ($stats['total_projects_invested'] ?? 0) . "<br>";
            echo "Total Invested: $" . number_format($stats['total_invested_amount'] ?? 0, 2) . "<br>";
            echo "Total Investments: " . ($stats['total_investments'] ?? 0) . "<br>";
        } else {
            echo "❌ No stats data found<br>";
        }
    } else {
        echo "❌ Failed to prepare stats query: " . $conn->error . "<br>";
    }
    
    // Test file paths
    echo "<h3>File Path Tests:</h3>";
    $files_to_check = [
        __DIR__ . '/layouts/main.php' => 'Main layout',
        __DIR__ . '/pages/dashboard.php' => 'Dashboard page',
        __DIR__ . '/partials/head.php' => 'Head partial',
        __DIR__ . '/partials/sidebar.php' => 'Sidebar partial',
        __DIR__ . '/partials/header.php' => 'Header partial',
        __DIR__ . '/partials/footer.php' => 'Footer partial'
    ];
    
    foreach ($files_to_check as $file => $description) {
        if (file_exists($file)) {
            echo "✅ " . $description . " exists<br>";
        } else {
            echo "❌ " . $description . " missing: " . $file . "<br>";
        }
    }
    
    // Test asset paths
    echo "<h3>Asset Path Tests:</h3>";
    $assets_to_check = [
        '../vendors/bootstrap/dist/css/bootstrap.min.css' => 'Bootstrap CSS',
        '../vendors/font-awesome/css/font-awesome.min.css' => 'Font Awesome CSS',
        '../build/css/custom.min.css' => 'Custom CSS',
        '../vendors/jquery/dist/jquery.min.js' => 'jQuery JS'
    ];
    
    foreach ($assets_to_check as $asset => $description) {
        $full_path = __DIR__ . '/' . $asset;
        if (file_exists($full_path)) {
            echo "✅ " . $description . " exists<br>";
        } else {
            echo "❌ " . $description . " missing: " . $full_path . "<br>";
        }
    }
    
    echo "<h3>Test Links:</h3>";
    echo "<a href='dashboard.php'>Try Full Dashboard</a><br>";
    echo "<a href='dashboard_simple.php'>Try Simple Dashboard</a><br>";
    echo "<a href='login.php'>Back to Login</a><br>";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>