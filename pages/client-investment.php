<?php /* pages/client-investment.php */
require_once __DIR__ . '/../config.php';

// Handle client investment deletion
if ($_POST && isset($_POST['deleteClientInvestment'])) {
    $clientInvestmentId = intval($_POST['deleteClientInvestment']);
    $deleteSQL = "DELETE FROM client_investments WHERE id = ?";
    $stmt = $conn->prepare($deleteSQL);
    $stmt->bind_param("i", $clientInvestmentId);
    
    if ($stmt->execute()) {
        echo "<script>alert('Client investment deleted successfully!'); window.location.href = 'index.php?p=client-investment';</script>";
    } else {
        echo "<script>alert('Error deleting client investment!');</script>";
    }
    $stmt->close();
}

// Handle adding new client investment
if ($_POST && isset($_POST['clientInvestmentClientId'])) {
    $clientId = intval($_POST['clientInvestmentClientId']);
    $investmentId = intval($_POST['clientInvestmentInvestmentId']);
    $investedAmount = floatval($_POST['clientInvestmentAmount']);
    $investmentDate = trim($_POST['clientInvestmentDate']);
    
    // Handle agreement document upload
    $agreementDocument = null;
    if (isset($_FILES['clientInvestmentAgreement']) && $_FILES['clientInvestmentAgreement']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/agreements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['clientInvestmentAgreement']['name'], PATHINFO_EXTENSION);
        $fileName = 'agreement_' . $clientId . '_' . $investmentId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['clientInvestmentAgreement']['tmp_name'], $uploadPath)) {
            $agreementDocument = $fileName;
        }
    }
    
    $status = isset($_POST['clientInvestmentStatus']) ? $_POST['clientInvestmentStatus'] : 'pending';
    
    $insertSQL = "INSERT INTO client_investments (client_id, investment_id, invested_amount, investment_date, agreement_document, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("iidsss", $clientId, $investmentId, $investedAmount, $investmentDate, $agreementDocument, $status);
    
    if ($stmt->execute()) {
        echo "<script>alert('Client investment added successfully!'); window.location.href = 'index.php?p=client-investment';</script>";
    } else {
        echo "<script>alert('Error adding client investment!');</script>";
    }
    $stmt->close();
}

// Handle updating existing client investment
if ($_POST && isset($_POST['editClientInvestmentId'])) {
    $clientInvestmentId = intval($_POST['editClientInvestmentId']);
    $clientId = intval($_POST['editClientInvestmentClientId']);
    $investmentId = intval($_POST['editClientInvestmentInvestmentId']);
    $investedAmount = floatval($_POST['editClientInvestmentAmount']);
    $investmentDate = trim($_POST['editClientInvestmentDate']);
    $status = isset($_POST['editClientInvestmentStatus']) ? $_POST['editClientInvestmentStatus'] : 'pending';
    
    // Handle agreement document upload
    $agreementDocument = null;
    $updateAgreement = false;
    
    if (isset($_FILES['editClientInvestmentAgreement']) && $_FILES['editClientInvestmentAgreement']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/agreements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['editClientInvestmentAgreement']['name'], PATHINFO_EXTENSION);
        $fileName = 'agreement_' . $clientId . '_' . $investmentId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['editClientInvestmentAgreement']['tmp_name'], $uploadPath)) {
            $agreementDocument = $fileName;
            $updateAgreement = true;
        }
    }
    
    if ($updateAgreement) {
        $updateSQL = "UPDATE client_investments SET client_id = ?, investment_id = ?, invested_amount = ?, investment_date = ?, agreement_document = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("iidsssi", $clientId, $investmentId, $investedAmount, $investmentDate, $agreementDocument, $status, $clientInvestmentId);
    } else {
        $updateSQL = "UPDATE client_investments SET client_id = ?, investment_id = ?, invested_amount = ?, investment_date = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("iidssi", $clientId, $investmentId, $investedAmount, $investmentDate, $status, $clientInvestmentId);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Client investment updated successfully!'); window.location.href = 'index.php?p=client-investment';</script>";
    } else {
        echo "<script>alert('Error updating client investment!');</script>";
    }
    $stmt->close();
}

// Get all clients for dropdown
$clientsSQL = "SELECT id, username, name FROM clients ORDER BY name ASC";
$clientsResult = $conn->query($clientsSQL);

// Get all investments for dropdown
$investmentsSQL = "SELECT id, title, total_goal FROM investments ORDER BY title ASC";
$investmentsResult = $conn->query($investmentsSQL);

// Get client investments with related data
$sql = "SELECT ci.id, ci.client_id, ci.investment_id, ci.invested_amount, ci.investment_date, ci.created_at, ci.agreement_document, ci.status,
               c.name as client_name, c.username as client_username,
               i.title as investment_title, i.total_goal, i.profit_percent
        FROM client_investments ci 
        LEFT JOIN clients c ON ci.client_id = c.id 
        LEFT JOIN investments i ON ci.investment_id = i.id 
        ORDER BY ci.investment_date DESC";
$result = $conn->query($sql);
?>
<div class="page-title">
  <div class="title_left">
    <h3>Client Investment Management</h3>
  </div>
</div>
<div class="clearfix"></div>
<div class="row">
  <div class="col-md-12 col-sm-12 ">
    <div class="x_panel">
      <div class="x_title">
        <h2>Client Investments</h2>
        <div class="clearfix"></div>
        <div style="float:right; margin-top:-30px;">
          <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addClientInvestmentModal">Add Client Investment</button>
        </div>
      </div>
      
      <!-- Add Client Investment Modal -->
      <div class="modal fade" id="addClientInvestmentModal" tabindex="-1" role="dialog" aria-labelledby="addClientInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="addClientInvestmentModalLabel">Add Client Investment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="clientInvestmentClientId">Client</label>
                  <select class="form-control" id="clientInvestmentClientId" name="clientInvestmentClientId" required>
                    <option value="">Select Client</option>
                    <?php 
                    $clientsResult->data_seek(0); // Reset result pointer
                    while($client = $clientsResult->fetch_assoc()): ?>
                      <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['username']) ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="clientInvestmentInvestmentId">Investment Project</label>
                  <select class="form-control" id="clientInvestmentInvestmentId" name="clientInvestmentInvestmentId" required>
                    <option value="">Select Investment</option>
                    <?php 
                    $investmentsResult->data_seek(0); // Reset result pointer
                    while($investment = $investmentsResult->fetch_assoc()): ?>
                      <option value="<?= $investment['id'] ?>"><?= htmlspecialchars($investment['title']) ?> (Goal: $<?= number_format($investment['total_goal'], 2) ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="clientInvestmentAmount">Invested Amount</label>
                  <input type="number" step="0.01" class="form-control" id="clientInvestmentAmount" name="clientInvestmentAmount" required>
                </div>
                <div class="form-group">
                  <label for="clientInvestmentDate">Investment Date</label>
                  <input type="date" class="form-control" id="clientInvestmentDate" name="clientInvestmentDate" required>
                </div>
                <div class="form-group">
                  <label for="clientInvestmentStatus">Status</label>
                  <select class="form-control" id="clientInvestmentStatus" name="clientInvestmentStatus" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="clientInvestmentAgreement">Agreement Document (Optional)</label>
                  <input type="file" class="form-control-file" id="clientInvestmentAgreement" name="clientInvestmentAgreement" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                  <small class="form-text text-muted">Upload investment agreement document. Accepted formats: PDF, DOC, DOCX, JPG, PNG</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Client Investment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Edit Client Investment Modal -->
      <div class="modal fade" id="editClientInvestmentModal" tabindex="-1" role="dialog" aria-labelledby="editClientInvestmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="editClientInvestmentModalLabel">Edit Client Investment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="editClientInvestmentId" name="editClientInvestmentId">
                <div class="form-group">
                  <label for="editClientInvestmentClientId">Client</label>
                  <select class="form-control" id="editClientInvestmentClientId" name="editClientInvestmentClientId" required>
                    <option value="">Select Client</option>
                    <?php 
                    $clientsResult->data_seek(0); // Reset result pointer
                    while($client = $clientsResult->fetch_assoc()): ?>
                      <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['username']) ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editClientInvestmentInvestmentId">Investment Project</label>
                  <select class="form-control" id="editClientInvestmentInvestmentId" name="editClientInvestmentInvestmentId" required>
                    <option value="">Select Investment</option>
                    <?php 
                    $investmentsResult->data_seek(0); // Reset result pointer
                    while($investment = $investmentsResult->fetch_assoc()): ?>
                      <option value="<?= $investment['id'] ?>"><?= htmlspecialchars($investment['title']) ?> (Goal: $<?= number_format($investment['total_goal'], 2) ?>)</option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editClientInvestmentAmount">Invested Amount</label>
                  <input type="number" step="0.01" class="form-control" id="editClientInvestmentAmount" name="editClientInvestmentAmount" required>
                </div>
                <div class="form-group">
                  <label for="editClientInvestmentDate">Investment Date</label>
                  <input type="date" class="form-control" id="editClientInvestmentDate" name="editClientInvestmentDate" required>
                </div>
                <div class="form-group">
                  <label for="editClientInvestmentStatus">Status</label>
                  <select class="form-control" id="editClientInvestmentStatus" name="editClientInvestmentStatus" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editClientInvestmentAgreement">Agreement Document (Optional)</label>
                  <input type="file" class="form-control-file" id="editClientInvestmentAgreement" name="editClientInvestmentAgreement" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                  <small class="form-text text-muted">Upload new agreement document to replace existing one. Accepted formats: PDF, DOC, DOCX, JPG, PNG</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Update Client Investment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Investment Info Modal -->
      <div class="modal fade" id="investmentInfoModal" tabindex="-1" role="dialog" aria-labelledby="investmentInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="investmentInfoModalLabel">Investment Information & Expected Profit</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id="investmentInfoContent">
              <div class="text-center">
                <i class="fa fa-spinner fa-spin fa-3x"></i>
                <p class="mt-2">Loading investment information...</p>
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
              <th>Client</th>
              <th>Username</th>
              <th>Investment Project</th>
              <th>Project Goal</th>
              <th>Profit %</th>
              <th>Invested Amount</th>
              <th>Investment Date</th>
              <th>Status</th>
              <th>Agreement</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['client_name'] ?: 'N/A') ?></td>
                  <td><?= htmlspecialchars($row['client_username'] ?: 'N/A') ?></td>
                  <td><?= htmlspecialchars($row['investment_title'] ?: 'N/A') ?></td>
                  <td>$<?= number_format($row['total_goal'] ?: 0, 2) ?></td>
                  <td><?= number_format($row['profit_percent'] ?: 0, 2) ?>%</td>
                  <td>$<?= number_format($row['invested_amount'], 2) ?></td>
                  <td><?= htmlspecialchars($row['investment_date']) ?></td>
                  <td>
                    <?php
                    $statusClass = '';
                    $statusText = ucfirst($row['status'] ?? 'pending');
                    switch($row['status']) {
                      case 'pending':
                        $statusClass = 'label-warning';
                        break;
                      case 'approved':
                        $statusClass = 'label-primary';
                        break;
                      case 'rejected':
                        $statusClass = 'label-danger';
                        break;
                      case 'active':
                        $statusClass = 'label-info';
                        break;
                      case 'completed':
                        $statusClass = 'label-success';
                        break;
                      default:
                        $statusClass = 'label-default';
                    }
                    ?>
                    <span class="label <?= $statusClass ?>"><?= $statusText ?></span>
                  </td>
                  <td>
                    <?php if (!empty($row['agreement_document'])): ?>
                      <button type="button" class="btn btn-success btn-sm" onclick="viewAgreement('<?= htmlspecialchars($row['agreement_document']) ?>')" title="View Agreement">
                        <i class="fa fa-file-text"></i> View
                      </button>
                    <?php else: ?>
                      <span class="text-muted">No agreement</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                  <td>
                    <button type="button" class="btn btn-info btn-sm" onclick="viewInvestmentInfo(<?= $row['id'] ?>)" title="View Investment Info" style="margin-right: 5px;">
                      <i class="fa fa-info-circle"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="editClientInvestment(<?= $row['id'] ?>, <?= $row['client_id'] ?>, <?= $row['investment_id'] ?>, <?= $row['invested_amount'] ?>, '<?= htmlspecialchars($row['investment_date']) ?>', '<?= htmlspecialchars($row['status']) ?>')" title="Edit Client Investment" style="margin-right: 5px;">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeClientInvestment(<?= $row['id'] ?>, '<?= htmlspecialchars($row['client_name']) ?>', '<?= htmlspecialchars($row['investment_title']) ?>')" title="Remove Client Investment">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="12">No client investments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function viewInvestmentInfo(clientInvestmentId) {
    // Show the modal and reset content
    document.getElementById('investmentInfoContent').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-2">Loading investment information...</p></div>';
    $('#investmentInfoModal').modal('show');
    
    // Fetch investment information via AJAX
    fetch('pages/get_investment_info.php?client_investment_id=' + clientInvestmentId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('investmentInfoContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('investmentInfoContent').innerHTML = '<div class="alert alert-danger">Error loading investment information: ' + error.message + '</div>';
        });
}

function editClientInvestment(clientInvestmentId, clientId, investmentId, investedAmount, investmentDate, status) {
    // Populate the edit modal with current client investment data
    document.getElementById('editClientInvestmentId').value = clientInvestmentId;
    document.getElementById('editClientInvestmentClientId').value = clientId;
    document.getElementById('editClientInvestmentInvestmentId').value = investmentId;
    document.getElementById('editClientInvestmentAmount').value = investedAmount;
    document.getElementById('editClientInvestmentDate').value = investmentDate;
    document.getElementById('editClientInvestmentStatus').value = status;
    
    // Show the edit modal
    $('#editClientInvestmentModal').modal('show');
}

function removeClientInvestment(clientInvestmentId, clientName, investmentTitle) {
    if (confirm('Are you sure you want to remove investment by "' + clientName + '" in "' + investmentTitle + '"? This action cannot be undone.')) {
        // Create a form and submit it to delete the client investment
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleteClientInvestment';
        input.value = clientInvestmentId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function viewAgreement(filename) {
    if (filename) {
        // Open the agreement document in a new window
        var url = 'uploads/agreements/' + filename;
        window.open(url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    } else {
        alert('No agreement document available.');
    }
}
</script>

<?php // $conn is closed in config.php or globally ?>