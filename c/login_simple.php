<?php
/* c/login_simple.php - Simplified Client Login for debugging */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();
    
    // Try to include config
    $config_path = __DIR__ . '/../config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    } else {
        die("Config file not found at: " . $config_path);
    }
    
    // If client is already logged in, redirect to dashboard
    if (isset($_SESSION['client_id'])) {
        header('Location: dashboard.php');
        exit();
    }

    $error_message = '';

    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        if (!empty($username) && !empty($password)) {
            // Check if database connection exists
            if (!isset($conn) || !$conn instanceof mysqli) {
                $error_message = 'Database connection error.';
            } else {
                // Check client login
                $stmt = $conn->prepare("SELECT id, username, password, name FROM clients WHERE username = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $client = $result->fetch_assoc();
                        
                        if (password_verify($password, $client['password'])) {
                            // Login successful
                            $_SESSION['client_id'] = $client['id'];
                            $_SESSION['client_username'] = $client['username'];
                            $_SESSION['client_name'] = $client['name'];
                            $_SESSION['user_type'] = 'client';
                            
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            $error_message = 'Invalid username or password.';
                        }
                    } else {
                        $error_message = 'No account found with that username.';
                    }
                    $stmt->close();
                } else {
                    $error_message = 'Database query error: ' . $conn->error;
                }
            }
        } else {
            $error_message = 'Please enter both username and password.';
        }
    }

} catch (Exception $e) {
    $error_message = 'System error: ' . $e->getMessage();
} catch (Error $e) {
    $error_message = 'Fatal error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Portal - Simple Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .login-form { max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .alert { padding: 10px; margin-bottom: 15px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Client Portal Login (Debug Version)</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <input type="text" class="form-control" name="username" placeholder="Username" required 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" />
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password" required />
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Log in</button>
            </div>
        </form>
        
        <p><a href="debug_login.php">Debug Information</a> | <a href="login.php">Full Login</a></p>
    </div>
</body>
</html>