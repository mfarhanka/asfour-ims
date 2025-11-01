<?php /* pages/client-list.php */
require_once __DIR__ . '/../config.php';

// Handle client deletion
if ($_POST && isset($_POST['deleteClient'])) {
    $clientId = intval($_POST['deleteClient']);
    $deleteSQL = "DELETE FROM clients WHERE id = ?";
    $stmt = $conn->prepare($deleteSQL);
    $stmt->bind_param("i", $clientId);
    
    if ($stmt->execute()) {
        echo "<script>alert('Client deleted successfully!'); window.location.href = 'index.php?p=client-list';</script>";
    } else {
        echo "<script>alert('Error deleting client!');</script>";
    }
    $stmt->close();
}

// Handle adding new client
if ($_POST && isset($_POST['clientName'])) {
    $username = trim($_POST['clientUsername']);
    $password = password_hash(trim($_POST['clientPassword']), PASSWORD_DEFAULT);
    $name = trim($_POST['clientName']);
    $email = trim($_POST['clientEmail']);
    $phone = trim($_POST['clientPhone']);
    
    $insertSQL = "INSERT INTO clients (username, password, name, email, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sssss", $username, $password, $name, $email, $phone);
    
    if ($stmt->execute()) {
        echo "<script>alert('Client added successfully!'); window.location.href = 'index.php?p=client-list';</script>";
    } else {
        echo "<script>alert('Error adding client!');</script>";
    }
    $stmt->close();
}

// Handle updating existing client
if ($_POST && isset($_POST['editClientId'])) {
    $clientId = intval($_POST['editClientId']);
    $username = trim($_POST['editClientUsername']);
    $name = trim($_POST['editClientName']);
    $email = trim($_POST['editClientEmail']);
    $phone = trim($_POST['editClientPhone']);
    $newPassword = trim($_POST['editClientPassword']);
    
    if (!empty($newPassword)) {
        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSQL = "UPDATE clients SET username = ?, password = ?, name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("sssssi", $username, $hashedPassword, $name, $email, $phone, $clientId);
    } else {
        // Update without changing password
        $updateSQL = "UPDATE clients SET username = ?, name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("ssssi", $username, $name, $email, $phone, $clientId);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Client updated successfully!'); window.location.href = 'index.php?p=client-list';</script>";
    } else {
        echo "<script>alert('Error updating client!');</script>";
    }
    $stmt->close();
}

$sql = "SELECT c.id, c.username, c.name, c.email, c.phone, c.created_at,
               COUNT(ci.id) as total_investments,
               COALESCE(SUM(ci.invested_amount), 0) as total_invested
        FROM clients c
        LEFT JOIN client_investments ci ON c.id = ci.client_id
        GROUP BY c.id, c.username, c.name, c.email, c.phone, c.created_at
        ORDER BY c.name ASC";
$result = $conn->query($sql);
?>
<div class="page-title">
  <div class="title_left">
    <h3>Client List</h3>
  </div>
</div>
<div class="clearfix"></div>
<div class="row">
  <div class="col-md-12 col-sm-12 ">
    <div class="x_panel">
      <div class="x_title">
        <h2>Clients</h2>
        <div class="clearfix"></div>
        <div style="float:right; margin-top:-30px;">
          <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addClientModal">Add Client</button>
        </div>
      </div>
      <!-- Add Client Modal -->
      <div class="modal fade" id="addClientModal" tabindex="-1" role="dialog" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="addClientModalLabel">Add Client</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="clientUsername">Username</label>
                  <input type="text" class="form-control" id="clientUsername" name="clientUsername" required>
                </div>
                <div class="form-group">
                  <label for="clientPassword">Password</label>
                  <input type="password" class="form-control" id="clientPassword" name="clientPassword" required>
                </div>
                <div class="form-group">
                  <label for="clientName">Name</label>
                  <input type="text" class="form-control" id="clientName" name="clientName" required>
                </div>
                <div class="form-group">
                  <label for="clientEmail">Email</label>
                  <input type="email" class="form-control" id="clientEmail" name="clientEmail" required>
                </div>
                <div class="form-group">
                  <label for="clientPhone">Phone</label>
                  <input type="text" class="form-control" id="clientPhone" name="clientPhone" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Client</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Edit Client Modal -->
      <div class="modal fade" id="editClientModal" tabindex="-1" role="dialog" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="editClientId" name="editClientId">
                <div class="form-group">
                  <label for="editClientUsername">Username</label>
                  <input type="text" class="form-control" id="editClientUsername" name="editClientUsername" required>
                </div>
                <div class="form-group">
                  <label for="editClientPassword">New Password (leave blank to keep current)</label>
                  <input type="password" class="form-control" id="editClientPassword" name="editClientPassword">
                </div>
                <div class="form-group">
                  <label for="editClientName">Name</label>
                  <input type="text" class="form-control" id="editClientName" name="editClientName" required>
                </div>
                <div class="form-group">
                  <label for="editClientEmail">Email</label>
                  <input type="email" class="form-control" id="editClientEmail" name="editClientEmail" required>
                </div>
                <div class="form-group">
                  <label for="editClientPhone">Phone</label>
                  <input type="text" class="form-control" id="editClientPhone" name="editClientPhone" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Update Client</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Client Investments Modal -->
      <div class="modal fade" id="clientInvestmentsModal" tabindex="-1" role="dialog" aria-labelledby="clientInvestmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="clientInvestmentsModalLabel">Client Investment Projects</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id="clientInvestmentsContent">
              <div class="text-center">
                <i class="fa fa-spinner fa-spin fa-3x"></i>
                <p class="mt-2">Loading investment projects...</p>
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
              <th>Username</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Total Investments</th>
              <th>Total Invested</th>
              <th>Created Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']) ?></td>
                  <td><?= htmlspecialchars($row['username']) ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= htmlspecialchars($row['phone']) ?></td>
                  <td><?= $row['total_investments'] ?></td>
                  <td>$<?= number_format($row['total_invested'], 2) ?></td>
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                  <td>
                    <button type="button" class="btn btn-info btn-sm" onclick="viewClientInvestments(<?= $row['id'] ?>)" title="View Invested Projects" style="margin-right: 5px;">
                      <i class="fa fa-list"></i>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="editClient(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>', '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['email']) ?>', '<?= htmlspecialchars($row['phone']) ?>')" title="Edit Client" style="margin-right: 5px;">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeClient(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')" title="Remove Client">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9">No clients found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function viewClientInvestments(clientId) {
    // Show the modal and reset content
    document.getElementById('clientInvestmentsContent').innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-2">Loading investment projects...</p></div>';
    $('#clientInvestmentsModal').modal('show');
    
    // Fetch client investments via AJAX
    fetch('pages/get_client_investments.php?client_id=' + clientId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('clientInvestmentsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('clientInvestmentsContent').innerHTML = '<div class="alert alert-danger">Error loading investment projects: ' + error.message + '</div>';
        });
}

function editClient(clientId, username, name, email, phone) {
    // Populate the edit modal with current client data
    document.getElementById('editClientId').value = clientId;
    document.getElementById('editClientUsername').value = username;
    document.getElementById('editClientName').value = name;
    document.getElementById('editClientEmail').value = email;
    document.getElementById('editClientPhone').value = phone;
    document.getElementById('editClientPassword').value = ''; // Clear password field
    
    // Show the edit modal
    $('#editClientModal').modal('show');
}

function removeClient(clientId, clientName) {
    if (confirm('Are you sure you want to remove client "' + clientName + '"? This action cannot be undone.')) {
        // Create a form and submit it to delete the client
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleteClient';
        input.value = clientId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php // $conn is closed in config.php or globally ?>
