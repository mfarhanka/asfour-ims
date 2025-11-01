<?php
/* c/debug_login.php - Debug login issues */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Login Debug Information</h2>";

try {
    echo "1. Session start test...<br>";
    session_start();
    echo "✅ Session started successfully<br>";
    
    echo "<br>2. Config file inclusion test...<br>";
    $config_path = __DIR__ . '/../config.php';
    echo "Config path: " . $config_path . "<br>";
    
    if (file_exists($config_path)) {
        echo "✅ Config file exists<br>";
        require_once $config_path;
        echo "✅ Config file included successfully<br>";
        
        echo "<br>3. Database connection test...<br>";
        if (isset($conn) && $conn instanceof mysqli) {
            echo "✅ Database connection object exists<br>";
            if ($conn->ping()) {
                echo "✅ Database connection is active<br>";
            } else {
                echo "❌ Database connection is not active<br>";
            }
        } else {
            echo "❌ Database connection object not found<br>";
        }
        
        echo "<br>4. Environment variables test...<br>";
        echo "IS_LOCAL_ENVIRONMENT: " . (defined('IS_LOCAL_ENVIRONMENT') ? (IS_LOCAL_ENVIRONMENT ? 'true' : 'false') : 'not defined') . "<br>";
        echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
        echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "<br>";
        
        echo "<br>5. File paths test...<br>";
        $paths_to_check = [
            '../vendors/bootstrap/dist/css/bootstrap.min.css',
            '../vendors/font-awesome/css/font-awesome.min.css',
            '../build/css/custom.min.css',
            '../vendors/jquery/dist/jquery.min.js'
        ];
        
        foreach ($paths_to_check as $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                echo "✅ " . $path . " exists<br>";
            } else {
                echo "❌ " . $path . " missing<br>";
            }
        }
        
    } else {
        echo "❌ Config file not found at: " . $config_path . "<br>";
    }
    
    echo "<br>6. PHP version and extensions...<br>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "MySQLi Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
    echo "Session Extension: " . (extension_loaded('session') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal Error occurred: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<br><a href='login.php'>Back to Login</a>";
?>