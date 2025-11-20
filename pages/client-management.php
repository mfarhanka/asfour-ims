<?php
/* pages/client-management.php - Admin page to manage client accounts (suspend/unsuspend/etc) */

// Check if user is logged in and is admin/superadmin
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['user_type']) || 
    !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle suspension/unsuspension actions
if ($_POST && isset($_POST['action']) && isset($_POST['client_id'])) {
    $action = $_POST['action'];
    $client_id = (int)$_POST['client_id'];
    $admin_id = $_SESSION['user_id'];
    
    if ($action === 'suspend') {
        $suspension_reason = trim($_POST['suspension_reason'] ?? '');
        $suspension_type = $_POST['suspension_type'] ?? 'permanent';
        $suspension_end_date = $_POST['suspension_end_date'] ?? null;
        
        if (empty($suspension_reason)) {
            $error_message = 'Suspension reason is required.';
        } else {
            $sql = "UPDATE clients SET status = 'suspended', suspended_by = ?, suspended_at = NOW(), suspension_reason = ?";
            $params = [$admin_id, $suspension_reason];
            $types = "is";
            
            if ($suspension_type === 'temporary' && !empty($suspension_end_date)) {
                $sql .= ", suspension_end_date = ?";
                $params[] = $suspension_end_date;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ? AND status = 'approved'";
            $params[] = $client_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Client account suspended successfully!';
            } else {
                $error_message = 'Error suspending client account or client not found.';
            }
            $stmt->close();
        }
        
    } elseif ($action === 'unsuspend') {
        $stmt = $conn->prepare("UPDATE clients SET status = 'approved', suspended_by = NULL, suspended_at = NULL, suspension_reason = NULL, suspension_end_date = NULL WHERE id = ? AND status = 'suspended'");
        $stmt->bind_param("i", $client_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_message = 'Client account unsuspended successfully!';
        } else {
            $error_message = 'Error unsuspending client account or client not found.';
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        $confirmation = $_POST['delete_confirmation'] ?? '';
        if ($confirmation !== 'DELETE') {
            $error_message = 'Invalid confirmation. Please type "DELETE" to confirm account deletion.';
        } else {
            $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->bind_param("i", $client_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Client account deleted successfully!';
            } else {
                $error_message = 'Error deleting client account or client not found.';
            }
            $stmt->close();
        }
    }
}

// Fetch all clients with their status
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT c.id, c.name, c.email, c.phone, c.username, c.status, c.created_at,
           c.suspended_at, c.suspension_reason, c.suspension_end_date,
           a1.name as approved_admin, a2.name as suspended_admin
    FROM clients c 
    LEFT JOIN admins a1 ON c.approved_by = a1.id 
    LEFT JOIN admins a2 ON c.suspended_by = a2.id 
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.username LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($status_filter)) {
    $sql .= " AND c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count clients by status
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM clients 
    GROUP BY status
");
$stmt->execute();
$result = $stmt->get_result();
$status_counts = [];
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$stmt->close();
?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-users"></i> Client Account Management</h2>
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

                <!-- Status Summary -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="info-tile tile-success">
                            <div class="tile-heading">
                                <span>Active Clients</span>
                            </div>
                            <div class="tile-body">
                                <span><?= $status_counts['approved'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-tile tile-warning">
                            <div class="tile-heading">
                                <span>Pending Approval</span>
                            </div>
                            <div class="tile-body">
                                <span><?= $status_counts['pending'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-tile tile-danger">
                            <div class="tile-heading">
                                <span>Suspended</span>
                            </div>
                            <div class="tile-body">
                                <span><?= $status_counts['suspended'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-tile tile-dark">
                            <div class="tile-heading">
                                <span>Rejected</span>
                            </div>
                            <div class="tile-body">
                                <span><?= $status_counts['rejected'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="get" action="" class="form-inline" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, email, or username" 
                               value="<?= htmlspecialchars($search) ?>" style="width: 300px;">
                    </div>
                    <div class="form-group" style="margin-left: 10px;">
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                        <i class="fa fa-search"></i> Filter
                    </button>
                    <a href="index.php?p=client-management" class="btn btn-default" style="margin-left: 5px;">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped jambo_table">
                        <thead>
                            <tr class="headings">
                                <th class="column-title">Client Details</th>
                                <th class="column-title">Contact Information</th>
                                <th class="column-title">Status</th>
                                <th class="column-title">Registration Date</th>
                                <th class="column-title">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
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
                                    <?php if ($client['status'] === 'approved'): ?>
                                        <span class="label label-success">Active</span>
                                    <?php elseif ($client['status'] === 'pending'): ?>
                                        <span class="label label-warning">Pending</span>
                                    <?php elseif ($client['status'] === 'suspended'): ?>
                                        <span class="label label-danger">Suspended</span>
                                        <?php if (!empty($client['suspension_reason'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($client['suspension_reason']) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($client['suspension_end_date'])): ?>
                                            <br><small class="text-info">Ends: <?= date('M j, Y', strtotime($client['suspension_end_date'])) ?></small>
                                        <?php endif; ?>
                                    <?php elseif ($client['status'] === 'rejected'): ?>
                                        <span class="label label-default">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fa fa-calendar"></i> 
                                    <?= date('M j, Y', strtotime($client['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($client['status'] === 'approved'): ?>
                                        <button type="button" class="btn btn-warning btn-xs" 
                                                onclick="suspendClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                                            <i class="fa fa-pause"></i> Suspend
                                        </button>
                                    <?php elseif ($client['status'] === 'suspended'): ?>
                                        <button type="button" class="btn btn-success btn-xs" 
                                                onclick="unsuspendClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                                            <i class="fa fa-play"></i> Unsuspend
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($client['status'] !== 'approved'): ?>
                                        <button type="button" class="btn btn-danger btn-xs" 
                                                onclick="deleteClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['name'], ENT_QUOTES) ?>')">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($clients)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> No clients found matching your criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suspension Modal -->
<div class="modal fade" id="suspensionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Suspend Client Account</h4>
            </div>
            <form id="suspensionForm" method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="client_id" id="suspendClientId">
                    
                    <p>You are about to suspend the account for: <strong id="suspendClientName"></strong></p>
                    
                    <div class="form-group">
                        <label>Suspension Type</label>
                        <div class="radio">
                            <label><input type="radio" name="suspension_type" value="permanent" checked> Permanent</label>
                        </div>
                        <div class="radio">
                            <label><input type="radio" name="suspension_type" value="temporary"> Temporary</label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="endDateGroup" style="display: none;">
                        <label>Suspension End Date</label>
                        <input type="date" class="form-control" name="suspension_end_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Suspension Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="suspension_reason" rows="3" 
                                  placeholder="Please provide a reason for suspension..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Suspend Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Action Forms -->
<form id="actionForm" method="post" action="" style="display: none;">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="client_id" id="formClientId">
    <input type="hidden" name="delete_confirmation" id="formDeleteConfirmation">
</form>

<script>
function suspendClient(clientId, clientName) {
    document.getElementById('suspendClientId').value = clientId;
    document.getElementById('suspendClientName').textContent = clientName;
    $('#suspensionModal').modal('show');
}

function unsuspendClient(clientId, clientName) {
    if (confirm('Are you sure you want to unsuspend the account for "' + clientName + '"?\n\nThey will regain access to their account immediately.')) {
        document.getElementById('formAction').value = 'unsuspend';
        document.getElementById('formClientId').value = clientId;
        document.getElementById('actionForm').submit();
    }
}

function deleteClient(clientId, clientName) {
    var confirmation = prompt('WARNING: This will permanently delete the account for "' + clientName + '".\n\nThis action CANNOT be undone and will remove all their data.\n\nType "DELETE" to confirm:');
    
    if (confirmation === 'DELETE') {
        document.getElementById('formAction').value = 'delete';
        document.getElementById('formClientId').value = clientId;
        document.getElementById('formDeleteConfirmation').value = confirmation;
        document.getElementById('actionForm').submit();
    } else if (confirmation !== null) {
        alert('Deletion cancelled. You must type "DELETE" exactly to confirm.');
    }
}

// Show/hide end date based on suspension type
$('input[name="suspension_type"]').change(function() {
    if ($(this).val() === 'temporary') {
        $('#endDateGroup').show();
    } else {
        $('#endDateGroup').hide();
    }
});

// Auto-dismiss alerts
setTimeout(function() {
    $('.alert').fadeOut();
}, 5000);
</script>