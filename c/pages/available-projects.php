<?php 
/* c/pages/available-projects.php - Available Investment Projects */

$client_id = $_SESSION['client_id'];

// Get available investment projects
$projectsSQL = "SELECT 
    i.id,
    i.title,
    i.total_goal,
    i.profit_percent,
    i.start_date,
    i.end_date,
    i.created_at,
    COALESCE(SUM(ci.invested_amount), 0) as total_invested,
    COUNT(ci.id) as investor_count
FROM investments i
LEFT JOIN client_investments ci ON i.id = ci.investment_id
WHERE i.start_date > NOW() OR (i.start_date <= NOW() AND i.end_date >= NOW())
GROUP BY i.id
ORDER BY i.created_at DESC";

// Use traditional query for projects (no parameters needed)
$projectsResult = $conn->query($projectsSQL);

// Get client's existing investments to check if already invested
$clientInvestmentsSQL = "SELECT investment_id FROM client_investments WHERE client_id = ?";
$stmt = $conn->prepare($clientInvestmentsSQL);
$stmt->bind_param("i", $client_id);
$stmt->execute();

// Use bind_result for compatibility
$stmt->bind_result($investment_id);
$clientInvestments = [];
while($stmt->fetch()) {
    $clientInvestments[] = $investment_id;
}
$stmt->close();
?>

<div class="page-title">
  <div class="title_left">
    <h3>Available Projects</h3>
  </div>
</div>

<div class="clearfix"></div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
    <strong>Success!</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_messages'])): ?>
  <div class="alert alert-danger alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
    <strong>Error!</strong>
    <ul style="margin-bottom: 0; margin-top: 10px;">
      <?php foreach ($_SESSION['error_messages'] as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php unset($_SESSION['error_messages']); ?>
<?php endif; ?>

<div class="row">
  <?php if ($projectsResult->num_rows > 0): ?>
    <?php while($project = $projectsResult->fetch_assoc()): ?>
      <?php 
        $fundingProgress = ($project['total_invested'] / $project['total_goal']) * 100;
        $isInvested = in_array($project['id'], $clientInvestments);
        $projectStarted = strtotime($project['start_date']) <= time();
        $projectEnded = strtotime($project['end_date']) < time();
        
        if ($projectEnded) {
          $statusLabel = '<span class="label label-default">Ended</span>';
          $canInvest = false;
        } elseif ($projectStarted) {
          $statusLabel = '<span class="label label-info">Active</span>';
          $canInvest = !$isInvested;
        } else {
          $statusLabel = '<span class="label label-warning">Upcoming</span>';
          $canInvest = !$isInvested;
        }
      ?>
      
      <div class="col-md-6 col-sm-12">
        <div class="x_panel">
          <div class="x_title">
            <h2>
              <?= htmlspecialchars($project['title']) ?> 
              <?= $statusLabel ?>
              <?php if ($isInvested): ?>
                <span class="label label-success">Invested</span>
              <?php endif; ?>
            </h2>
            <div class="clearfix"></div>
          </div>
          
          <div class="x_content">
            <div class="project-details">
              <p class="text-muted">Investment opportunity with excellent returns and secure funding.</p>
              
              <div class="row" style="margin-bottom: 15px;">
                <div class="col-xs-6">
                  <strong>Investment Goal:</strong><br>
                  <span class="text-primary">$<?= number_format($project['total_goal'], 0) ?></span>
                </div>
                <div class="col-xs-6">
                  <strong>Profit Rate:</strong><br>
                  <span class="text-success"><?= number_format($project['profit_percent'], 1) ?>%</span>
                </div>
              </div>
              
              <div class="row" style="margin-bottom: 15px;">
                <div class="col-xs-6">
                  <strong>Start Date:</strong><br>
                  <small><?= date('M d, Y', strtotime($project['start_date'])) ?></small>
                </div>
                <div class="col-xs-6">
                  <strong>End Date:</strong><br>
                  <small><?= date('M d, Y', strtotime($project['end_date'])) ?></small>
                </div>
              </div>
              
              <div class="progress-info">
                <div class="row">
                  <div class="col-xs-6">
                    <strong>Funding Progress:</strong>
                  </div>
                  <div class="col-xs-6 text-right">
                    <strong><?= number_format($fundingProgress, 1) ?>%</strong>
                  </div>
                </div>
                <div class="progress">
                  <div class="progress-bar progress-bar-success" style="width: <?= min($fundingProgress, 100) ?>%">
                  </div>
                </div>
                <div class="funding-stats">
                  <small class="text-muted">
                    $<?= number_format($project['total_invested'], 0) ?> raised of $<?= number_format($project['total_goal'], 0) ?> goal
                    • <?= $project['investor_count'] ?> investor(s)
                  </small>
                </div>
              </div>
              
              <div style="margin-top: 20px;">
                <?php if ($canInvest && !$projectEnded): ?>
                  <button class="btn btn-primary btn-sm" onclick="showInvestModal(<?= $project['id'] ?>, '<?= htmlspecialchars($project['title']) ?>', <?= $project['total_goal'] ?>, <?= $project['profit_percent'] ?>)">
                    <i class="fa fa-plus"></i> Invest Now
                  </button>
                <?php elseif ($isInvested): ?>
                  <button class="btn btn-success btn-sm" disabled>
                    <i class="fa fa-check"></i> Already Invested
                  </button>
                <?php else: ?>
                  <button class="btn btn-default btn-sm" disabled>
                    <i class="fa fa-ban"></i> Not Available
                  </button>
                <?php endif; ?>
                
                <button class="btn btn-info btn-sm" onclick="showProjectDetails(<?= $project['id'] ?>)">
                  <i class="fa fa-info-circle"></i> View Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="col-md-12">
      <div class="x_panel">
        <div class="x_content">
          <div class="text-center" style="padding: 50px;">
            <i class="fa fa-search fa-5x text-muted"></i>
            <h3 style="margin-top: 20px;">No Available Projects</h3>
            <p class="text-muted">There are no investment projects available at the moment. Please check back later!</p>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Investment Modal -->
<div class="modal fade" id="investModal" tabindex="-1" role="dialog" aria-labelledby="investModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="investModalLabel">Make Investment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="investmentForm" method="POST" action="invest.php">
        <div class="modal-body">
          <input type="hidden" id="investment_id" name="investment_id" value="">
          
          <div class="form-group">
            <label for="project_title">Project:</label>
            <input type="text" class="form-control" id="project_title" readonly>
          </div>
          
          <div class="form-group">
            <label for="investment_amount">Investment Amount ($):</label>
            <input type="number" class="form-control" id="investment_amount" name="investment_amount" 
                   min="100" step="0.01" required placeholder="Minimum $100">
            <small class="form-text text-muted">Minimum investment amount is $100</small>
          </div>
          
          <div class="form-group">
            <label for="investment_date">Investment Date:</label>
            <input type="date" class="form-control" id="investment_date" name="investment_date" 
                   value="<?= date('Y-m-d') ?>" required>
          </div>
          
          <div class="investment-summary" id="investmentSummary" style="display: none;">
            <div class="alert alert-info">
              <h6><strong>Investment Summary:</strong></h6>
              <div id="summaryContent"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check"></i> Confirm Investment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Project Details Modal -->
<div class="modal fade" id="projectDetailsModal" tabindex="-1" role="dialog" aria-labelledby="projectDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectDetailsModalLabel">Project Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="projectDetailsContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-3x"></i>
          <p class="mt-2">Loading project details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentProfitPercent = 0;

function showInvestModal(investmentId, title, goal, profitPercent) {
    document.getElementById('investment_id').value = investmentId;
    document.getElementById('project_title').value = title;
    currentProfitPercent = profitPercent;
    
    // Clear previous values
    document.getElementById('investment_amount').value = '';
    document.getElementById('investmentSummary').style.display = 'none';
    
    $('#investModal').modal('show');
}

function showProjectDetails(projectId) {
    document.getElementById('projectDetailsContent').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-2">Loading project details...</p></div>';
    $('#projectDetailsModal').modal('show');
    
    // You can add AJAX call here to load detailed project information
    setTimeout(function() {
        document.getElementById('projectDetailsContent').innerHTML = '<p>Detailed project information would be loaded here via AJAX.</p>';
    }, 1000);
}

// Calculate investment summary when amount changes
document.getElementById('investment_amount').addEventListener('input', function() {
    const amount = parseFloat(this.value);
    if (amount >= 100) {
        const expectedProfit = amount * (currentProfitPercent / 100);
        const totalReturn = amount + expectedProfit;
        
        const summaryHtml = `
            <div class="row">
                <div class="col-xs-4"><strong>Investment:</strong><br>$${amount.toFixed(2)}</div>
                <div class="col-xs-4"><strong>Expected Profit:</strong><br>$${expectedProfit.toFixed(2)}</div>
                <div class="col-xs-4"><strong>Total Return:</strong><br>$${totalReturn.toFixed(2)}</div>
            </div>
        `;
        
        document.getElementById('summaryContent').innerHTML = summaryHtml;
        document.getElementById('investmentSummary').style.display = 'block';
    } else {
        document.getElementById('investmentSummary').style.display = 'none';
    }
});

// Form validation
document.getElementById('investmentForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('investment_amount').value);
    if (amount < 100) {
        e.preventDefault();
        alert('Minimum investment amount is $100');
        return false;
    }
});
</script>