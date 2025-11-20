<?php
/* pages/pending-clients.php - Admin page to approve/reject pending client registrations */

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['user_type']) || 
    !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle approval/rejection actions
if ($_POST && isset($_POST['action']) && isset($_POST['client_id'])) {
    $action = $_POST['action'];
    $client_id = (int)$_POST['client_id'];
    $admin_id = $_SESSION['user_id'];
    $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE clients SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $admin_id, $client_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_message = 'Client account approved successfully!';
        } else {
            $error_message = 'Error approving client account.';
        }
        $stmt->close();
        
    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $error_message = 'Rejection reason is required.';
        } else {
            $stmt = $conn->prepare("UPDATE clients SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("isi", $admin_id, $rejection_reason, $client_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Client account rejected.';
            } else {
                $error_message = 'Error rejecting client account.';
            }
            $stmt->close();
        }
    }
}

// Fetch pending clients
$stmt = $conn->prepare("
    SELECT id, name, email, phone, username, created_at 
    FROM clients 
    WHERE status = 'pending' 
    ORDER BY created_at ASC
");
$stmt->execute();
$pending_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recently processed clients
$stmt = $conn->prepare("
    SELECT c.id, c.name, c.email, c.phone, c.username, c.created_at, c.status, 
           c.approved_at, c.rejection_reason, c.suspension_reason, a.name as admin_name
    FROM clients c 
    LEFT JOIN admins a ON c.approved_by = a.id 
    WHERE c.status IN ('approved', 'rejected', 'suspended') 
    ORDER BY c.approved_at DESC 
    LIMIT 10
");
$stmt->execute();
$processed_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-users"></i> Pending Client Approvals 
                    <?php if (count($pending_clients) > 0): ?>
                        <span class="badge bg-orange"><?= count($pending_clients) ?></span>
                    <?php endif; ?>
                </h2>
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
                        <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (count($pending_clients) === 0): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No pending client registrations at this time.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped jambo_table bulk_action">
                            <thead>
                                <tr class="headings">
                                    <th class="column-title">Client Details</th>
                                    <th class="column-title">Contact Information</th>
                                    <th class="column-title">Registration Date</th>
                                    <th class="column-title">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_clients as $client): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($client['name']) ?></strong><br>
                                        <small class="text-muted">Username: <?= htmlspecialchars($client['username']) ?></small>
                                    </td>
                                    <td>
                                        <i class="fa fa-envelope"></i> <?= htmlspecialchars($client['email']) ?><br>
                                        <i class="fa fa-phone"></i> <?= htmlspecialchars($client['phone']) ?>
                                    </td>
                                    <td>
                                        <i class="fa fa-calendar"></i> 
                                        <?= date('M j, Y g:i A', strtotime($client['created_at'])) ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm" 
                                                onclick="approveClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                                            <i class="fa fa-check"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="rejectClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                                            <i class="fa fa-times"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (count($processed_clients) > 0): ?>
                    <h4 style="margin-top: 30px;"><i class="fa fa-history"></i> Recently Processed</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processed_clients as $client): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($client['name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($client['username']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($client['email']) ?><br>
                                        <small><?= htmlspecialchars($client['phone']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($client['status'] === 'approved'): ?>
                                            <span class="label label-success">Approved</span>
                                        <?php elseif ($client['status'] === 'suspended'): ?>
                                            <span class="label label-danger">Suspended</span>
                                            <?php if (!empty($client['suspension_reason'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($client['suspension_reason']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="label label-danger">Rejected</span>
                                            <?php if (!empty($client['rejection_reason'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($client['rejection_reason']) ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($client['admin_name'] ?: 'System') ?></td>
                                    <td><?= date('M j, Y', strtotime($client['approved_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Approval Form -->
<form id="approvalForm" method="post" action="" style="display: none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="client_id" id="formClientId">
    <input type="hidden" name="rejection_reason" id="formRejectionReason">
</form>

<script>
function approveClient(clientId, clientName) {
    if (confirm('Are you sure you want to approve the account for "' + clientName + '"?\n\nOnce approved, they will be able to login and start investing.')) {
        document.getElementById('formAction').value = 'approve';
        document.getElementById('formClientId').value = clientId;
        document.getElementById('approvalForm').submit();
    }
}

function rejectClient(clientId, clientName) {
    var reason = prompt('Please provide a reason for rejecting "' + clientName + '"\'s account:\n\n(This will be stored for reference)');
    
    if (reason !== null && reason.trim() !== '') {
        document.getElementById('formAction').value = 'reject';
        document.getElementById('formClientId').value = clientId;
        document.getElementById('formRejectionReason').value = reason.trim();
        document.getElementById('approvalForm').submit();
    } else if (reason !== null) {
        alert('Rejection reason is required.');
    }
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut();
}, 5000);
</script>