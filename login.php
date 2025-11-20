<?php
session_start();
require_once __DIR__ . '/config.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'client') {
        header('Location: c/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error_message = '';

// Check for error parameter from URL
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'account_not_approved':
            $error_message = 'Your account is no longer approved. Please contact administrator.';
            break;
        case 'account_suspended':
            $error_message = 'Your account has been suspended. Please contact administrator for assistance.';
            break;
    }
}

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (!empty($username) && !empty($password)) {
        // Check for hardcoded superadmin
        if ($username === 'farhan' && $password === 'kamar123') {
            // Superadmin login successful
            $_SESSION['user_id'] = 0; // Special ID for superadmin
            $_SESSION['username'] = 'farhan';
            $_SESSION['name'] = 'Super Administrator';
            $_SESSION['user_type'] = 'superadmin';
            
            header('Location: index.php');
            exit();
        }
        
        // Check admin login
        $stmt = $conn->prepare("SELECT id, username, password, name FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['user_type'] = 'admin';
                
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            // Check client login
            $stmt = $conn->prepare("SELECT id, username, password, name, status, suspension_reason, suspension_end_date FROM clients WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Check if account is approved
                    if ($user['status'] === 'pending') {
                        $error_message = 'Your account is pending admin approval. Please wait for approval before logging in.';
                    } elseif ($user['status'] === 'rejected') {
                        $error_message = 'Your account has been rejected. Please contact administrator for more information.';
                    } elseif ($user['status'] === 'suspended') {
                        $suspension_msg = 'Your account has been suspended.';
                        if (!empty($user['suspension_reason'])) {
                            $suspension_msg .= ' Reason: ' . htmlspecialchars($user['suspension_reason']);
                        }
                        if (!empty($user['suspension_end_date'])) {
                            $end_date = date('F j, Y', strtotime($user['suspension_end_date']));
                            $suspension_msg .= ' Suspension ends on: ' . $end_date;
                        }
                        $suspension_msg .= ' Please contact administrator for assistance.';
                        $error_message = $suspension_msg;
                    } elseif ($user['status'] === 'approved') {
                        // Login successful
                        $_SESSION['client_id'] = $user['id'];
                        $_SESSION['client_username'] = $user['username'];
                        $_SESSION['client_name'] = $user['name'];
                        $_SESSION['user_type'] = 'client';
                        
                        header('Location: c/dashboard.php');
                        exit();
                    } else {
                        $error_message = 'Account status unknown. Please contact administrator.';
                    }
                } else {
                    $error_message = 'Invalid username or password.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
        }
        $stmt->close();
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
    <link rel="icon" href="images/favicon.ico" type="image/ico" />

    <title>AIMS - Asfour Investment Management System | Login</title>

    <!-- Bootstrap -->
    <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- Animate.css -->
    <link href="vendors/animate.css/animate.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="build/css/custom.min.css" rel="stylesheet">
  </head>

  <body class="login">
    <div>
      <div class="login_wrapper">
        <div class="animate form login_form">
          <section class="login_content">
            <form method="post" action="">
              <h1>AIMS Login</h1>
              
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
                  New client? 
                  <a href="signup.php" class="to_register"> Create Account </a>
                </p>
                
                <div class="clearfix"></div>
                <br />

                <div>
                  <h1><i class="fa fa-paw"></i> AIMS</h1>
                  <p>©<?= date('Y') ?> Asfour Investment Management System. All Rights Reserved.</p>
                </div>
              </div>
            </form>
          </section>
        </div>
      </div>
    </div>

    <!-- jQuery -->
    <script src="vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="vendors/nprogress/nprogress.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="build/js/custom.min.js"></script>
  </body>
</html>