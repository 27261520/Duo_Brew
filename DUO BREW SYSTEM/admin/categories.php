<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Categories Management
 */

$pageTitle = 'Categories';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        
        if ($action === 'add') {
            $db->insert(
                "INSERT INTO categories (name, description, sort_order, status) VALUES (?, ?, ?, ?)",
                [$name, $description, $sortOrder, $status], 'ssis'
            );
            logActivity('Add Category', "Added category: $name");
            redirectWith('categories.php', 'Category added successfully!');
        } else {
            $id = intval($_POST['id']);
            $db->execute(
                "UPDATE categories SET name = ?, description = ?, sort_order = ?, status = ? WHERE id = ?",
                [$name, $description, $sortOrder, $status, $id], 'ssisi'
            );
            logActivity('Edit Category', "Edited category: $name");
            redirectWith('categories.php', 'Category updated successfully!');
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $cat = $db->getRow("SELECT name FROM categories WHERE id = ?", [$id], 'i');
        if ($cat) {
            $db->execute("UPDATE products SET status = 'archived' WHERE category_id = ?", [$id], 'i');
            $db->execute("DELETE FROM categories WHERE id = ?", [$id], 'i');
            logActivity('Delete Category', "Deleted category: {$cat['name']}");
            redirectWith('categories.php', 'Category deleted successfully!');
        }
    }
}

$categories = $db->getRows("SELECT * FROM categories ORDER BY sort_order ASC", [], '');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Categories</h4>
    <button class="btn btn-brown" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()">
        <i class="fas fa-plus me-1"></i> Add Category
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No categories found</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $cat['id'] ?></td>
                                <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                <td><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                                <td><?= $cat['sort_order'] ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadge($cat['status']) ?>">
                                        <?= getStatusLabel($cat['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-brown" onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="catId" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="catName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="catDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="catSort" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="catStatus" class="form-select">
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId" value="0">
                    <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                    <p class="text-muted small">Products in this category will be archived.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script>
function resetForm() {
    document.getElementById("formAction").value = "add";
    document.getElementById("modalTitle").textContent = "Add Category";
    document.getElementById("catId").value = "0";
    document.getElementById("catName").value = "";
    document.getElementById("catDescription").value = "";
    document.getElementById("catSort").value = "0";
    document.getElementById("catStatus").value = "active";
}

function editCategory(cat) {
    document.getElementById("formAction").value = "edit";
    document.getElementById("modalTitle").textContent = "Edit Category";
    document.getElementById("catId").value = cat.id;
    document.getElementById("catName").value = cat.name;
    document.getElementById("catDescription").value = cat.description || "";
    document.getElementById("catSort").value = cat.sort_order;
    document.getElementById("catStatus").value = cat.status;
    
    const modal = new bootstrap.Modal(document.getElementById("categoryModal"));
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

