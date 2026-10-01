<?php
$pageTitle = 'Users';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $username = sanitize($_POST['username']);
        $fullName = sanitize($_POST['full_name']);
        $email    = sanitize($_POST['email']);
        $phone    = sanitize($_POST['phone']);
        $roleId   = intval($_POST['role_id']);
        $status   = sanitize($_POST['status'] ?? 'active');
        
        if ($action === 'add') {
            $rawPassword = $_POST['password'] ?? '';
            
            if (empty($rawPassword)) {
                redirectWith('users.php', 'Error: Password is required for new users!');
            }

            $existing = $db->getRows("SELECT id FROM users WHERE username = ?", [$username], 's');
            if (!empty($existing)) {
                redirectWith('users.php', 'Error: Username already exists!');
            }

            $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
            
            $db->insert(
                "INSERT INTO users (username, password, full_name, email, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$username, $hashedPassword, $fullName, $email, $phone, $roleId, $status],
                'sssssis'
            );
            logActivity('Add User', "Added user: $username");
            redirectWith('users.php', 'User account created! They can now log in.');

        } else {
            $id = intval($_POST['id']);
            
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $db->execute(
                    "UPDATE users SET username=?, password=?, full_name=?, email=?, phone=?, role_id=?, status=? WHERE id=?",
                    [$username, $hashedPassword, $fullName, $email, $phone, $roleId, $status, $id],
                    'sssssisi'
                );
            } else {
                $db->execute(
                    "UPDATE users SET username=?, full_name=?, email=?, phone=?, role_id=?, status=? WHERE id=?",
                    [$username, $fullName, $email, $phone, $roleId, $status, $id],
                    'sssssii'
                );
            }
            logActivity('Edit User', "Edited user: $username");
            redirectWith('users.php', 'User updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $db->execute("UPDATE users SET status = 'inactive' WHERE id = ?", [$id], 'i');
        logActivity('Deactivate User', "Deactivated user ID: $id");
        redirectWith('users.php', 'User deactivated successfully!');
    }
}

$roles = $db->getRows("SELECT * FROM roles ORDER BY id", [], '');
$users = $db->getRows(
    "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC", [], ''
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Users</h4>
    <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()">
        <i class="fas fa-plus me-1"></i> Add User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                            <td><span class="badge bg-cream text-brown"><?= htmlspecialchars($u['role_name']) ?></span></td>
                            <td><?= $u['last_login'] ? formatDateTime($u['last_login']) : 'Never' ?></td>
                            <td>
                                <span class="badge <?= getStatusBadge($u['status']) ?>"><?= getStatusLabel($u['status']) ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-brown" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="userAction" value="add">
                    <input type="hidden" name="id" id="userId" value="0">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="userUsername" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="userFullName" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="userEmail" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="userPhone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="userPassword" class="form-control" placeholder="Required for new users">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_id" id="userRole" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brown">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Deactivate User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId" value="0">
                    <p>Deactivate <strong id="deleteName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script>
function resetUserForm() {
    document.getElementById("userAction").value = "add";
    document.getElementById("userModalTitle").textContent = "Add User";
    document.getElementById("userId").value = "0";
    document.getElementById("userUsername").value = "";
    document.getElementById("userFullName").value = "";
    document.getElementById("userEmail").value = "";
    document.getElementById("userPhone").value = "";
    document.getElementById("userPassword").value = "";
    document.getElementById("userPassword").required = true;
    document.getElementById("userPassword").placeholder = "Enter password";
    document.getElementById("userStatus").value = "active";
}

function editUser(u) {
    document.getElementById("userAction").value = "edit";
    document.getElementById("userModalTitle").textContent = "Edit User";
    document.getElementById("userId").value = u.id;
    document.getElementById("userUsername").value = u.username;
    document.getElementById("userFullName").value = u.full_name;
    document.getElementById("userEmail").value = u.email || "";
    document.getElementById("userPhone").value = u.phone || "";
    document.getElementById("userPassword").required = false;
    document.getElementById("userPassword").value = "";
    document.getElementById("userPassword").placeholder = "Leave blank to keep current";
    document.getElementById("userRole").value = u.role_id;
    document.getElementById("userStatus").value = u.status;
    
    const modal = new bootstrap.Modal(document.getElementById("userModal"));
    modal.show();
}

function confirmDelete(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").textContent = name;
    const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
    modal.show();
}
</script>
';
require_once __DIR__ . '/../includes/footer.php'; 
?>