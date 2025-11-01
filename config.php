<?php
// config.php

// Environment Detection and Configuration
// Set to true for local development, false for server/production
// define('IS_LOCAL_ENVIRONMENT', true);  // Manual setting - commented out for auto-detection

// Auto-detect environment based on server name or IP
define('IS_LOCAL_ENVIRONMENT', (
    isset($_SERVER['HTTP_HOST']) && (
        $_SERVER['HTTP_HOST'] === 'localhost' || 
        $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
        strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
    )
) || (
    isset($_SERVER['SERVER_NAME']) && (
        $_SERVER['SERVER_NAME'] === 'localhost' || 
        $_SERVER['SERVER_NAME'] === '127.0.0.1'
    )
) || (
    isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] === '127.0.0.1'
));

if (IS_LOCAL_ENVIRONMENT) {
    // Local Development Database Settings (XAMPP/WAMP/MAMP)
    $host       = "localhost";
    $db_user    = "root";
    $db_pass    = "";
    $db_name    = "asfour-ims";
    $port       = 3306;
    
    // Local development settings
    $base_url   = "http://localhost/asfour-ims-v1.1/";
    $upload_path = $_SERVER['DOCUMENT_ROOT'] . "/asfour-ims-v1.1/uploads/";
    
    // Enable error reporting for development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
} else {
    // Server/Production Database Settings
    $host       = "localhost";
    $db_user    = "dzvisual_admin";
    $db_pass    = "Farhan@123456";
    $db_name    = "dzvisual_asfour";
    $port       = 3306;
    
    // Production server settings
    $base_url   = "https://dzvisuals.com/asfour/";  // Update with actual domain
    $upload_path = "/home/dzvisual/public_html/asfour/uploads/";  // Server upload path
    
    // Disable error reporting for production
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name, $port);

// Check connection
if ($conn->connect_error) {
    if (IS_LOCAL_ENVIRONMENT) {
        die("Database Connection failed: " . $conn->connect_error);
    } else {
        // Log error in production instead of displaying it
        error_log("Database Connection failed: " . $conn->connect_error);
        die("Database connection error. Please contact administrator.");
    }
}

// Set charset to avoid issues with special characters
$conn->set_charset("utf8mb4");

// Start session (if your system needs login/session handling)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set timezone based on environment with error handling
try {
    if (IS_LOCAL_ENVIRONMENT) {
        date_default_timezone_set("Asia/Kuala_Lumpur");  // Local timezone
    } else {
        date_default_timezone_set("UTC");                 // Server timezone (or change as needed)
    }
} catch (Exception $e) {
    // Fallback to UTC if timezone setting fails
    date_default_timezone_set("UTC");
    if (IS_LOCAL_ENVIRONMENT) {
        error_log("Timezone setting failed: " . $e->getMessage());
    }
}

// Additional Configuration Constants
define('ENVIRONMENT', IS_LOCAL_ENVIRONMENT ? 'development' : 'production');
define('BASE_URL', $base_url);
define('UPLOAD_PATH', $upload_path);

// File upload settings
define('MAX_UPLOAD_SIZE', IS_LOCAL_ENVIRONMENT ? '10M' : '5M');
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Debug function (only works in local environment)
function debug($data) {
    if (IS_LOCAL_ENVIRONMENT) {
        echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ccc; margin: 10px 0;">';
        print_r($data);
        echo '</pre>';
    }
}

// Environment debugging (remove after confirming it works)
if (isset($_GET['debug_env'])) {
    echo "<h3>Environment Debug Info:</h3>";
    echo "IS_LOCAL_ENVIRONMENT: " . (IS_LOCAL_ENVIRONMENT ? 'true' : 'false') . "<br>";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "<br>";
    echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'not set') . "<br>";
    echo "ENVIRONMENT: " . ENVIRONMENT . "<br>";
    echo "Database Host: " . $host . "<br>";
    echo "Database User: " . $db_user . "<br>";
    echo "Database Name: " . $db_name . "<br>";
    exit();
}
?>
