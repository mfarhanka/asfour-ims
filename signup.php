<?php
session_start();
require_once __DIR__ . '/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'client') {
        header('Location: c/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$success_message = '';
$error_message = '';

// Handle signup form submission
if ($_POST && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['phone']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters long.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[+]?[0-9\s\-\(\)]+$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Password confirmation does not match.';
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username is already taken. Please choose a different username.';
        }
        $stmt->close();
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email address is already registered. Please use a different email or login.';
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new client
            $stmt = $conn->prepare("INSERT INTO clients (name, email, phone, username, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $username, $hashed_password);
            
            if ($stmt->execute()) {
                $success_message = 'Registration submitted successfully! Your account is pending admin approval. You will be contacted once your account is approved and you can then login.';
                // Clear form data
                $_POST = [];
            } else {
                $error_message = 'Error creating account. Please try again.';
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = 'Error creating account: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
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

    <title>AIMS - Client Registration | Sign Up</title>

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
              <h1>Client Registration</h1>
              <p>Create your investment account</p>
              <div class="alert alert-info" style="text-align: left; margin-bottom: 20px;">
                <i class="fa fa-info-circle"></i> 
                <strong>Note:</strong> Your account will need admin approval before you can login and start investing.
              </div>
              
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                  <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                  <br><br>
                  <a href="login.php" class="btn btn-primary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Login
                  </a>
                </div>
              <?php endif; ?>
              
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                  <i class="fa fa-exclamation-triangle"></i> <?= $error_message ?>
                </div>
              <?php endif; ?>
              
              <?php if (empty($success_message)): ?>
              <div>
                <input type="text" class="form-control" name="name" placeholder="Full Name" required="" 
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                       maxlength="100" />
              </div>
              <div>
                <input type="email" class="form-control" name="email" placeholder="Email Address" required="" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                       maxlength="100" />
              </div>
              <div>
                <input type="tel" class="form-control" name="phone" placeholder="Phone Number" required="" 
                       value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" 
                       maxlength="20" />
              </div>
              <div>
                <input type="text" class="form-control" name="username" placeholder="Username" required="" 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                       maxlength="50" pattern="[a-zA-Z0-9_]+" 
                       title="Username can only contain letters, numbers, and underscores" />
              </div>
              <div>
                <input type="password" class="form-control" name="password" placeholder="Password (minimum 6 characters)" 
                       required="" minlength="6" />
              </div>
              <div>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" 
                       required="" minlength="6" />
              </div>
              <div>
                <button type="submit" class="btn btn-default submit">
                  <i class="fa fa-user-plus"></i> Create Account
                </button>
              </div>
              <?php endif; ?>

              <div class="clearfix"></div>

              <div class="separator">
                <p class="change_link">
                  Already have an account? 
                  <a href="login.php" class="to_register"> Sign In </a>
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
    
    <script>
    $(document).ready(function() {
        // Form validation
        $('form').on('submit', function(e) {
            var password = $('input[name="password"]').val();
            var confirmPassword = $('input[name="confirm_password"]').val();
            
            if (password !== confirmPassword) {
                alert('Password confirmation does not match');
                e.preventDefault();
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return false;
            }
            
            var username = $('input[name="username"]').val();
            var usernamePattern = /^[a-zA-Z0-9_]+$/;
            if (!usernamePattern.test(username)) {
                alert('Username can only contain letters, numbers, and underscores');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // Real-time password confirmation validation
        $('input[name="confirm_password"]').on('keyup', function() {
            var password = $('input[name="password"]').val();
            var confirmPassword = $(this).val();
            
            if (confirmPassword && password !== confirmPassword) {
                $(this).css('border-color', '#e74c3c');
            } else if (confirmPassword) {
                $(this).css('border-color', '#27ae60');
            } else {
                $(this).css('border-color', '');
            }
        });
        
        // Username validation
        $('input[name="username"]').on('keyup', function() {
            var username = $(this).val();
            var usernamePattern = /^[a-zA-Z0-9_]+$/;
            
            if (username && !usernamePattern.test(username)) {
                $(this).css('border-color', '#e74c3c');
            } else if (username) {
                $(this).css('border-color', '#27ae60');
            } else {
                $(this).css('border-color', '');
            }
        });
        
        // Auto-dismiss success message after 10 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut();
        }, 10000);
    });
    </script>
  </body>
</html>