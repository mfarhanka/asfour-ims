<?php
/* c/get-payment-history.php - Get payment transaction history for an investment */

session_start();

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit();
}

// Include database connection
include '../config.php';

$client_id = $_SESSION['client_id'];
$investment_id = isset($_GET['investment_id']) ? intval($_GET['investment_id']) : 0;

if ($investment_id <= 0) {
    echo '<div class="alert alert-danger">Invalid investment ID.</div>';
    exit();
}

// Get investment details
$invSQL = "SELECT ci.invested_amount, ci.total_paid, ci.remaining_amount, ci.is_fully_paid,
                  i.title as project_title
           FROM client_investments ci
           JOIN investments i ON ci.investment_id = i.id
           WHERE ci.id = ? AND ci.client_id = ?";
$stmt = $conn->prepare($invSQL);
$stmt->bind_param("ii", $investment_id, $client_id);
$stmt->execute();
$stmt->bind_result($invested_amount, $total_paid, $remaining_amount, $is_fully_paid, $project_title);

if (!$stmt->fetch()) {
    $stmt->close();
    echo '<div class="alert alert-danger">Investment not found.</div>';
    exit();
}
$stmt->close();

// Get payment transactions
$ptSQL = "SELECT id, payment_amount, payment_proof, payment_date, payment_notes, 
                 status, uploaded_at, verified_at, rejection_reason
          FROM payment_transactions
          WHERE client_investment_id = ?
          ORDER BY payment_date DESC, uploaded_at DESC";
$stmt = $conn->prepare($ptSQL);
$stmt->bind_param("i", $investment_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h4><?= htmlspecialchars($project_title) ?></h4>

<div class="row" style="margin-bottom: 20px;">
  <div class="col-md-4">
    <div class="panel panel-primary">
      <div class="panel-body text-center">
        <h5>Total Investment</h5>
        <h3>$<?= number_format($invested_amount, 2) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-success">
      <div class="panel-body text-center">
        <h5>Total Paid</h5>
        <h3>$<?= number_format($total_paid, 2) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-<?= $remaining_amount > 0 ? 'warning' : 'success' ?>">
      <div class="panel-body text-center">
        <h5>Remaining</h5>
        <h3>$<?= number_format($remaining_amount, 2) ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="progress" style="height: 30px; margin-bottom: 20px;">
  <div class="progress-bar progress-bar-<?= $is_fully_paid ? 'success' : 'info' ?>" 
       style="width: <?= min(($total_paid / $invested_amount) * 100, 100) ?>%">
    <?= number_format(($total_paid / $invested_amount) * 100, 1) ?>% Paid
  </div>
</div>

<?php if ($result->num_rows > 0): ?>
  <h5>Payment Transactions (<?= $result->num_rows ?>)</h5>
  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Date</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Proof</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php while($pt = $result->fetch_assoc()): ?>
          <tr>
            <td>
              <strong><?= date('M d, Y', strtotime($pt['payment_date'])) ?></strong><br>
              <small class="text-muted">Uploaded: <?= date('M d H:i', strtotime($pt['uploaded_at'])) ?></small>
            </td>
            <td><strong class="text-primary">$<?= number_format($pt['payment_amount'], 2) ?></strong></td>
            <td>
              <?php if ($pt['status'] == 'verified'): ?>
                <span class="label label-success">Verified</span><br>
                <small class="text-muted"><?= date('M d H:i', strtotime($pt['verified_at'])) ?></small>
              <?php elseif ($pt['status'] == 'rejected'): ?>
                <span class="label label-danger">Rejected</span><br>
                <small class="text-danger"><?= htmlspecialchars($pt['rejection_reason']) ?></small>
              <?php else: ?>
                <span class="label label-warning">Pending Verification</span>
              <?php endif; ?>
            </td>
            <td>
              <button type="button" class="btn btn-info btn-xs" onclick="window.open('../uploads/payments/<?= htmlspecialchars($pt['payment_proof']) ?>', '_blank')">
                <i class="fa fa-eye"></i> View
              </button>
            </td>
            <td>
              <?php if ($pt['payment_notes']): ?>
                <small><?= htmlspecialchars($pt['payment_notes']) ?></small>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i> No payment transactions yet. Upload your first payment to get started!
  </div>
<?php endif; ?>

<?php $stmt->close(); ?>
