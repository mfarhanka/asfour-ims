<?php 
/* c/pages/my-investments.php - Client My Investments Content */

$client_id = $_SESSION['client_id'];

// Get client's detailed investment information with partial payment support
$investmentsSQL = "SELECT 
    ci.id as investment_id,
    ci.invested_amount,
    ci.total_paid,
    ci.remaining_amount,
    ci.is_fully_paid,
    ci.investment_date,
    ci.agreement_document,
    ci.agreement_uploaded,
    ci.status,
    ci.rejection_reason,
    ci.created_at,
    i.id as project_id,
    i.title as project_title,
    i.total_goal,
    i.profit_percent,
    i.profit_percent_min,
    i.profit_percent_max,
    i.start_date,
    i.end_date,
    c.name as client_name
FROM client_investments ci
JOIN investments i ON ci.investment_id = i.id
JOIN clients c ON ci.client_id = c.id
WHERE ci.client_id = ?
ORDER BY ci.investment_date DESC, ci.created_at DESC";

$stmt = $conn->prepare($investmentsSQL);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$investmentsResult = $stmt->get_result();
?>

<div class="page-title">
  <div class="title_left">
    <h3>My Investments</h3>
  </div>
</div>

<div class="clearfix"></div>

<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-briefcase"></i> My Investment Portfolio</h2>
        <div class="clearfix"></div>
      </div>
      
      <div class="x_content">
        <?php if ($investmentsResult->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Investment ID</th>
                  <th>Project Title</th>
                  <th>Investment Amount</th>
                  <th>Payment Status</th>
                  <th>Investment Date</th>
                  <th>Profit Rate</th>
                  <th>Expected Profit</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while($investment = $investmentsResult->fetch_assoc()): ?>
                  <?php 
                    // Handle profit range or fixed profit
                    $profitPercentMin = $investment['profit_percent_min'] ?? $investment['profit_percent'];
                    $profitPercentMax = $investment['profit_percent_max'] ?? $investment['profit_percent'];
                    $isProfitRange = ($profitPercentMin != $profitPercentMax);
                    
                    if ($isProfitRange) {
                        $profitMin = $investment['invested_amount'] * ($profitPercentMin / 100);
                        $profitMax = $investment['invested_amount'] * ($profitPercentMax / 100);
                        $profitDisplay = number_format($profitPercentMin, 1) . '% - ' . number_format($profitPercentMax, 1) . '%';
                        $expectedProfitDisplay = '$' . number_format($profitMin, 2) . ' - $' . number_format($profitMax, 2);
                    } else {
                        $expectedProfit = $investment['invested_amount'] * ($investment['profit_percent'] / 100);
                        $profitDisplay = number_format($investment['profit_percent'], 1) . '%';
                        $expectedProfitDisplay = '$' . number_format($expectedProfit, 2);
                    }
                    
                    $projectStarted = strtotime($investment['start_date']) <= time();
                    $projectEnded = strtotime($investment['end_date']) < time();
                    
                    // Display investment status based on database status and project timeline
                    $canUploadPayment = false;
                    $paymentProgress = ($investment['invested_amount'] > 0) ? ($investment['total_paid'] / $investment['invested_amount']) * 100 : 0;
                    
                    switch($investment['status']) {
                      case 'pending':
                        $status = '<span class="label label-warning">Pending Approval</span>';
                        break;
                      case 'approved':
                        $status = '<span class="label label-info">Awaiting Payment</span>';
                        $canUploadPayment = true;
                        break;
                      case 'payment_partial':
                        $status = '<span class="label label-primary">Partial Payment</span>';
                        $canUploadPayment = true;
                        break;
                      case 'payment_pending':
                        $status = '<span class="label label-success">Fully Paid - Pending Activation</span>';
                        break;
                      case 'rejected':
                        $status = '<span class="label label-danger">Rejected</span>';
                        break;
                      case 'active':
                        $status = '<span class="label label-success">Active</span>';
                        break;
                      case 'completed':
                        $status = '<span class="label label-default">Completed</span>';
                        break;
                      default:
                        $status = '<span class="label label-default">Unknown</span>';
                    }
                  ?>
                  <tr>
                    <td><strong>#<?= $investment['investment_id'] ?></strong></td>
                    <td>
                      <strong><?= htmlspecialchars($investment['project_title']) ?></strong>
                      <br><small class="text-muted">Project #<?= $investment['project_id'] ?></small>
                    </td>
                    <td><strong class="text-primary">$<?= number_format($investment['invested_amount'], 2) ?></strong></td>
                    <td>
                      <div class="progress" style="margin-bottom: 5px;">
                        <div class="progress-bar progress-bar-<?= $investment['is_fully_paid'] ? 'success' : 'info' ?>" 
                             style="width: <?= min($paymentProgress, 100) ?>%">
                          <?= number_format($paymentProgress, 0) ?>%
                        </div>
                      </div>
                      <small>
                        <strong>Paid:</strong> $<?= number_format($investment['total_paid'], 2) ?><br>
                        <strong>Remaining:</strong> $<?= number_format($investment['remaining_amount'], 2) ?>
                      </small>
                    </td>
                    <td>
                      <?= date('M d, Y', strtotime($investment['investment_date'])) ?>
                      <br><small class="text-muted"><?= date('g:i A', strtotime($investment['created_at'])) ?></small>
                    </td>
                    <td><span class="label label-success"><?= $profitDisplay ?></span></td>
                    <td><strong class="text-success"><?= $expectedProfitDisplay ?></strong></td>
                    <td><?= $status ?></td>
                    <td>
                      <?php if ($canUploadPayment && $investment['remaining_amount'] > 0): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showUploadPaymentModal(<?= $investment['investment_id'] ?>, '<?= htmlspecialchars($investment['project_title']) ?>', <?= $investment['invested_amount'] ?>, <?= $investment['remaining_amount'] ?>)">
                          <i class="fa fa-upload"></i> Add Payment
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="viewPaymentHistory(<?= $investment['investment_id'] ?>)">
                          <i class="fa fa-list"></i> Payments
                        </button>
                      <?php elseif ($investment['status'] == 'rejected'): ?>
                        <button type="button" class="btn btn-warning btn-sm" onclick="showRejectionReason('<?= htmlspecialchars(addslashes($investment['rejection_reason'] ?? 'No reason provided')) ?>')">
                          <i class="fa fa-info-circle"></i> View Reason
                        </button>
                      <?php elseif ($investment['is_fully_paid']): ?>
                        <button type="button" class="btn btn-info btn-sm" onclick="viewPaymentHistory(<?= $investment['investment_id'] ?>)">
                          <i class="fa fa-list"></i> View Payments
                        </button>
                      <?php else: ?>
                        <button type="button" class="btn btn-default btn-sm" onclick="viewPaymentHistory(<?= $investment['investment_id'] ?>)">
                          <i class="fa fa-list"></i> Payments
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center" style="padding: 50px;">
            <i class="fa fa-briefcase fa-5x text-muted"></i>
            <h3 style="margin-top: 20px;">No Investments Found</h3>
            <p class="text-muted">You haven't made any investments yet. Browse available projects to get started!</p>
            <a href="available-projects.php" class="btn btn-primary">
              <i class="fa fa-search"></i> Browse Available Projects
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Upload Payment Proof Modal -->
<div class="modal fade" id="uploadPaymentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="uploadPaymentForm" method="POST" action="upload-payment-proof.php" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Add Payment</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="investment_id" id="upload_investment_id">
          
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Partial Payments Allowed:</strong> You can pay in multiple installments.
          </div>
          
          <div class="form-group">
            <label>Project:</label>
            <p id="upload_project_title" class="form-control-static"><strong></strong></p>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Total Investment:</label>
                <p id="upload_total_amount" class="form-control-static"><strong class="text-primary"></strong></p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Remaining to Pay:</label>
                <p id="upload_remaining_amount" class="form-control-static"><strong class="text-warning"></strong></p>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="payment_amount">Payment Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control" name="payment_amount" id="payment_amount" required min="0.01" placeholder="Enter amount you're paying now">
            <small class="form-text text-muted">You can pay partial amounts. Just upload proof for this payment.</small>
          </div>
          
          <div class="form-group">
            <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="payment_date" id="payment_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="payment_proof_file">Payment Proof <span class="text-danger">*</span></label>
            <input type="file" class="form-control-file" name="payment_proof_file" id="payment_proof_file" required accept="image/*,.pdf">
            <small class="form-text text-muted">Accepted formats: JPG, PNG, PDF (Max: 5MB)</small>
          </div>
          
          <div class="form-group">
            <label for="payment_notes">Payment Notes (Optional)</label>
            <textarea class="form-control" name="payment_notes" id="payment_notes" rows="2" placeholder="e.g., Bank transfer reference, check number..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-upload"></i> Upload Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment History</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="paymentHistoryContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-3x"></i>
          <p>Loading payment history...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function showUploadPaymentModal(investmentId, projectTitle, totalAmount, remainingAmount) {
    // Check if remaining amount is valid
    if (remainingAmount <= 0) {
        alert('This investment is already fully paid. No additional payment needed.');
        return;
    }
    
    document.getElementById('upload_investment_id').value = investmentId;
    document.getElementById('upload_project_title').innerHTML = '<strong>' + projectTitle + '</strong>';
    document.getElementById('upload_total_amount').innerHTML = '<strong class="text-primary">$' + totalAmount.toFixed(2) + '</strong>';
    document.getElementById('upload_remaining_amount').innerHTML = '<strong class="text-warning">$' + remainingAmount.toFixed(2) + '</strong>';
    
    var paymentInput = document.getElementById('payment_amount');
    paymentInput.value = '';
    paymentInput.min = '0.01';
    paymentInput.max = remainingAmount.toFixed(2);
    paymentInput.step = '0.01';
    paymentInput.placeholder = 'Enter amount (max: $' + remainingAmount.toFixed(2) + ')';
    
    document.getElementById('payment_proof_file').value = '';
    document.getElementById('payment_notes').value = '';
    $('#uploadPaymentModal').modal('show');
}

function viewPaymentHistory(investmentId) {
    document.getElementById('paymentHistoryContent').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading payment history...</p></div>';
    $('#paymentHistoryModal').modal('show');
    
    // Fetch payment history via AJAX
    fetch('get-payment-history.php?investment_id=' + investmentId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('paymentHistoryContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('paymentHistoryContent').innerHTML = '<div class="alert alert-danger">Error loading payment history.</div>';
        });
}

function showRejectionReason(reason) {
    alert('Rejection Reason:\n\n' + reason);
}

// Validate file size and payment amount before upload
document.getElementById('uploadPaymentForm').addEventListener('submit', function(e) {
    var fileInput = document.getElementById('payment_proof_file');
    var paymentAmount = parseFloat(document.getElementById('payment_amount').value);
    var remainingAmountText = document.getElementById('upload_remaining_amount').innerText.replace('$', '').replace(',', '');
    var remainingAmount = parseFloat(remainingAmountText);
    
    if (fileInput.files.length > 0) {
        var fileSize = fileInput.files[0].size / 1024 / 1024; // Convert to MB
        if (fileSize > 5) {
            e.preventDefault();
            alert('File size must be less than 5MB. Your file is ' + fileSize.toFixed(2) + 'MB');
            return false;
        }
    }
    
    if (isNaN(paymentAmount) || paymentAmount < 0.01) {
        e.preventDefault();
        alert('Please enter a valid payment amount (minimum $0.01)');
        return false;
    }
    
    if (remainingAmount <= 0) {
        e.preventDefault();
        alert('No remaining amount to pay. The investment is already fully paid.');
        return false;
    }
    
    if (paymentAmount > remainingAmount) {
        e.preventDefault();
        alert('Payment amount ($' + paymentAmount.toFixed(2) + ') cannot exceed remaining amount ($' + remainingAmount.toFixed(2) + ')');
        return false;
    }
});
</script>