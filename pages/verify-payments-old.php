<?php /* pages/verify-payments.php - Admin page to verify payment proofs and activate investments */
require_once __DIR__ . '/../config.php';

// Handle individual payment transaction verification
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
            $isFullyPaid = ($remainingAmount <= 0) ? 1 : 0;
            
            $updateInvSQL = "UPDATE client_investments 
                            SET total_paid = ?, 
                                remaining_amount = ?, 
                                is_fully_paid = ?
                            WHERE id = ?";
            $stmt = $conn->prepare($updateInvSQL);
            $stmt->bind_param("ddii", $newTotalPaid, $remainingAmount, $isFullyPaid, $investmentId);
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
        ORDER BY ci.created_at ASC";
$result = $conn->query($sql);
?>

<div class="page-title">
  <div class="title_left">
    <h3>Verify Payment Proofs</h3>
  </div>
</div>

<div class="clearfix"></div>

<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="x_panel">
      <div class="x_title">
        <h2><i class="fa fa-check-square-o"></i> Payment Verification Queue</h2>
        <div class="clearfix"></div>
      </div>
      
      <div class="x_content">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> <strong>Step 3 of 3:</strong> Verify payment proofs uploaded by clients. Once verified, investments will be activated.
          </div>
          
          <div class="row">
            <?php while($row = $result->fetch_assoc()): 
              $expectedProfit = ($row['invested_amount'] * $row['profit_percent']) / 100;
              $totalReturn = $row['invested_amount'] + $expectedProfit;
            ?>
              <div class="col-md-6">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Investment #<?= $row['id'] ?></h2>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <table class="table table-bordered">
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
                          <i class="fa fa-envelope"></i> <?= htmlspecialchars($row['client_email']) ?><br>
                          <i class="fa fa-phone"></i> <?= htmlspecialchars($row['client_phone']) ?>
                        </td>
                      </tr>
                      <tr>
                        <th>Project</th>
                        <td><strong><?= htmlspecialchars($row['investment_title']) ?></strong></td>
                      </tr>
                      <tr>
                        <th>Investment Amount</th>
                        <td><strong class="text-primary">$<?= number_format($row['invested_amount'], 2) ?></strong></td>
                      </tr>
                      <tr>
                        <th>Expected Profit</th>
                        <td>
                          <span class="text-success">$<?= number_format($expectedProfit, 2) ?></span><br>
                          <small class="text-muted">Total Return: $<?= number_format($totalReturn, 2) ?></small>
                        </td>
                      </tr>
                      <tr>
                        <th>Investment Date</th>
                        <td><?= date('M d, Y', strtotime($row['investment_date'])) ?></td>
                      </tr>
                      <tr>
                        <th>Payment Uploaded</th>
                        <td><?= date('M d, Y H:i', strtotime($row['payment_proof_uploaded_at'])) ?></td>
                      </tr>
                      <tr>
                        <th>Payment Proof</th>
                        <td>
                          <?php if (!empty($row['payment_proof'])): ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="viewPaymentProof('<?= htmlspecialchars($row['payment_proof']) ?>')" title="View Payment Proof">
                              <i class="fa fa-eye"></i> View Payment Proof
                            </button>
                          <?php else: ?>
                            <span class="text-muted">No payment proof</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    </table>
                    
                    <div class="text-center" style="margin-top: 15px;">
                      <button type="button" class="btn btn-success" onclick="verifyPayment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')">
                        <i class="fa fa-check"></i> Verify & Activate Investment
                      </button>
                      <button type="button" class="btn btn-danger" onclick="showRejectPaymentModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')">
                        <i class="fa fa-times"></i> Reject Payment
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-success text-center">
            <i class="fa fa-check-circle fa-3x"></i>
            <h4 style="margin-top: 15px;">No Payments to Verify</h4>
            <p>All payment proofs have been processed!</p>
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
          <h5 class="modal-title">Reject Payment Proof</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="reject_payment" id="reject_payment_id">
          <p id="reject_payment_details"></p>
          <div class="alert alert-warning">
            <i class="fa fa-info-circle"></i> The payment proof will be rejected and the client will be able to upload a new one.
          </div>
          <div class="form-group">
            <label for="payment_rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" name="payment_rejection_reason" id="payment_rejection_reason" rows="4" required placeholder="Please provide a clear reason (e.g., unclear image, wrong amount, wrong account, etc.)"></textarea>
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

function verifyPayment(id, clientName, projectTitle) {
    if (confirm('Verify and activate this investment?\n\nClient: ' + clientName + '\nProject: ' + projectTitle + '\n\nThe investment will be marked as ACTIVE.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'verify_payment';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectPaymentModal(id, clientName, projectTitle) {
    document.getElementById('reject_payment_id').value = id;
    document.getElementById('reject_payment_details').innerHTML = 
        '<strong>Client:</strong> ' + clientName + '<br>' +
        '<strong>Project:</strong> ' + projectTitle;
    document.getElementById('payment_rejection_reason').value = '';
    $('#rejectPaymentModal').modal('show');
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

function openPaymentInNewTab() {
    if (currentPaymentProofUrl) {
        window.open(currentPaymentProofUrl, '_blank');
    }
}
</script>
