<?php 
/* c/pages/my-investments.php - Client My Investments Content */

$client_id = $_SESSION['client_id'];

// Get client's detailed investment information
$investmentsSQL = "SELECT 
    ci.id as investment_id,
    ci.invested_amount,
    ci.investment_date,
    ci.agreement_document,
    ci.status,
    ci.created_at,
    i.id as project_id,
    i.title as project_title,
    i.total_goal,
    i.profit_percent,
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
                  <th>Investment Date</th>
                  <th>Project Goal</th>
                  <th>Profit Rate</th>
                  <th>Expected Profit</th>
                  <th>Project Duration</th>
                  <th>Agreement</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while($investment = $investmentsResult->fetch_assoc()): ?>
                  <?php 
                    $expectedProfit = $investment['invested_amount'] * ($investment['profit_percent'] / 100);
                    $projectStarted = strtotime($investment['start_date']) <= time();
                    $projectEnded = strtotime($investment['end_date']) < time();
                    
                    // Display investment status based on database status and project timeline
                    switch($investment['status']) {
                      case 'pending':
                        $status = '<span class="label label-warning">Pending Approval</span>';
                        break;
                      case 'approved':
                        if ($projectEnded) {
                          $status = '<span class="label label-success">Completed</span>';
                        } elseif ($projectStarted) {
                          $status = '<span class="label label-info">Active</span>';
                        } else {
                          $status = '<span class="label label-primary">Approved</span>';
                        }
                        break;
                      case 'rejected':
                        $status = '<span class="label label-danger">Rejected</span>';
                        break;
                      case 'active':
                        $status = '<span class="label label-info">Active</span>';
                        break;
                      case 'completed':
                        $status = '<span class="label label-success">Completed</span>';
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
                      <?= date('M d, Y', strtotime($investment['investment_date'])) ?>
                      <br><small class="text-muted"><?= date('g:i A', strtotime($investment['created_at'])) ?></small>
                    </td>
                    <td>$<?= number_format($investment['total_goal'], 0) ?></td>
                    <td><span class="label label-success"><?= number_format($investment['profit_percent'], 1) ?>%</span></td>
                    <td><strong class="text-success">$<?= number_format($expectedProfit, 2) ?></strong></td>
                    <td>
                      <small>
                        <strong>Start:</strong> <?= date('M d, Y', strtotime($investment['start_date'])) ?><br>
                        <strong>End:</strong> <?= date('M d, Y', strtotime($investment['end_date'])) ?>
                      </small>
                    </td>
                    <td>
                      <?php if (!empty($investment['agreement_document'])): ?>
                        <button type="button" class="btn btn-success btn-xs" onclick="viewAgreement('<?= htmlspecialchars($investment['agreement_document']) ?>')" title="View Agreement">
                          <i class="fa fa-file-text"></i> View
                        </button>
                      <?php else: ?>
                        <span class="text-muted">No agreement</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $status ?></td>
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

<!-- Investment Details Modal -->
<div class="modal fade" id="investmentDetailsModal" tabindex="-1" role="dialog" aria-labelledby="investmentDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="investmentDetailsModalLabel">Investment Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="investmentDetailsContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-3x"></i>
          <p class="mt-2">Loading investment details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewAgreement(filename) {
    if (filename) {
        // Open the agreement document in a new window
        var url = '../uploads/agreements/' + filename;
        window.open(url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    } else {
        alert('No agreement document available.');
    }
}

function viewInvestmentDetails(investmentId) {
    // Show the modal and reset content
    document.getElementById('investmentDetailsContent').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-2">Loading investment details...</p></div>';
    $('#investmentDetailsModal').modal('show');
    
    // You can add AJAX call here to load detailed investment information
    // For now, we'll just show the modal
}
</script>