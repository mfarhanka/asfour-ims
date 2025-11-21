<?php /* pages/pending-investments.php - Admin page to review and approve/reject pending investment requests */
require_once __DIR__ . '/../config.php';

// Handle approval
if ($_POST && isset($_POST['approve_investment'])) {
    $investmentId = intval($_POST['approve_investment']);
    $adminId = $_SESSION['admin_id'];
    
    $approveSQL = "UPDATE client_investments 
                   SET status = 'approved', 
                       approved_at = NOW(), 
                       approved_by = ? 
                   WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($approveSQL);
    $stmt->bind_param("ii", $adminId, $investmentId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<script>alert('Investment request approved successfully! Client can now upload payment proof.'); window.location.href = 'index.php?p=pending-investments';</script>";
    } else {
        echo "<script>alert('Error approving investment request!');</script>";
    }
    $stmt->close();
}

// Handle rejection
if ($_POST && isset($_POST['reject_investment'])) {
    $investmentId = intval($_POST['reject_investment']);
    $rejectionReason = trim($_POST['rejection_reason']);
    
    // Set default rejection reason if none provided
    if (empty($rejectionReason)) {
        $rejectionReason = 'No reason stated';
    }
    
    $rejectSQL = "UPDATE client_investments 
                  SET status = 'rejected', 
                      rejection_reason = ? 
                  WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($rejectSQL);
    $stmt->bind_param("si", $rejectionReason, $investmentId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<script>alert('Investment request rejected.'); window.location.href = 'index.php?p=pending-investments';</script>";
    } else {
        echo "<script>alert('Error rejecting investment request!');</script>";
    }
    $stmt->close();
}

// Get all pending investments
$sql = "SELECT ci.id, ci.client_id, ci.investment_id, ci.invested_amount, ci.investment_date, ci.created_at,
               c.name as client_name, c.username as client_username, c.email as client_email, c.phone as client_phone,
               i.title as investment_title, i.total_goal, i.profit_percent, i.start_date, i.end_date
        FROM client_investments ci 
        LEFT JOIN clients c ON ci.client_id = c.id 
        LEFT JOIN investments i ON ci.investment_id = i.id 
        WHERE ci.status = 'pending'
        ORDER BY ci.created_at ASC";
$result = $conn->query($sql);
?>

<div class="page-title">
  <div class="title_left">
    <h3>Pending Investment Requests</h3>
  </div>
</div>

<div class="clearfix"></div>

<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-clock-o"></i> Review Investment Requests</h2>
        <div class="clearfix"></div>
      </div>
      
      <div class="x_content">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Step 1 of 3:</strong> Review and approve/reject investment requests. After approval, clients will upload payment proof for verification.
          </div>
          
          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Client Name</th>
                  <th>Contact</th>
                  <th>Project Title</th>
                  <th>Investment Amount</th>
                  <th>Expected Profit</th>
                  <th>Investment Date</th>
                  <th>Submitted At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $result->fetch_assoc()): 
                  $expectedProfit = ($row['invested_amount'] * $row['profit_percent']) / 100;
                  $totalReturn = $row['invested_amount'] + $expectedProfit;
                ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                      <strong><?= htmlspecialchars($row['client_name']) ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars($row['client_username']) ?></small>
                    </td>
                    <td>
                      <i class="fa fa-envelope"></i> <?= htmlspecialchars($row['client_email']) ?><br>
                      <i class="fa fa-phone"></i> <?= htmlspecialchars($row['client_phone']) ?>
                    </td>
                    <td>
                      <strong><?= htmlspecialchars($row['investment_title']) ?></strong><br>
                      <small class="text-muted">
                        Goal: $<?= number_format($row['total_goal'], 2) ?> | 
                        Profit: <?= number_format($row['profit_percent'], 1) ?>%
                      </small>
                    </td>
                    <td>
                      <strong class="text-primary">$<?= number_format($row['invested_amount'], 2) ?></strong>
                    </td>
                    <td>
                      <span class="text-success">$<?= number_format($expectedProfit, 2) ?></span><br>
                      <small class="text-muted">Total: $<?= number_format($totalReturn, 2) ?></small>
                    </td>
                    <td><?= date('M d, Y', strtotime($row['investment_date'])) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                    <td>
                      <button type="button" class="btn btn-success btn-sm" onclick="approveInvestment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')" title="Approve Investment">
                        <i class="fa fa-check"></i> Approve
                      </button>
                      <button type="button" class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')" title="Reject Investment">
                        <i class="fa fa-times"></i> Reject
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-success text-center">
            <i class="fa fa-check-circle fa-3x"></i>
            <h4 style="margin-top: 15px;">No Pending Investment Requests</h4>
            <p>All investment requests have been processed!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Reject Investment Request</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="reject_investment" id="reject_investment_id">
          <p id="reject_investment_details"></p>
          <div class="form-group">
            <label for="rejection_reason">Rejection Reason</label>
            <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="4" placeholder="Enter reason for rejection or leave default...">No reason stated</textarea>
            <small class="text-muted">You can modify the reason above or leave the default message.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa fa-times"></i> Reject Investment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function approveInvestment(id, clientName, projectTitle) {
    if (confirm('Approve investment request?\n\nClient: ' + clientName + '\nProject: ' + projectTitle + '\n\nThe client will be notified and can upload payment proof.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'approve_investment';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(id, clientName, projectTitle) {
    document.getElementById('reject_investment_id').value = id;
    document.getElementById('reject_investment_details').innerHTML = 
        '<strong>Client:</strong> ' + clientName + '<br>' +
        '<strong>Project:</strong> ' + projectTitle;
    document.getElementById('rejection_reason').value = '';
    $('#rejectModal').modal('show');
}
</script>
