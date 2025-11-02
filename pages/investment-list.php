<?php /* pages/investment-list.php */
require_once __DIR__ . '/../config.php';

// Handle investment deletion
if ($_POST && isset($_POST['deleteInvestment'])) {
    $investmentId = intval($_POST['deleteInvestment']);
    $deleteSQL = "DELETE FROM investments WHERE id = ?";
    $stmt = $conn->prepare($deleteSQL);
    $stmt->bind_param("i", $investmentId);
    
    if ($stmt->execute()) {
        echo "<script>alert('Investment deleted successfully!'); window.location.href = 'index.php?p=investment-list';</script>";
    } else {
        echo "<script>alert('Error deleting investment!');</script>";
    }
    $stmt->close();
}

// Handle adding new investment
if ($_POST && isset($_POST['investmentTitle'])) {
    $title = trim($_POST['investmentTitle']);
    $totalGoal = floatval($_POST['investmentTotalGoal']);
    $profitType = trim($_POST['profitType']);
    $startDate = trim($_POST['investmentStartDate']);
    $endDate = trim($_POST['investmentEndDate']);
    
    if ($profitType === 'fixed') {
        $profitPercent = floatval($_POST['investmentProfitPercent']);
        $profitPercentMin = $profitPercent;
        $profitPercentMax = $profitPercent;
    } else {
        $profitPercent = floatval($_POST['investmentProfitPercentMin']); // Store min as default
        $profitPercentMin = floatval($_POST['investmentProfitPercentMin']);
        $profitPercentMax = floatval($_POST['investmentProfitPercentMax']);
    }
    
    $insertSQL = "INSERT INTO investments (title, total_goal, profit_percent, profit_percent_min, profit_percent_max, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sddddss", $title, $totalGoal, $profitPercent, $profitPercentMin, $profitPercentMax, $startDate, $endDate);
    
    if ($stmt->execute()) {
        echo "<script>alert('Investment added successfully!'); window.location.href = 'index.php?p=investment-list';</script>";
    } else {
        echo "<script>alert('Error adding investment!');</script>";
    }
    $stmt->close();
}

// Handle updating existing investment
if ($_POST && isset($_POST['editInvestmentId'])) {
    $investmentId = intval($_POST['editInvestmentId']);
    $title = trim($_POST['editInvestmentTitle']);
    $totalGoal = floatval($_POST['editInvestmentTotalGoal']);
    $profitType = trim($_POST['editProfitType']);
    $startDate = trim($_POST['editInvestmentStartDate']);
    $endDate = trim($_POST['editInvestmentEndDate']);
    
    if ($profitType === 'fixed') {
        $profitPercent = floatval($_POST['editInvestmentProfitPercent']);
        $profitPercentMin = $profitPercent;
        $profitPercentMax = $profitPercent;
    } else {
        $profitPercent = floatval($_POST['editInvestmentProfitPercentMin']); // Store min as default
        $profitPercentMin = floatval($_POST['editInvestmentProfitPercentMin']);
        $profitPercentMax = floatval($_POST['editInvestmentProfitPercentMax']);
    }
    
    $updateSQL = "UPDATE investments SET title = ?, total_goal = ?, profit_percent = ?, profit_percent_min = ?, profit_percent_max = ?, start_date = ?, end_date = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSQL);
    $stmt->bind_param("sddddssi", $title, $totalGoal, $profitPercent, $profitPercentMin, $profitPercentMax, $startDate, $endDate, $investmentId);
    
    if ($stmt->execute()) {
        echo "<script>alert('Investment updated successfully!'); window.location.href = 'index.php?p=investment-list';</script>";
    } else {
        echo "<script>alert('Error updating investment!');</script>";
    }
    $stmt->close();
}

$sql = "SELECT i.id, i.title, i.total_goal, i.profit_percent, i.profit_percent_min, i.profit_percent_max, i.start_date, i.end_date, i.created_at,
               COALESCE(SUM(ci.invested_amount), 0) as total_invested
        FROM investments i 
        LEFT JOIN client_investments ci ON i.id = ci.investment_id 
        GROUP BY i.id, i.title, i.total_goal, i.profit_percent, i.profit_percent_min, i.profit_percent_max, i.start_date, i.end_date, i.created_at
        ORDER BY i.created_at DESC";
$result = $conn->query($sql);
?>
<div class="page-title">
  <div class="title_left">
    <h3>Investment Management</h3>
  </div>
</div>
<div class="clearfix"></div>
<div class="row">
  <div class="col-md-12 col-sm-12 ">
    <div class="x_panel">
      <div class="x_title">
        <h2>Investments</h2>
        <div class="clearfix"></div>
        <div style="float:right; margin-top:-30px;">
          <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addInvestmentModal">Add Investment</button>
        </div>
      </div>
      
      <!-- Add Investment Modal -->
      <div class="modal fade" id="addInvestmentModal" tabindex="-1" role="dialog" aria-labelledby="addInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="addInvestmentModalLabel">Add Investment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="investmentTitle">Title</label>
                  <input type="text" class="form-control" id="investmentTitle" name="investmentTitle" required>
                </div>
                <div class="form-group">
                  <label for="investmentTotalGoal">Total Goal</label>
                  <input type="number" step="0.01" class="form-control" id="investmentTotalGoal" name="investmentTotalGoal" required>
                </div>
                <div class="form-group">
                  <label>Profit Type</label>
                  <div>
                    <label class="radio-inline">
                      <input type="radio" name="profitType" value="fixed" checked onchange="toggleProfitFields(false)"> Fixed Percentage
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="profitType" value="range" onchange="toggleProfitFields(false)"> Range (e.g., 20% - 25%)
                    </label>
                  </div>
                </div>
                <div class="form-group" id="fixedProfitGroup">
                  <label for="investmentProfitPercent">Investment Profit Percent (%)</label>
                  <input type="number" step="0.01" min="0" max="100" class="form-control" id="investmentProfitPercent" name="investmentProfitPercent">
                </div>
                <div id="rangeProfitGroup" style="display: none;">
                  <div class="form-group">
                    <label for="investmentProfitPercentMin">Minimum Profit Percent (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="investmentProfitPercentMin" name="investmentProfitPercentMin" placeholder="e.g., 20">
                  </div>
                  <div class="form-group">
                    <label for="investmentProfitPercentMax">Maximum Profit Percent (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="investmentProfitPercentMax" name="investmentProfitPercentMax" placeholder="e.g., 25">
                  </div>
                </div>
                <div class="form-group">
                  <label for="investmentStartDate">Start Date</label>
                  <input type="date" class="form-control" id="investmentStartDate" name="investmentStartDate" required>
                </div>
                <div class="form-group">
                  <label for="investmentEndDate">End Date</label>
                  <input type="date" class="form-control" id="investmentEndDate" name="investmentEndDate" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Investment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Edit Investment Modal -->
      <div class="modal fade" id="editInvestmentModal" tabindex="-1" role="dialog" aria-labelledby="editInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="editInvestmentModalLabel">Edit Investment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="editInvestmentId" name="editInvestmentId">
                <div class="form-group">
                  <label for="editInvestmentTitle">Title</label>
                  <input type="text" class="form-control" id="editInvestmentTitle" name="editInvestmentTitle" required>
                </div>
                <div class="form-group">
                  <label for="editInvestmentTotalGoal">Total Goal</label>
                  <input type="number" step="0.01" class="form-control" id="editInvestmentTotalGoal" name="editInvestmentTotalGoal" required>
                </div>
                <div class="form-group">
                  <label>Profit Type</label>
                  <div>
                    <label class="radio-inline">
                      <input type="radio" name="editProfitType" value="fixed" checked onchange="toggleProfitFields(true)"> Fixed Percentage
                    </label>
                    <label class="radio-inline">
                      <input type="radio" name="editProfitType" value="range" onchange="toggleProfitFields(true)"> Range (e.g., 20% - 25%)
                    </label>
                  </div>
                </div>
                <div class="form-group" id="editFixedProfitGroup">
                  <label for="editInvestmentProfitPercent">Investment Profit Percent (%)</label>
                  <input type="number" step="0.01" min="0" max="100" class="form-control" id="editInvestmentProfitPercent" name="editInvestmentProfitPercent">
                </div>
                <div id="editRangeProfitGroup" style="display: none;">
                  <div class="form-group">
                    <label for="editInvestmentProfitPercentMin">Minimum Profit Percent (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="editInvestmentProfitPercentMin" name="editInvestmentProfitPercentMin" placeholder="e.g., 20">
                  </div>
                  <div class="form-group">
                    <label for="editInvestmentProfitPercentMax">Maximum Profit Percent (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="editInvestmentProfitPercentMax" name="editInvestmentProfitPercentMax" placeholder="e.g., 25">
                  </div>
                </div>
                <div class="form-group">
                  <label for="editInvestmentStartDate">Start Date</label>
                  <input type="date" class="form-control" id="editInvestmentStartDate" name="editInvestmentStartDate" required>
                </div>
                <div class="form-group">
                  <label for="editInvestmentEndDate">End Date</label>
                  <input type="date" class="form-control" id="editInvestmentEndDate" name="editInvestmentEndDate" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Update Investment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- View Investment Clients Modal -->
      <div class="modal fade" id="viewClientsModal" tabindex="-1" role="dialog" aria-labelledby="viewClientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewClientsModalLabel">Investment Clients</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div id="clientsTableContainer">
                <div class="text-center">
                  <i class="fa fa-spinner fa-spin"></i> Loading clients...
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="x_content">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Total Goal</th>
              <th>Total Invested</th>
              <th>Progress</th>
              <th>Profit Percent</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Created Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): 
                $progress_percent = $row['total_goal'] > 0 ? ($row['total_invested'] / $row['total_goal']) * 100 : 0;
                $progress_class = $progress_percent >= 100 ? 'success' : ($progress_percent >= 50 ? 'warning' : 'info');
                
                // Format profit percent display
                $profitDisplay = '';
                if ($row['profit_percent_min'] && $row['profit_percent_max'] && $row['profit_percent_min'] != $row['profit_percent_max']) {
                    $profitDisplay = number_format($row['profit_percent_min'], 1) . '% - ' . number_format($row['profit_percent_max'], 1) . '%';
                } else {
                    $profitDisplay = number_format($row['profit_percent'], 1) . '%';
                }
              ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['title']) ?></td>
                  <td>$<?= number_format($row['total_goal'], 2) ?></td>
                  <td>$<?= number_format($row['total_invested'], 2) ?></td>
                  <td>
                    <div class="progress" style="margin-bottom: 0;">
                      <div class="progress-bar progress-bar-<?= $progress_class ?>" style="width: <?= min($progress_percent, 100) ?>%">
                        <?= number_format($progress_percent, 1) ?>%
                      </div>
                    </div>
                  </td>
                  <td><?= $profitDisplay ?></td>
                  <td><?= htmlspecialchars($row['start_date']) ?></td>
                  <td><?= htmlspecialchars($row['end_date']) ?></td>
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                  <td>
                    <button type="button" class="btn btn-info btn-sm" onclick="viewInvestmentClients(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')" title="View Clients" style="margin-right: 5px;">
                      <i class="fa fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="editInvestment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>', <?= $row['total_goal'] ?>, <?= $row['profit_percent'] ?>, <?= $row['profit_percent_min'] ?? 'null' ?>, <?= $row['profit_percent_max'] ?? 'null' ?>, '<?= htmlspecialchars($row['start_date']) ?>', '<?= htmlspecialchars($row['end_date']) ?>')" title="Edit Investment" style="margin-right: 5px;">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeInvestment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')" title="Remove Investment">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="10">No investments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function toggleProfitFields(isEdit) {
    var prefix = isEdit ? 'edit' : '';
    var profitType = document.querySelector('input[name="' + (isEdit ? 'editProfitType' : 'profitType') + '"]:checked').value;
    
    var fixedGroup = document.getElementById(prefix + (isEdit ? 'F' : 'f') + 'ixedProfitGroup');
    var rangeGroup = document.getElementById(prefix + (isEdit ? 'R' : 'r') + 'angeProfitGroup');
    
    if (profitType === 'fixed') {
        fixedGroup.style.display = 'block';
        rangeGroup.style.display = 'none';
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercent').required = true;
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercentMin').required = false;
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercentMax').required = false;
    } else {
        fixedGroup.style.display = 'none';
        rangeGroup.style.display = 'block';
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercent').required = false;
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercentMin').required = true;
        document.getElementById(prefix + (isEdit ? 'I' : 'i') + 'nvestmentProfitPercentMax').required = true;
    }
}

function viewInvestmentClients(investmentId, investmentTitle) {
    // Set modal title
    document.getElementById('viewClientsModalLabel').textContent = 'Clients Invested in: ' + investmentTitle;
    
    // Show loading spinner
    document.getElementById('clientsTableContainer').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading clients...</div>';
    
    // Show the modal
    $('#viewClientsModal').modal('show');
    
    // Make AJAX request to get client investment details
    fetch('pages/get_investment_clients.php?investment_id=' + investmentId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('clientsTableContainer').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('clientsTableContainer').innerHTML = '<div class="alert alert-danger">Error loading client data: ' + error + '</div>';
        });
}

function editInvestment(investmentId, title, totalGoal, profitPercent, profitPercentMin, profitPercentMax, startDate, endDate) {
    // Populate the edit modal with current investment data
    document.getElementById('editInvestmentId').value = investmentId;
    document.getElementById('editInvestmentTitle').value = title;
    document.getElementById('editInvestmentTotalGoal').value = totalGoal;
    document.getElementById('editInvestmentStartDate').value = startDate;
    document.getElementById('editInvestmentEndDate').value = endDate;
    
    // Determine if it's a fixed or range profit
    if (profitPercentMin && profitPercentMax && profitPercentMin !== profitPercentMax) {
        // Range profit
        document.querySelector('input[name="editProfitType"][value="range"]').checked = true;
        document.getElementById('editInvestmentProfitPercentMin').value = profitPercentMin;
        document.getElementById('editInvestmentProfitPercentMax').value = profitPercentMax;
        toggleProfitFields(true);
    } else {
        // Fixed profit
        document.querySelector('input[name="editProfitType"][value="fixed"]').checked = true;
        document.getElementById('editInvestmentProfitPercent').value = profitPercent;
        toggleProfitFields(true);
    }
    
    // Show the edit modal
    $('#editInvestmentModal').modal('show');
}

function removeInvestment(investmentId, investmentTitle) {
    if (confirm('Are you sure you want to remove investment "' + investmentTitle + '"? This action cannot be undone.')) {
        // Create a form and submit it to delete the investment
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleteInvestment';
        input.value = investmentId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php // $conn is closed in config.php or globally ?>