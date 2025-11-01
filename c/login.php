<?php
/* c/login.php - Client Login */

// Error handling for debugging (remove in production)
if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    session_start();
    
    // Check if config file exists before including
    $config_path = __DIR__ . '/../config.php';
    if (!file_exists($config_path)) {
        die('Configuration file not found. Please check your installation.');
    }
    
    require_once $config_path;
    
    // Check if database connection is available
    if (!isset($conn) || !($conn instanceof mysqli)) {
        die('Database connection not available. Please check your configuration.');
    }
    
} catch (Exception $e) {
    if (isset($_GET['debug'])) {
        die('Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    } else {
        die('System temporarily unavailable. Please try again later.');
    }
} catch (Error $e) {
    if (isset($_GET['debug'])) {
        die('Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    } else {
        die('System temporarily unavailable. Please try again later.');
    }
}

// If client is already logged in, redirect to dashboard
if (isset($_SESSION['client_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (!empty($username) && !empty($password)) {
        // Check client login
        $stmt = $conn->prepare("SELECT id, username, password, name FROM clients WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        // Use bind_result for compatibility with servers without mysqlnd
        $stmt->bind_result($client_id, $client_username, $client_password, $client_name);
        
        if ($stmt->fetch()) {
            $client = [
                'id' => $client_id,
                'username' => $client_username,
                'password' => $client_password,
                'name' => $client_name
            ];
            $stmt->close();
            
            if (password_verify($password, $client['password'])) {
                // Login successful
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_username'] = $client['username'];
                $_SESSION['client_name'] = $client['name'];
                $_SESSION['user_type'] = 'client';
                
                // Redirect to emergency dashboard until main dashboard is fixed
                header('Location: emergency_dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            $error_message = 'No account found with that username. Please contact your administrator.';
        }
    } else {
        $error_message = 'Please enter both username and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../images/favicon.ico" type="image/ico" />

    <title>Client Portal - Asfour Investment Management System | Login</title>

    <?php 
    // Define base path for assets
    $base_path = (IS_LOCAL_ENVIRONMENT ?? true) ? '../' : '../';
    ?>
    <!-- Bootstrap -->
    <link href="<?= $base_path ?>vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="<?= $base_path ?>vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="<?= $base_path ?>vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="<?= $base_path ?>vendors/animate.css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="<?= $base_path ?>build/css/custom.min.css" rel="stylesheet">
  </head>

  <body class="login">
    <div>
      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
            <form method="post" action="">
              <h1>Client Portal</h1>
              
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                  <?= htmlspecialchars($error_message) ?>
                </div>
              <?php endif; ?>
              
              <div>
                <input type="text" class="form-control" name="username" placeholder="Username" required="" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" />
              </div>
              <div>
                <input type="password" class="form-control" name="password" placeholder="Password" required="" />
              </div>
              <div>
                <button type="submit" class="btn btn-default submit">Log in</button>
              </div>

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">
                  <a href="../login.php" class="to_register"> Go to Admin Portal </a>
                </p>
                
                <div class="clearfix"></div>
                <br />

                <div>
                  <h1><i class="fa fa-user"></i> Client Portal</h1>
                  <p>©<?= date('Y') ?> Asfour Investment Management System. All Rights Reserved.</p>
                </div>
              </div>
            </form>
          </section>
        </div>
      </div>
    </div>

    <!-- jQuery -->
    <script src="<?= $base_path ?>vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="<?= $base_path ?>vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="<?= $base_path ?>vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="<?= $base_path ?>vendors/nprogress/nprogress.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="<?= $base_path ?>build/js/custom.min.js"></script>
  </body>
</html>