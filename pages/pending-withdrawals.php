<?php 
/* pages/pending-withdrawals.php - Admin Withdrawal Management Page */

// Fetch pending withdrawal requests
$withdrawalsSQL = "SELECT 
    w.withdrawal_id,
    w.client_investment_id,
    w.withdrawal_amount,
    w.request_date,
    w.status,
    w.withdrawal_proof,
    w.processed_date,
    w.admin_notes,
    w.client_notes,
    c.id as client_id,
    c.name as client_name,
    c.email as client_email,
    c.phone as client_phone,
    i.id as investment_id,
    i.title as project_title,
    i.profit_percent,
    i.profit_percent_min,
    i.profit_percent_max,
    ci.invested_amount,
    ci.investment_date,
    a.username as processed_by_username
FROM withdrawals w
JOIN client_investments ci ON w.client_investment_id = ci.id
JOIN clients c ON w.client_id = c.id
JOIN investments i ON w.investment_id = i.id
LEFT JOIN admins a ON w.processed_by = a.id
ORDER BY 
    CASE w.status
        WHEN 'pending' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'completed' THEN 3
        WHEN 'rejected' THEN 4
    END,
    w.request_date DESC";

$withdrawalsResult = $conn->query($withdrawalsSQL);

// Get statistics
$statsSQL = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'pending' THEN withdrawal_amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN status = 'completed' THEN withdrawal_amount ELSE 0 END) as completed_amount
FROM withdrawals";
$statsResult = $conn->query($statsSQL);
$stats = $statsResult->fetch_assoc();
?>

<div class="page-title">
  <div class="title_left">
    <h3>Withdrawal Management <small>Process client profit withdrawals</small></h3>
  </div>
</div>

<div class="clearfix"></div>

<!-- Statistics Row -->
<div class="row">
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="tile-stats">
      <div class="icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="count"><?= $stats['pending_count'] ?></div>
      <h3>Pending Requests</h3>
      <p>$<?= number_format($stats['pending_amount'], 2) ?></p>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="tile-stats">
      <div class="icon"><i class="fa fa-check-circle"></i></div>
      <div class="count"><?= $stats['approved_count'] ?></div>
      <h3>Approved</h3>
      <p>Awaiting Transfer</p>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="tile-stats">
      <div class="icon"><i class="fa fa-check"></i></div>
      <div class="count"><?= $stats['completed_count'] ?></div>
      <h3>Completed</h3>
      <p>$<?= number_format($stats['completed_amount'], 2) ?></p>
    </div>
  </div>
  <div class="col-md-3 col-sm-6 col-xs-12">
    <div class="tile-stats">
      <div class="icon"><i class="fa fa-money"></i></div>
      <div class="count"><?= $stats['total_requests'] ?></div>
      <h3>Total Requests</h3>
      <p>All Time</p>
    </div>
  </div>
</div>

<!-- Withdrawals Table -->
<div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-list"></i> Withdrawal Requests</h2>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <?php if ($withdrawalsResult->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered jambo_table bulk_action" id="withdrawalsTable">
              <thead>
                <tr>
                  <th>Request ID</th>
                  <th>Client</th>
                  <th>Project</th>
                  <th>Investment</th>
                  <th>Withdrawal Amount</th>
                  <th>Request Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($withdrawal = $withdrawalsResult->fetch_assoc()): ?>
                  <?php
                    // Handle profit range
                    $profitPercentMin = $withdrawal['profit_percent_min'] ?? $withdrawal['profit_percent'];
                    $profitPercentMax = $withdrawal['profit_percent_max'] ?? $withdrawal['profit_percent'];
                    $isProfitRange = ($profitPercentMin != $profitPercentMax);
                    
                    if ($isProfitRange) {
                        $profitDisplay = number_format($profitPercentMin, 1) . '% - ' . number_format($profitPercentMax, 1) . '%';
                    } else {
                        $profitDisplay = number_format($withdrawal['profit_percent'], 1) . '%';
                    }
                    
                    switch($withdrawal['status']) {
                      case 'pending':
                        $statusLabel = '<span class="label label-warning">Pending Review</span>';
                        break;
                      case 'approved':
                        $statusLabel = '<span class="label label-info">Approved - Awaiting Transfer</span>';
                        break;
                      case 'completed':
                        $statusLabel = '<span class="label label-success">Completed</span>';
                        break;
                      case 'rejected':
                        $statusLabel = '<span class="label label-danger">Rejected</span>';
                        break;
                      default:
                        $statusLabel = '<span class="label label-default">Unknown</span>';
                    }
                  ?>
                  <tr>
                    <td><strong>#<?= $withdrawal['withdrawal_id'] ?></strong></td>
                    <td>
                      <strong><?= htmlspecialchars($withdrawal['client_name']) ?></strong>
                      <br><small class="text-muted"><?= htmlspecialchars($withdrawal['client_email']) ?></small>
                    </td>
                    <td>
                      <strong><?= htmlspecialchars($withdrawal['project_title']) ?></strong>
                      <br><small class="text-muted">Project #<?= $withdrawal['investment_id'] ?></small>
                    </td>
                    <td>
                      <strong class="text-primary">$<?= number_format($withdrawal['invested_amount'], 2) ?></strong>
                      <br><span class="label label-success"><?= $profitDisplay ?></span>
                      <br><small class="text-muted">Inv. Date: <?= date('M d, Y', strtotime($withdrawal['investment_date'])) ?></small>
                    </td>
                    <td><strong class="text-success">$<?= number_format($withdrawal['withdrawal_amount'], 2) ?></strong></td>
                    <td>
                      <?= date('M d, Y', strtotime($withdrawal['request_date'])) ?>
                      <br><small class="text-muted"><?= date('g:i A', strtotime($withdrawal['request_date'])) ?></small>
                    </td>
                    <td><?= $statusLabel ?></td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm" 
                              data-id="<?= $withdrawal['withdrawal_id'] ?>"
                              data-client="<?= htmlspecialchars($withdrawal['client_name']) ?>"
                              data-project="<?= htmlspecialchars($withdrawal['project_title']) ?>"
                              data-amount="<?= $withdrawal['withdrawal_amount'] ?>"
                              data-client-notes="<?= htmlspecialchars($withdrawal['client_notes']) ?>"
                              data-admin-notes="<?= htmlspecialchars($withdrawal['admin_notes'] ?? '') ?>"
                              data-status="<?= $withdrawal['status'] ?>"
                              data-proof="<?= htmlspecialchars($withdrawal['withdrawal_proof'] ?? '') ?>"
                              data-email="<?= htmlspecialchars($withdrawal['client_email']) ?>"
                              data-phone="<?= htmlspecialchars($withdrawal['client_phone'] ?? '') ?>"
                              onclick="viewWithdrawalDetails(this)">
                        <i class="fa fa-eye"></i> View Details
                      </button>
                      
                      <?php if ($withdrawal['status'] == 'pending'): ?>
                        <button type="button" class="btn btn-success btn-sm" onclick="approveWithdrawal(<?= $withdrawal['withdrawal_id'] ?>)">
                          <i class="fa fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $withdrawal['withdrawal_id'] ?>)">
                          <i class="fa fa-times"></i> Reject
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="cancelWithdrawal(<?= $withdrawal['withdrawal_id'] ?>)">
                          <i class="fa fa-ban"></i> Cancel
                        </button>
                      <?php elseif ($withdrawal['status'] == 'approved'): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showUploadProofModal(<?= $withdrawal['withdrawal_id'] ?>, '<?= htmlspecialchars(addslashes($withdrawal['client_name'])) ?>', <?= $withdrawal['withdrawal_amount'] ?>)">
                          <i class="fa fa-upload"></i> Upload Proof
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="cancelWithdrawal(<?= $withdrawal['withdrawal_id'] ?>)">
                          <i class="fa fa-ban"></i> Cancel
                        </button>
                      <?php elseif ($withdrawal['status'] == 'completed' && $withdrawal['withdrawal_proof']): ?>
                        <a href="uploads/withdrawals/<?= htmlspecialchars($withdrawal['withdrawal_proof']) ?>" target="_blank" class="btn btn-default btn-sm">
                          <i class="fa fa-download"></i> View Proof
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center" style="padding: 50px;">
            <i class="fa fa-inbox fa-5x text-muted"></i>
            <h3 style="margin-top: 20px;">No Withdrawal Requests</h3>
            <p class="text-muted">No withdrawal requests have been submitted yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Withdrawal Details Modal -->
<div class="modal fade" id="withdrawalDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-info-circle"></i> Withdrawal Request Details</h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Client Information</h4>
            <table class="table table-bordered">
              <tr><th>Name:</th><td id="detail_client_name"></td></tr>
              <tr><th>Email:</th><td id="detail_client_email"></td></tr>
              <tr><th>Phone:</th><td id="detail_client_phone"></td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <h4>Withdrawal Information</h4>
            <table class="table table-bordered">
              <tr><th>Project:</th><td id="detail_project"></td></tr>
              <tr><th>Amount:</th><td id="detail_amount"></td></tr>
              <tr><th>Status:</th><td id="detail_status"></td></tr>
            </table>
          </div>
        </div>
        
        <h4>Client Bank Details / Instructions</h4>
        <div class="well" id="detail_client_notes" style="white-space: pre-wrap; background: #f9f9f9;"></div>
        
        <div id="admin_notes_section" style="display: none;">
          <h4>Admin Notes</h4>
          <div class="well" id="detail_admin_notes" style="white-space: pre-wrap; background: #fff3cd;"></div>
        </div>
        
        <div id="proof_section" style="display: none;">
          <h4>Transfer Proof</h4>
          <a href="#" id="proof_link" target="_blank" class="btn btn-default">
            <i class="fa fa-download"></i> View Transfer Proof
          </a>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Upload Proof Modal -->
<div class="modal fade" id="uploadProofModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="uploadProofForm" method="POST" action="pages/process-withdrawal-proof.php" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-upload"></i> Upload Transfer Proof</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="proof_withdrawal_id" name="withdrawal_id" required>
          
          <div class="alert alert-info">
            <strong>Client:</strong> <span id="proof_client_name"></span><br>
            <strong>Amount:</strong> $<span id="proof_amount"></span>
          </div>
          
          <div class="form-group">
            <label for="withdrawal_proof_file">Transfer Proof (Receipt/Screenshot) <span class="text-danger">*</span></label>
            <input type="file" class="form-control" id="withdrawal_proof_file" name="withdrawal_proof" accept="image/*,.pdf" required>
            <p class="help-block">Upload bank transfer receipt or screenshot (Max 5MB, JPG/PNG/PDF)</p>
          </div>
          
          <div class="form-group">
            <label for="proof_admin_notes">Notes (Optional)</label>
            <textarea class="form-control" id="proof_admin_notes" name="admin_notes" rows="3" placeholder="Any additional notes about this transfer..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fa fa-check"></i> Complete Withdrawal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="rejectForm" method="POST" action="pages/process-withdrawal-action.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-times-circle"></i> Reject Withdrawal Request</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="reject_withdrawal_id" name="withdrawal_id" required>
          <input type="hidden" name="action" value="reject">
          
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            Are you sure you want to reject this withdrawal request?
          </div>
          
          <div class="form-group">
            <label for="reject_reason">Rejection Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" id="reject_reason" name="admin_notes" rows="4" required placeholder="Explain why this withdrawal request is being rejected..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa fa-times"></i> Reject Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
  $('#withdrawalsTable').DataTable({
    "order": [[ 5, "desc" ]],
    "pageLength": 25
  });
});

function viewWithdrawalDetails(button) {
    // Get data from button attributes
    var clientName = button.getAttribute('data-client');
    var email = button.getAttribute('data-email');
    var phone = button.getAttribute('data-phone');
    var project = button.getAttribute('data-project');
    var amount = parseFloat(button.getAttribute('data-amount'));
    var clientNotes = button.getAttribute('data-client-notes');
    var adminNotes = button.getAttribute('data-admin-notes');
    var status = button.getAttribute('data-status');
    var proof = button.getAttribute('data-proof');
    
    document.getElementById('detail_client_name').textContent = clientName;
    document.getElementById('detail_client_email').textContent = email;
    document.getElementById('detail_client_phone').textContent = phone || 'N/A';
    document.getElementById('detail_project').textContent = project;
    document.getElementById('detail_amount').innerHTML = '<strong class="text-success">$' + amount.toFixed(2) + '</strong>';
    document.getElementById('detail_client_notes').textContent = clientNotes;
    
    var statusHtml = '';
    switch(status) {
        case 'pending': statusHtml = '<span class="label label-warning">Pending Review</span>'; break;
        case 'approved': statusHtml = '<span class="label label-info">Approved</span>'; break;
        case 'completed': statusHtml = '<span class="label label-success">Completed</span>'; break;
        case 'rejected': statusHtml = '<span class="label label-danger">Rejected</span>'; break;
    }
    document.getElementById('detail_status').innerHTML = statusHtml;
    
    if (adminNotes) {
        document.getElementById('detail_admin_notes').textContent = adminNotes;
        document.getElementById('admin_notes_section').style.display = 'block';
    } else {
        document.getElementById('admin_notes_section').style.display = 'none';
    }
    
    if (proof) {
        document.getElementById('proof_link').href = 'uploads/withdrawals/' + proof;
        document.getElementById('proof_section').style.display = 'block';
    } else {
        document.getElementById('proof_section').style.display = 'none';
    }
    
    $('#withdrawalDetailsModal').modal('show');
}

function approveWithdrawal(withdrawalId) {
    if (confirm('Approve this withdrawal request? The client will be notified.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'pages/process-withdrawal-action.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'withdrawal_id';
        idInput.value = withdrawalId;
        form.appendChild(idInput);
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'approve';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(withdrawalId) {
    document.getElementById('reject_withdrawal_id').value = withdrawalId;
    document.getElementById('reject_reason').value = '';
    $('#rejectModal').modal('show');
}

function showUploadProofModal(withdrawalId, clientName, amount) {
    document.getElementById('proof_withdrawal_id').value = withdrawalId;
    document.getElementById('proof_client_name').textContent = clientName;
    document.getElementById('proof_amount').textContent = amount.toFixed(2);
    document.getElementById('withdrawal_proof_file').value = '';
    document.getElementById('proof_admin_notes').value = '';
    $('#uploadProofModal').modal('show');
}

function cancelWithdrawal(withdrawalId) {
    if (confirm('Are you sure you want to cancel this withdrawal request? This action cannot be undone.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'pages/process-withdrawal-action.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'withdrawal_id';
        idInput.value = withdrawalId;
        form.appendChild(idInput);
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'cancel';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
