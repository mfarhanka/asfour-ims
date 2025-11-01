<?php /* pages/admin-list.php */
require_once __DIR__ . '/../config.php';

// Handle admin deletion
if ($_POST && isset($_POST['deleteAdmin'])) {
    $adminId = intval($_POST['deleteAdmin']);
    $deleteSQL = "DELETE FROM admins WHERE id = ?";
    $stmt = $conn->prepare($deleteSQL);
    $stmt->bind_param("i", $adminId);
    
    if ($stmt->execute()) {
        echo "<script>alert('Admin deleted successfully!'); window.location.href = 'index.php?p=admin-list';</script>";
    } else {
        echo "<script>alert('Error deleting admin!');</script>";
    }
    $stmt->close();
}

// Handle adding new admin
if ($_POST && isset($_POST['adminUsername'])) {
    $username = trim($_POST['adminUsername']);
    $password = password_hash(trim($_POST['adminPassword']), PASSWORD_DEFAULT);
    $name = trim($_POST['adminName']);
    
    $insertSQL = "INSERT INTO admins (username, password, name) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    $stmt->bind_param("sss", $username, $password, $name);
    
    if ($stmt->execute()) {
        echo "<script>alert('Admin added successfully!'); window.location.href = 'index.php?p=admin-list';</script>";
    } else {
        echo "<script>alert('Error adding admin!');</script>";
    }
    $stmt->close();
}

// Handle updating existing admin
if ($_POST && isset($_POST['editAdminId'])) {
    $adminId = intval($_POST['editAdminId']);
    $username = trim($_POST['editAdminUsername']);
    $name = trim($_POST['editAdminName']);
    $newPassword = trim($_POST['editAdminPassword']);
    
    if (!empty($newPassword)) {
        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSQL = "UPDATE admins SET username = ?, password = ?, name = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("sssi", $username, $hashedPassword, $name, $adminId);
    } else {
        // Update without changing password
        $updateSQL = "UPDATE admins SET username = ?, name = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("ssi", $username, $name, $adminId);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Admin updated successfully!'); window.location.href = 'index.php?p=admin-list';</script>";
    } else {
        echo "<script>alert('Error updating admin!');</script>";
    }
    $stmt->close();
}

$sql = "SELECT id, username, name, created_at FROM admins ORDER BY username ASC";
$result = $conn->query($sql);
?>
<div class="page-title">
  <div class="title_left">
    <h3>Admin List</h3>
  </div>
</div>
<div class="clearfix"></div>
<div class="row">
  <div class="col-md-12 col-sm-12 ">
    <div class="x_panel">
      <div class="x_title">
        <h2>Administrators</h2>
        <div class="clearfix"></div>
        <div style="float:right; margin-top:-30px;">
          <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addAdminModal">Add Admin</button>
        </div>
      </div>
      <!-- Add Admin Modal -->
      <div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <label for="adminUsername">Username</label>
                  <input type="text" class="form-control" id="adminUsername" name="adminUsername" required>
                </div>
                <div class="form-group">
                  <label for="adminPassword">Password</label>
                  <input type="password" class="form-control" id="adminPassword" name="adminPassword" required>
                </div>
                <div class="form-group">
                  <label for="adminName">Name</label>
                  <input type="text" class="form-control" id="adminName" name="adminName" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Admin</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Edit Admin Modal -->
      <div class="modal fade" id="editAdminModal" tabindex="-1" role="dialog" aria-labelledby="editAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <form method="post" action="">
              <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <input type="hidden" id="editAdminId" name="editAdminId">
                <div class="form-group">
                  <label for="editAdminUsername">Username</label>
                  <input type="text" class="form-control" id="editAdminUsername" name="editAdminUsername" required>
                </div>
                <div class="form-group">
                  <label for="editAdminPassword">New Password (leave blank to keep current)</label>
                  <input type="password" class="form-control" id="editAdminPassword" name="editAdminPassword">
                </div>
                <div class="form-group">
                  <label for="editAdminName">Name</label>
                  <input type="text" class="form-control" id="editAdminName" name="editAdminName" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Update Admin</button>
              </div>
            </form>
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
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                  <td>
                    <button type="button" class="btn btn-primary btn-sm" onclick="editAdmin(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>', '<?= htmlspecialchars($row['name']) ?>')" title="Edit Admin" style="margin-right: 5px;">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAdmin(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')" title="Remove Admin">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5">No administrators found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function editAdmin(adminId, username, name) {
    // Populate the edit modal with current admin data
    document.getElementById('editAdminId').value = adminId;
    document.getElementById('editAdminUsername').value = username;
    document.getElementById('editAdminName').value = name;
    document.getElementById('editAdminPassword').value = ''; // Clear password field
    
    // Show the edit modal
    $('#editAdminModal').modal('show');
}

function removeAdmin(adminId, adminUsername) {
    if (confirm('Are you sure you want to remove administrator "' + adminUsername + '"? This action cannot be undone.')) {
        // Create a form and submit it to delete the admin
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleteAdmin';
        input.value = adminId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php // $conn is closed in config.php or globally ?>