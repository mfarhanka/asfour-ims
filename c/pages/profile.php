<?php
/* c/pages/profile.php - Client Profile Page */

// Ensure user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: ../login.php');
    exit();
}

$client_id = $_SESSION['client_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $account_holder = trim($_POST['account_holder'] ?? '');
    $iban_swift = trim($_POST['iban_swift'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone number is required';
    
    // Check if email is already taken by another client
    $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $client_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email is already registered to another account';
    }
    $stmt->close();

    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM clients WHERE id = ?");
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            }
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Password confirmation does not match';
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Update basic profile information
            $update_profile = "UPDATE clients SET name = ?, email = ?, phone = ?";
            $params = [$name, $email, $phone];
            $types = "sss";
            
            // Add password to update if provided
            if (!empty($new_password)) {
                $update_profile .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                $types .= "s";
            }
            
            $update_profile .= " WHERE id = ?";
            $params[] = $client_id;
            $types .= "i";

            $stmt = $conn->prepare($update_profile);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();

            // Handle bank account information
            // Check if bank info record exists
            $stmt = $conn->prepare("SELECT id FROM client_bank_info WHERE client_id = ?");
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $bank_exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($bank_exists) {
                // Update existing bank info
                $stmt = $conn->prepare("UPDATE client_bank_info SET bank_name = ?, account_number = ?, account_holder = ?, iban_swift = ? WHERE client_id = ?");
                $stmt->bind_param("ssssi", $bank_name, $account_number, $account_holder, $iban_swift, $client_id);
            } else {
                // Insert new bank info
                $stmt = $conn->prepare("INSERT INTO client_bank_info (client_id, bank_name, account_number, account_holder, iban_swift) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $client_id, $bank_name, $account_number, $account_holder, $iban_swift);
            }
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            // Update session data
            $_SESSION['client_name'] = $name;
            
            $success_message = 'Profile updated successfully!';
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Fetch current client data
$stmt = $conn->prepare("
    SELECT c.*, 
           cbi.bank_name, cbi.account_number, cbi.account_holder, cbi.iban_swift
    FROM clients c 
    LEFT JOIN client_bank_info cbi ON c.id = cbi.client_id 
    WHERE c.id = ?
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-user"></i> My Profile</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-check"></i> <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fa fa-exclamation-triangle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" class="form-horizontal form-label-left">
                    
                    <!-- Personal Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fa fa-user"></i> Personal Information</h4>
                            
                            <div class="form-group">
                                <label class="control-label">Full Name <span class="required">*</span></label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?= htmlspecialchars($client_data['name'] ?? '') ?>" 
                                       required maxlength="100">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Email Address <span class="required">*</span></label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($client_data['email'] ?? '') ?>" 
                                       required maxlength="100">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Phone Number <span class="required">*</span></label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?= htmlspecialchars($client_data['phone'] ?? '') ?>" 
                                       required maxlength="20">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Username</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($client_data['username'] ?? '') ?>" 
                                       disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                        </div>

                        <!-- Bank Account Information -->
                        <div class="col-md-6">
                            <h4><i class="fa fa-bank"></i> Bank Account Information</h4>
                            <small class="text-muted">Required for withdrawal processing</small>
                            
                            <div class="form-group">
                                <label class="control-label">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" 
                                       value="<?= htmlspecialchars($client_data['bank_name'] ?? '') ?>" 
                                       maxlength="100" placeholder="e.g., Bank of America, JPMorgan Chase">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Account Number</label>
                                <input type="text" class="form-control" name="account_number" 
                                       value="<?= htmlspecialchars($client_data['account_number'] ?? '') ?>" 
                                       maxlength="50" placeholder="Your bank account number">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Account Holder Name</label>
                                <input type="text" class="form-control" name="account_holder" 
                                       value="<?= htmlspecialchars($client_data['account_holder'] ?? '') ?>" 
                                       maxlength="100" placeholder="Name as it appears on bank account">
                            </div>

                            <div class="form-group">
                                <label class="control-label">IBAN/SWIFT Code</label>
                                <input type="text" class="form-control" name="iban_swift" 
                                       value="<?= htmlspecialchars($client_data['iban_swift'] ?? '') ?>" 
                                       maxlength="50" placeholder="For international transfers (optional)">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Password Change Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fa fa-lock"></i> Change Password</h4>
                            <small class="text-muted">Leave blank if you don't want to change your password</small>
                            
                            <div class="form-group">
                                <label class="control-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" 
                                       placeholder="Enter your current password">
                            </div>

                            <div class="form-group">
                                <label class="control-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" 
                                       placeholder="Enter new password (minimum 6 characters)" 
                                       minlength="6">
                            </div>

                            <div class="form-group">
                                <label class="control-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" 
                                       placeholder="Confirm your new password">
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="col-md-6">
                            <h4><i class="fa fa-info-circle"></i> Account Information</h4>
                            
                            <div class="form-group">
                                <label class="control-label">Member Since</label>
                                <p class="form-control-static">
                                    <i class="fa fa-calendar"></i> 
                                    <?= date('F j, Y', strtotime($client_data['created_at'])) ?>
                                </p>
                            </div>

                            <div class="form-group">
                                <label class="control-label">Client ID</label>
                                <p class="form-control-static">
                                    <span class="label label-primary">#<?= sprintf('%04d', $client_data['id']) ?></span>
                                </p>
                            </div>

                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> 
                                <strong>Important:</strong> Please ensure your bank account information is accurate. 
                                This will be used for processing withdrawals and profit distributions.
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Update Profile
                            </button>
                            <a href="dashboard.php" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    $('form').on('submit', function(e) {
        var newPassword = $('input[name="new_password"]').val();
        var confirmPassword = $('input[name="confirm_password"]').val();
        var currentPassword = $('input[name="current_password"]').val();
        
        // If any password field is filled, all must be filled
        if (newPassword || confirmPassword || currentPassword) {
            if (!currentPassword) {
                alert('Current password is required to change password');
                e.preventDefault();
                return false;
            }
            if (!newPassword) {
                alert('New password is required');
                e.preventDefault();
                return false;
            }
            if (newPassword !== confirmPassword) {
                alert('Password confirmation does not match');
                e.preventDefault();
                return false;
            }
            if (newPassword.length < 6) {
                alert('New password must be at least 6 characters long');
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>