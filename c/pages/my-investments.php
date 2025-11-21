<?php 
/* c/pages/my-investments.php - Client My Investments Content */

$client_id = $_SESSION['client_id'];

// Get and display success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get client's bank account information
$bankInfoSQL = "SELECT bank_name, account_number, account_holder, iban_swift FROM client_bank_info WHERE client_id = ?";
$bankStmt = $conn->prepare($bankInfoSQL);
$bankStmt->bind_param("i", $client_id);
$bankStmt->execute();
$bankResult = $bankStmt->get_result();
$bankInfo = $bankResult->fetch_assoc();
$bankStmt->close();

// Get client's detailed investment information with partial payment support and withdrawal status
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
    i.duration,
    i.start_date,
    i.end_date,
    c.name as client_name,
    w.withdrawal_id,
    w.status as withdrawal_status,
    w.withdrawal_amount,
    w.request_date as withdrawal_request_date,
    w.withdrawal_proof
FROM client_investments ci
JOIN investments i ON ci.investment_id = i.id
JOIN clients c ON ci.client_id = c.id
LEFT JOIN (
    SELECT 
        w1.client_investment_id,
        w1.withdrawal_id,
        w1.status,
        w1.withdrawal_amount,
        w1.request_date,
        w1.withdrawal_proof
    FROM withdrawals w1
    INNER JOIN (
        SELECT 
            client_investment_id, 
            MAX(request_date) as latest_request
        FROM withdrawals 
        GROUP BY client_investment_id
    ) w2 ON w1.client_investment_id = w2.client_investment_id 
        AND w1.request_date = w2.latest_request
) w ON ci.id = w.client_investment_id
WHERE ci.client_id = ?
ORDER BY ci.investment_date DESC, ci.created_at DESC";

$stmt = $conn->prepare($investmentsSQL);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$investmentsResult = $stmt->get_result();

// Get withdrawal history for this client
$withdrawalHistorySQL = "SELECT 
    w.withdrawal_id,
    w.withdrawal_amount,
    w.request_date,
    w.status,
    w.withdrawal_proof,
    w.processed_date,
    w.admin_notes,
    i.title as project_title,
    ci.invested_amount
FROM withdrawals w
JOIN client_investments ci ON w.client_investment_id = ci.id
JOIN investments i ON w.investment_id = i.id
WHERE w.client_id = ?
ORDER BY w.request_date DESC";

$withdrawalStmt = $conn->prepare($withdrawalHistorySQL);
$withdrawalStmt->bind_param("i", $client_id);
$withdrawalStmt->execute();
$withdrawalHistoryResult = $withdrawalStmt->get_result();
?>

<div class="page-title">
  <div class="title_left">
    <h3>My Investments</h3>
  </div>
</div>

<div class="clearfix"></div>

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
                  <th>Withdraw Date</th>
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
                    
                    // Calculate withdraw date (end of project period)
                    $withdrawDate = null;
                    $withdrawDateDisplay = '<span class="text-muted">-</span>';
                    if ($investment['duration'] && $investment['end_date']) {
                        try {
                            $endDate = new DateTime($investment['end_date']);
                            $projectStart = clone $endDate;
                            $projectStart->modify('+1 day');
                            
                            $months = 0;
                            if (strpos($investment['duration'], 'month') !== false) {
                                $months = intval($investment['duration']);
                            } elseif (strpos($investment['duration'], 'year') !== false) {
                                $months = intval($investment['duration']) * 12;
                            }
                            
                            if ($months > 0) {
                                $withdrawDate = clone $projectStart;
                                $withdrawDate->modify("+{$months} months");
                                $withdrawDateDisplay = $withdrawDate->format('M d, Y');
                            }
                        } catch (Exception $e) {
                            // If date calculation fails, show dash
                        }
                    }
                    
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
                    <td><?= $withdrawDateDisplay ?></td>
                    <td><?= $status ?></td>
                    <td>
                      <?php 
                        // Check if withdrawal is available (project duration ended and investment is active/completed)
                        $canWithdraw = false;
                        $canCancelWithdrawal = false;
                        $withdrawalButtonDisabled = false;
                        $withdrawalButtonText = 'Request Withdrawal';
                        $withdrawalButtonClass = 'btn-success';
                        
                        if ($withdrawDate && $investment['status'] == 'active') {
                            $today = new DateTime();
                            if ($today >= $withdrawDate) {
                                // Project duration has ended
                                if ($investment['withdrawal_status'] && $investment['withdrawal_status'] != 'rejected') {
                                    // Withdrawal already requested (not rejected)
                                    $withdrawalButtonDisabled = true;
                                    switch($investment['withdrawal_status']) {
                                        case 'pending':
                                            $withdrawalButtonText = 'Withdrawal Pending';
                                            $withdrawalButtonClass = 'btn-warning';
                                            $canCancelWithdrawal = true; // Allow cancel for pending
                                            break;
                                        case 'approved':
                                            $withdrawalButtonText = 'Withdrawal Approved';
                                            $withdrawalButtonClass = 'btn-info';
                                            $canCancelWithdrawal = true; // Allow cancel for approved
                                            break;
                                        case 'completed':
                                            $withdrawalButtonText = 'Withdrawal Completed';
                                            $withdrawalButtonClass = 'btn-default';
                                            break;
                                    }
                                } else {
                                    // No active withdrawal or previous was rejected - allow new request
                                    $canWithdraw = true;
                                }
                            }
                        }
                      ?>
                      
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
                      
                      <?php if ($canWithdraw || $withdrawalButtonDisabled): ?>
                        <br style="margin-bottom: 5px;">
                        <button type="button" 
                                class="btn <?= $withdrawalButtonClass ?> btn-sm" 
                                onclick="showWithdrawalModal(<?= $investment['investment_id'] ?>, '<?= htmlspecialchars($investment['project_title']) ?>', <?= $isProfitRange ? $profitMin : $expectedProfit ?>, <?= $isProfitRange ? $profitMax : $expectedProfit ?>)"
                                <?= $withdrawalButtonDisabled ? 'disabled' : '' ?>>
                          <i class="fa fa-money"></i> <?= $withdrawalButtonText ?>
                        </button>
                        <?php if ($canCancelWithdrawal && $investment['withdrawal_id']): ?>
                          <button type="button" class="btn btn-danger btn-sm" 
                                  onclick="cancelClientWithdrawal(<?= $investment['withdrawal_id'] ?>, '<?= htmlspecialchars($investment['project_title']) ?>')">
                            <i class="fa fa-ban"></i> Cancel Request
                          </button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
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

<!-- Withdrawal History Section -->
<?php if ($withdrawalHistoryResult->num_rows > 0): ?>
<div class="row">
  <div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-history"></i> Withdrawal History</h2>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Project</th>
                <th>Investment Amount</th>
                <th>Withdrawal Amount</th>
                <th>Request Date</th>
                <th>Status</th>
                <th>Transfer Proof</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($withdrawal = $withdrawalHistoryResult->fetch_assoc()): ?>
                <?php
                  switch($withdrawal['status']) {
                    case 'pending':
                      $statusLabel = '<span class="label label-warning">Pending Review</span>';
                      break;
                    case 'approved':
                      $statusLabel = '<span class="label label-info">Approved - Processing</span>';
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
                  <td><strong><?= htmlspecialchars($withdrawal['project_title']) ?></strong></td>
                  <td><strong class="text-primary">$<?= number_format($withdrawal['invested_amount'], 2) ?></strong></td>
                  <td><strong class="text-success">$<?= number_format($withdrawal['withdrawal_amount'], 2) ?></strong></td>
                  <td>
                    <?= date('M d, Y', strtotime($withdrawal['request_date'])) ?>
                    <br><small class="text-muted"><?= date('g:i A', strtotime($withdrawal['request_date'])) ?></small>
                  </td>
                  <td><?= $statusLabel ?></td>
                  <td>
                    <?php if ($withdrawal['status'] == 'completed' && $withdrawal['withdrawal_proof']): ?>
                      <a href="../uploads/withdrawals/<?= htmlspecialchars($withdrawal['withdrawal_proof']) ?>" target="_blank" class="btn btn-success btn-xs">
                        <i class="fa fa-file-image-o"></i> View Proof
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($withdrawal['status'] == 'rejected' && $withdrawal['admin_notes']): ?>
                      <button type="button" class="btn btn-warning btn-sm" onclick="showWithdrawalRejectionReason('<?= htmlspecialchars(addslashes($withdrawal['admin_notes'])) ?>')">
                        <i class="fa fa-info-circle"></i> View Reason
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

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

function showWithdrawalModal(investmentId, projectTitle, profitMin, profitMax) {
    document.getElementById('withdrawal_investment_id').value = investmentId;
    document.getElementById('withdrawal_project_title').innerHTML = '<strong>' + projectTitle + '</strong>';
    
    var profitDisplay = '';
    if (profitMin == profitMax) {
        profitDisplay = '<strong class="text-success">$' + profitMin.toFixed(2) + '</strong>';
    } else {
        profitDisplay = '<strong class="text-success">$' + profitMin.toFixed(2) + ' - $' + profitMax.toFixed(2) + '</strong>';
    }
    document.getElementById('withdrawal_profit_amount').innerHTML = profitDisplay;
    
    document.getElementById('withdrawal_notes').value = '';
    $('#withdrawalModal').modal('show');
}

function showWithdrawalRejectionReason(reason) {
    alert('Rejection Reason:\n\n' + reason);
}

function cancelClientWithdrawal(withdrawalId, projectTitle) {
    if (confirm('Are you sure you want to cancel your withdrawal request for "' + projectTitle + '"?\n\nThis action cannot be undone.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cancel-withdrawal.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'withdrawal_id';
        idInput.value = withdrawalId;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- Withdrawal Request Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="withdrawalRequestForm" method="POST" action="request-withdrawal.php">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <i class="fa fa-money"></i> Request Profit Withdrawal
          </h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="withdrawal_investment_id" name="investment_id" required>
          
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            <strong>Project:</strong> <span id="withdrawal_project_title"></span>
          </div>
          
          <div class="form-group">
            <label>Expected Profit Amount</label>
            <div class="well well-sm" style="background: #f9f9f9;">
              <span id="withdrawal_profit_amount"></span>
            </div>
            <p class="help-block">
              <i class="fa fa-info-circle"></i> This is your expected profit from this investment. 
              The actual amount transferred may vary based on final project performance.
            </p>
          </div>
          
          <div class="form-group">
            <label for="withdrawal_notes">Bank Account Details / Instructions <span class="text-danger">*</span></label>
            
            <?php if (!empty($bankInfo)): ?>
              <!-- Show saved bank details with option to edit -->
              <div class="well well-sm" style="background: #f0f8ff; border-left: 4px solid #3498db;">
                <h5><i class="fa fa-bank"></i> Your Saved Bank Details:</h5>
                <div class="row">
                  <div class="col-md-6">
                    <strong>Bank Name:</strong> <?= htmlspecialchars($bankInfo['bank_name'] ?: 'Not specified') ?><br>
                    <strong>Account Holder:</strong> <?= htmlspecialchars($bankInfo['account_holder'] ?: 'Not specified') ?>
                  </div>
                  <div class="col-md-6">
                    <strong>Account Number:</strong> <?= htmlspecialchars($bankInfo['account_number'] ?: 'Not specified') ?><br>
                    <strong>IBAN/SWIFT:</strong> <?= htmlspecialchars($bankInfo['iban_swift'] ?: 'Not specified') ?>
                  </div>
                </div>
                <div style="margin-top: 10px;">
                  <label class="checkbox-inline">
                    <input type="checkbox" id="use_saved_bank_details" checked>
                    Use these bank details for withdrawal
                  </label>
                  <span class="pull-right">
                    <a href="profile.php" target="_blank" class="btn btn-xs btn-primary">
                      <i class="fa fa-edit"></i> Update Bank Details
                    </a>
                  </span>
                </div>
              </div>
            <?php endif; ?>
            
            <textarea class="form-control" id="withdrawal_notes" name="client_notes" rows="6" required><?php if (!empty($bankInfo)): ?>Bank Transfer Details:

Bank Name: <?= htmlspecialchars($bankInfo['bank_name'] ?: 'Not specified') ?>
Account Number: <?= htmlspecialchars($bankInfo['account_number'] ?: 'Not specified') ?>
Account Holder Name: <?= htmlspecialchars($bankInfo['account_holder'] ?: 'Not specified') ?>
IBAN/SWIFT Code: <?= htmlspecialchars($bankInfo['iban_swift'] ?: 'Not specified') ?>

Please transfer the withdrawal amount to the above account details.<?php else: ?>Please provide your bank account details for the transfer:

Bank Name:
Account Number:
Account Holder Name:
IBAN/SWIFT (if applicable):

Any additional instructions...<?php endif; ?></textarea>
            
            <p class="help-block">
              <i class="fa fa-exclamation-triangle"></i> Please ensure your bank details are correct. 
              Admin will process the withdrawal based on this information.
              <?php if (!empty($bankInfo)): ?>
                <br><i class="fa fa-info-circle"></i> Your saved bank details have been pre-filled. You can edit them above if needed.
              <?php endif; ?>
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fa fa-check"></i> Submit Withdrawal Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    <?php if (!empty($bankInfo)): ?>
    // Handle saved bank details checkbox
    $('#use_saved_bank_details').change(function() {
        var savedDetails = `Bank Transfer Details:

Bank Name: <?= htmlspecialchars($bankInfo['bank_name'] ?: 'Not specified') ?>
Account Number: <?= htmlspecialchars($bankInfo['account_number'] ?: 'Not specified') ?>
Account Holder Name: <?= htmlspecialchars($bankInfo['account_holder'] ?: 'Not specified') ?>
IBAN/SWIFT Code: <?= htmlspecialchars($bankInfo['iban_swift'] ?: 'Not specified') ?>

Please transfer the withdrawal amount to the above account details.`;

        var manualTemplate = `Please provide your bank account details for the transfer:

Bank Name:
Account Number:
Account Holder Name:
IBAN/SWIFT (if applicable):

Any additional instructions...`;

        if ($(this).is(':checked')) {
            $('#withdrawal_notes').val(savedDetails);
        } else {
            $('#withdrawal_notes').val(manualTemplate);
        }
    });
    <?php endif; ?>
    
    // Form submission validation
    $('#withdrawalRequestForm').on('submit', function(e) {
        var notes = $('#withdrawal_notes').val().trim();
        
        if (notes === '') {
            alert('Please provide bank account details for withdrawal.');
            e.preventDefault();
            return false;
        }
        
        console.log('Submitting withdrawal with notes length:', notes.length);
        console.log('Investment ID:', $('#withdrawal_investment_id').val());
        return true;
    });
    
    // Existing JavaScript for withdrawal modal
    window.openWithdrawalModal = function(investmentId, projectTitle, investedAmount, profitPercent, duration, startDate) {
        // Set form values
        $('#withdrawal_investment_id').val(investmentId);
        $('#withdrawal_project_title').text(projectTitle);
        $('#withdrawal_invested_amount').text('$' + parseFloat(investedAmount).toLocaleString());
        
        // Calculate expected profit
        var profit = parseFloat(investedAmount) * (parseFloat(profitPercent) / 100);
        $('#withdrawal_profit_amount').text('$' + profit.toLocaleString());
        
        // Show modal
        $('#withdrawalModal').modal('show');
    };
});
</script>