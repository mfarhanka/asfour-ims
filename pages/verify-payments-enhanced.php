<?php /* pages/verify-payments.php - Admin page to verify payment proofs and activate investments with partial payment support */
require_once __DIR__ . '/../config.php';

// Include payment transaction verification handler
if ($_POST && isset($_POST['verify_payment_transaction'])) {
    $transactionId = intval($_POST['verify_payment_transaction']);
    $adminId = $_SESSION['admin_id'];
    
    // Get transaction details
    $getSQL = "SELECT pt.client_investment_id, pt.payment_amount, ci.invested_amount, ci.total_paid
               FROM payment_transactions pt
               JOIN client_investments ci ON pt.client_investment_id = ci.id
               WHERE pt.id = ? AND pt.status = 'pending'";
    $stmt = $conn->prepare($getSQL);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $stmt->bind_result($investmentId, $paymentAmount, $investedAmount, $totalPaid);
    
    if ($stmt->fetch()) {
        $stmt->close();
        
        $conn->begin_transaction();
        try {
            // Mark payment transaction as verified
            $verifySQL = "UPDATE payment_transactions 
                          SET status = 'verified', 
                              verified_at = NOW(), 
                              verified_by = ? 
                          WHERE id = ?";
            $stmt = $conn->prepare($verifySQL);
            $stmt->bind_param("ii", $adminId, $transactionId);
            $stmt->execute();
            $stmt->close();
            
            // Update investment total_paid and remaining_amount
            $newTotalPaid = $totalPaid + $paymentAmount;
            $remainingAmount = $investedAmount - $newTotalPaid;
            $isFullyPaid = ($remainingAmount <= 0.01) ? 1 : 0; // 0.01 tolerance for rounding
            $newStatus = $isFullyPaid ? 'payment_pending' : 'payment_partial';
            
            $updateInvSQL = "UPDATE client_investments 
                            SET total_paid = ?, 
                                remaining_amount = ?, 
                                is_fully_paid = ?,
                                status = ?
                            WHERE id = ?";
            $stmt = $conn->prepare($updateInvSQL);
            $stmt->bind_param("ddisi", $newTotalPaid, $remainingAmount, $isFullyPaid, $newStatus, $investmentId);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            echo "<script>alert('Payment verified successfully!'); window.location.href = 'index.php?p=verify-payments';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error verifying payment: " . $e->getMessage() . "');</script>";
        }
    } else {
        $stmt->close();
        echo "<script>alert('Payment transaction not found!');</script>";
    }
}

// Handle payment transaction rejection
if ($_POST && isset($_POST['reject_payment_transaction'])) {
    $transactionId = intval($_POST['reject_payment_transaction']);
    $rejectionReason = trim($_POST['payment_rejection_reason']);
    
    $rejectSQL = "UPDATE payment_transactions 
                  SET status = 'rejected', 
                      rejection_reason = ? 
                  WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($rejectSQL);
    $stmt->bind_param("si", $rejectionReason, $transactionId);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<script>alert('Payment rejected. Client can upload a new payment.'); window.location.href = 'index.php?p=verify-payments';</script>";
    } else {
        echo "<script>alert('Error rejecting payment!');</script>";
    }
    $stmt->close();
}

// Handle agreement upload
if ($_POST && isset($_POST['upload_agreement_id'])) {
    $investmentId = intval($_POST['upload_agreement_id']);
    
    if (isset($_FILES['agreement_file']) && $_FILES['agreement_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/agreements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['agreement_file']['name'], PATHINFO_EXTENSION);
        $fileName = 'agreement_inv_' . $investmentId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['agreement_file']['tmp_name'], $uploadPath)) {
            $updateSQL = "UPDATE client_investments 
                         SET agreement_document = ?, 
                             agreement_uploaded = 1 
                         WHERE id = ?";
            $stmt = $conn->prepare($updateSQL);
            $stmt->bind_param("si", $fileName, $investmentId);
            
            if ($stmt->execute()) {
                echo "<script>alert('Agreement uploaded successfully!'); window.location.href = 'index.php?p=verify-payments';</script>";
            } else {
                echo "<script>alert('Error saving agreement!');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Error uploading agreement file!');</script>";
        }
    }
}

// Handle investment activation (requires full payment + agreement)
if ($_POST && isset($_POST['activate_investment'])) {
    $investmentId = intval($_POST['activate_investment']);
    $adminId = $_SESSION['admin_id'];
    
    // Check if fully paid and agreement uploaded
    $checkSQL = "SELECT is_fully_paid, agreement_uploaded, agreement_document 
                 FROM client_investments 
                 WHERE id = ?";
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("i", $investmentId);
    $stmt->execute();
    $stmt->bind_result($isFullyPaid, $agreementUploaded, $agreementDoc);
    $stmt->fetch();
    $stmt->close();
    
    if (!$isFullyPaid) {
        echo "<script>alert('Cannot activate: Investment not fully paid yet.');</script>";
    } elseif (!$agreementUploaded || empty($agreementDoc)) {
        echo "<script>alert('Cannot activate: Agreement document must be uploaded first.');</script>";
    } else {
        $activateSQL = "UPDATE client_investments 
                       SET status = 'active', 
                           payment_verified_at = NOW(), 
                           payment_verified_by = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($activateSQL);
        $stmt->bind_param("ii", $adminId, $investmentId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "<script>alert('Investment activated successfully!'); window.location.href = 'index.php?p=verify-payments';</script>";
        } else {
            echo "<script>alert('Error activating investment!');</script>";
        }
        $stmt->close();
    }
}

// Get investments with pending payments or ready to activate
$sql = "SELECT ci.id, ci.client_id, ci.investment_id, ci.invested_amount, ci.investment_date, 
               ci.total_paid, ci.remaining_amount, ci.is_fully_paid, ci.agreement_uploaded, ci.agreement_document,
               ci.created_at, ci.status,
               c.name as client_name, c.username as client_username, c.email as client_email, c.phone as client_phone,
               i.title as investment_title, i.total_goal, i.profit_percent, i.start_date, i.end_date
        FROM client_investments ci 
        LEFT JOIN clients c ON ci.client_id = c.id 
        LEFT JOIN investments i ON ci.investment_id = i.id 
        WHERE ci.status IN ('approved', 'payment_pending', 'payment_partial')
        ORDER BY ci.is_fully_paid DESC, ci.created_at ASC";
$result = $conn->query($sql);
?>

<div class="page-title">
  <div class="title_left">
    <h3>Verify Payments & Activate Investments</h3>
  </div>
</div>

<div class="clearfix"></div>

<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-check-square-o"></i> Payment Verification & Activation Queue</h2>
        <div class="clearfix"></div>
      </div>
      
      <div class="x_content">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Workflow:</strong> 
            1) Verify each payment transaction → 
            2) Upload agreement document → 
            3) Activate investment when fully paid
          </div>
          
          <div class="row">
            <?php while($row = $result->fetch_assoc()): 
              $expectedProfit = ($row['invested_amount'] * $row['profit_percent']) / 100;
              $totalReturn = $row['invested_amount'] + $expectedProfit;
              $paymentProgress = ($row['total_paid'] / $row['invested_amount']) * 100;
              
              // Get pending payment transactions for this investment
              $ptSQL = "SELECT id, payment_amount, payment_proof, payment_date, payment_notes, uploaded_at
                        FROM payment_transactions
                        WHERE client_investment_id = ? AND status = 'pending'
                        ORDER BY uploaded_at ASC";
              $ptStmt = $conn->prepare($ptSQL);
              $ptStmt->bind_param("i", $row['id']);
              $ptStmt->execute();
              $pendingPayments = $ptStmt->get_result();
              $ptStmt->close();
            ?>
              <div class="col-md-12">
                <div class="x_panel" style="border-left: 4px solid <?= $row['is_fully_paid'] ? '#26B99A' : '#3498DB' ?>;">
                  <div class="x_title">
                    <h2>
                      Investment #<?= $row['id'] ?>
                      <?php if ($row['is_fully_paid']): ?>
                        <span class="label label-success">Fully Paid</span>
                      <?php else: ?>
                        <span class="label label-info">Partial Payment</span>
                      <?php endif; ?>
                      <?php if ($row['agreement_uploaded']): ?>
                        <span class="label label-primary"><i class="fa fa-file-text"></i> Agreement Ready</span>
                      <?php else: ?>
                        <span class="label label-warning"><i class="fa fa-exclamation"></i> Need Agreement</span>
                      <?php endif; ?>
                    </h2>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="row">
                      <!-- Left Column: Client & Investment Info -->
                      <div class="col-md-5">
                        <h4>Investment Details</h4>
                        <table class="table table-bordered table-condensed">
                          <tr>
                            <th width="40%">Client</th>
                            <td>
                              <strong><?= htmlspecialchars($row['client_name']) ?></strong><br>
                              <small class="text-muted"><?= htmlspecialchars($row['client_username']) ?></small>
                            </td>
                          </tr>
                          <tr>
                            <th>Contact</th>
                            <td>
                              <small>
                                <i class="fa fa-envelope"></i> <?= htmlspecialchars($row['client_email']) ?><br>
                                <i class="fa fa-phone"></i> <?= htmlspecialchars($row['client_phone']) ?>
                              </small>
                            </td>
                          </tr>
                          <tr>
                            <th>Project</th>
                            <td><strong><?= htmlspecialchars($row['investment_title']) ?></strong></td>
                          </tr>
                          <tr>
                            <th>Total Investment</th>
                            <td><strong class="text-primary">$<?= number_format($row['invested_amount'], 2) ?></strong></td>
                          </tr>
                          <tr>
                            <th>Expected Profit</th>
                            <td>
                              <span class="text-success">$<?= number_format($expectedProfit, 2) ?></span> 
                              <small class="text-muted">(<?= $row['profit_percent'] ?>%)</small>
                            </td>
                          </tr>
                        </table>
                        
                        <!-- Payment Progress -->
                        <h5>Payment Progress</h5>
                        <div class="progress" style="height: 25px;">
                          <div class="progress-bar progress-bar-<?= $row['is_fully_paid'] ? 'success' : 'info' ?>" 
                               style="width: <?= min($paymentProgress, 100) ?>%">
                            <?= number_format($paymentProgress, 1) ?>%
                          </div>
                        </div>
                        <table class="table table-condensed" style="margin-top: 10px;">
                          <tr>
                            <td><strong>Total Paid:</strong></td>
                            <td class="text-right"><span class="text-success">$<?= number_format($row['total_paid'], 2) ?></span></td>
                          </tr>
                          <tr>
                            <td><strong>Remaining:</strong></td>
                            <td class="text-right"><span class="text-<?= $row['remaining_amount'] > 0 ? 'warning' : 'success' ?>">$<?= number_format($row['remaining_amount'], 2) ?></span></td>
                          </tr>
                        </table>
                      </div>
                      
                      <!-- Right Column: Pending Payments -->
                      <div class="col-md-7">
                        <h4>Pending Payment Transactions (<?= $pendingPayments->num_rows ?>)</h4>
                        <?php if ($pendingPayments->num_rows > 0): ?>
                          <?php while($pt = $pendingPayments->fetch_assoc()): ?>
                            <div class="panel panel-default" style="margin-bottom: 10px;">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-6">
                                    <strong>Amount: <span class="text-primary">$<?= number_format($pt['payment_amount'], 2) ?></span></strong><br>
                                    <small>Date: <?= date('M d, Y', strtotime($pt['payment_date'])) ?></small><br>
                                    <small>Uploaded: <?= date('M d, Y H:i', strtotime($pt['uploaded_at'])) ?></small>
                                    <?php if ($pt['payment_notes']): ?>
                                      <br><small><strong>Notes:</strong> <?= htmlspecialchars($pt['payment_notes']) ?></small>
                                    <?php endif; ?>
                                  </div>
                                  <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-info btn-sm" onclick="viewPaymentProof('<?= htmlspecialchars($pt['payment_proof']) ?>')">
                                      <i class="fa fa-eye"></i> View Proof
                                    </button><br><br>
                                    <button type="button" class="btn btn-success btn-sm" onclick="verifyPaymentTransaction(<?= $pt['id'] ?>, <?= $pt['payment_amount'] ?>)">
                                      <i class="fa fa-check"></i> Verify
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="showRejectPaymentModal(<?= $pt['id'] ?>)">
                                      <i class="fa fa-times"></i> Reject
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endwhile; ?>
                        <?php else: ?>
                          <div class="alert alert-warning">
                            <i class="fa fa-info-circle"></i> No pending payment transactions. Waiting for client to upload payment.
                          </div>
                        <?php endif; ?>
                        
                        <!-- Agreement & Activation Section -->
                        <?php if ($row['is_fully_paid']): ?>
                          <hr>
                          <h4>Agreement & Activation</h4>
                          <div class="panel panel-<?= $row['agreement_uploaded'] ? 'success' : 'warning' ?>">
                            <div class="panel-body">
                              <?php if ($row['agreement_uploaded']): ?>
                                <i class="fa fa-check-circle text-success"></i> 
                                <strong>Agreement Uploaded</strong><br>
                                <button type="button" class="btn btn-primary btn-sm" onclick="viewAgreement('<?= htmlspecialchars($row['agreement_document']) ?>')" style="margin-top: 10px;">
                                  <i class="fa fa-file-text"></i> View Agreement
                                </button>
                                <hr>
                                <button type="button" class="btn btn-success btn-lg btn-block" onclick="activateInvestment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')">
                                  <i class="fa fa-check-circle"></i> ACTIVATE INVESTMENT
                                </button>
                              <?php else: ?>
                                <i class="fa fa-exclamation-triangle text-warning"></i> 
                                <strong>Agreement Required</strong><br>
                                <small>Please upload the signed agreement before activating</small>
                                <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                                  <input type="hidden" name="upload_agreement_id" value="<?= $row['id'] ?>">
                                  <div class="form-group">
                                    <input type="file" name="agreement_file" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                  </div>
                                  <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-upload"></i> Upload Agreement
                                  </button>
                                </form>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-success text-center">
            <i class="fa fa-check-circle fa-3x"></i>
            <h4 style="margin-top: 15px;">All Investments Processed</h4>
            <p>No pending payments to verify!</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Payment Rejection Modal -->
<div class="modal fade" id="rejectPaymentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Reject Payment Transaction</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="reject_payment_transaction" id="reject_payment_transaction_id">
          <div class="alert alert-warning">
            <i class="fa fa-info-circle"></i> The payment will be rejected and the client can upload a new payment proof.
          </div>
          <div class="form-group">
            <label for="payment_rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" name="payment_rejection_reason" id="payment_rejection_reason" rows="4" required placeholder="e.g., Unclear image, wrong amount, wrong account details..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa fa-times"></i> Reject Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Payment Proof Viewer Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment Proof</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div id="paymentProofContent"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="openPaymentInNewTab()">
          <i class="fa fa-external-link"></i> Open in New Tab
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let currentPaymentProofUrl = '';

function verifyPaymentTransaction(transactionId, amount) {
    if (confirm('Verify this payment of $' + amount.toFixed(2) + '?\n\nThis will add it to the total paid amount.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'verify_payment_transaction';
        input.value = transactionId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectPaymentModal(transactionId) {
    document.getElementById('reject_payment_transaction_id').value = transactionId;
    document.getElementById('payment_rejection_reason').value = '';
    $('#rejectPaymentModal').modal('show');
}

function activateInvestment(id, clientName, projectTitle) {
    if (confirm('ACTIVATE this investment?\n\nClient: ' + clientName + '\nProject: ' + projectTitle + '\n\nThis will mark the investment as ACTIVE and start generating profits.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'activate_investment';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function viewPaymentProof(filename) {
    if (filename) {
        currentPaymentProofUrl = '../uploads/payments/' + filename;
        var ext = filename.split('.').pop().toLowerCase();
        
        if (ext === 'pdf') {
            document.getElementById('paymentProofContent').innerHTML = 
                '<embed src="' + currentPaymentProofUrl + '" type="application/pdf" width="100%" height="600px" />';
        } else {
            document.getElementById('paymentProofContent').innerHTML = 
                '<img src="' + currentPaymentProofUrl + '" class="img-responsive" style="max-width: 100%; height: auto;" />';
        }
        
        $('#paymentProofModal').modal('show');
    }
}

function viewAgreement(filename) {
    if (filename) {
        window.open('../uploads/agreements/' + filename, '_blank');
    }
}

function openPaymentInNewTab() {
    if (currentPaymentProofUrl) {
        window.open(currentPaymentProofUrl, '_blank');
    }
}
</script>
