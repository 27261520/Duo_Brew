<?php
ob_start();

$pageTitle = 'Products';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

if (!hasRole('Administrator')) {
    header('Location: ' . BASE_URL . 'pos/index.php');
    exit();
}

$db = Database::getInstance();

// Normalize upload directory path for Windows / XAMPP
$uploadDir = __DIR__ . '/../assets/images/products/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $status = sanitize($_POST['status'] ?? 'active');
        $isSeasonal = isset($_POST['is_seasonal']) ? 1 : 0;
        $isPromotional = isset($_POST['is_promotional']) ? 1 : 0;
        
        $image = $_FILES['image'] ?? null;
        $imageName = '';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if ($image && isset($image['error']) && $image['error'] === UPLOAD_ERR_OK && $image['size'] > 0) {
            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions)) {
                $imageName = uniqid('prod_') . '.' . $ext;
                $uploadPath = $uploadDir . $imageName;
                if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
                    $imageName = '';
                }
            }
        }
        
        if ($action === 'add') {
            $db->insert(
                "INSERT INTO products (category_id, name, description, price, image, is_seasonal, is_promotional, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$categoryId, $name, $description, $price, $imageName, $isSeasonal, $isPromotional, $status],
                'issdiiis'
            );
            logActivity('Add Product', "Added product: $name");
            redirectWith('products.php', 'Product added successfully!');
        } else {
            $id = intval($_POST['id']);
            
            if (!empty($imageName)) {
                $existingProduct = $db->getRow("SELECT image FROM products WHERE id = ?", [$id], 'i');
                if (!empty($existingProduct['image'])) {
                    $oldFilename = basename($existingProduct['image']);
                    $oldPath = $uploadDir . $oldFilename;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $db->execute(
                    "UPDATE products SET category_id=?, name=?, description=?, price=?, image=?, is_seasonal=?, is_promotional=?, status=? WHERE id=?",
                    [$categoryId, $name, $description, $price, $imageName, $isSeasonal, $isPromotional, $status, $id],
                    'issdiiisi'
                );
            } else {
                $db->execute(
                    "UPDATE products SET category_id=?, name=?, description=?, price=?, is_seasonal=?, is_promotional=?, status=? WHERE id=?",
                    [$categoryId, $name, $description, $price, $isSeasonal, $isPromotional, $status, $id],
                    'issdiiii'
                );
            }
            logActivity('Edit Product', "Edited product: $name");
            redirectWith('products.php', 'Product updated successfully!');
        }
    } elseif ($action === 'archive') {
        $id = intval($_POST['id']);
        $db->execute("UPDATE products SET status = 'archived' WHERE id = ?", [$id], 'i');
        logActivity('Archive Product', "Archived product ID: $id");
        redirectWith('products.php', 'Product archived successfully!');
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        
        try {
            $db->execute("DELETE FROM product_options WHERE product_id = ?", [$id], 'i');
            
            $existingProduct = $db->getRow("SELECT image FROM products WHERE id = ?", [$id], 'i');
            if (!empty($existingProduct['image'])) {
                $oldPath = $uploadDir . basename($existingProduct['image']);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $db->execute("DELETE FROM products WHERE id = ?", [$id], 'i');
            logActivity('Delete Product', "Permanently deleted product ID: $id");
            redirectWith('products.php', 'Product permanently deleted!');
        } catch (Throwable $e) {
            $db->execute("UPDATE products SET status = 'archived' WHERE id = ?", [$id], 'i');
            logActivity('Archive Product', "Archived product ID: $id due to existing order history");
            redirectWith('products.php', 'Product is linked to past sales records and has been safely archived instead!');
        }
    } elseif ($action === 'add_option') {
        $productId = intval($_POST['product_id']);
        $optionType = sanitize($_POST['option_type']);
        $optionName = sanitize($_POST['option_name']);
        $priceModifier = floatval($_POST['price_modifier']);
        
        $db->insert(
            "INSERT INTO product_options (product_id, option_type, option_name, price_modifier) VALUES (?, ?, ?, ?)",
            [$productId, $optionType, $optionName, $priceModifier],
            'issd'
        );
        redirectWith('products.php', 'Option added successfully!');
    } elseif ($action === 'delete_option') {
        $id = intval($_POST['id']);
        $db->execute("DELETE FROM product_options WHERE id = ?", [$id], 'i');
        redirectWith('products.php', 'Option deleted successfully!');
    }
}

$categories = $db->getRows("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC", [], '');
$products = $db->getRows(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     JOIN categories c ON p.category_id = c.id 
     ORDER BY p.status ASC, c.sort_order ASC, p.name ASC", [], ''
);
?>

<style>
.filter-cat-btn {
    background-color: #ffffff !important;
    color: #6B3A2A !important;
    border: 1px solid #6B3A2A !important;
    font-weight: 600;
    transition: all 0.2s ease-in-out;
}

.filter-cat-btn:hover {
    background-color: #f7ede8 !important;
}

.filter-cat-btn.active {
    background-color: #6B3A2A !important;
    color: #ffffff !important;
    border-color: #6B3A2A !important;
}

.product-img-box {
    height: 140px;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
}
</style>

<!-- Header Title and Add Action -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Products</h4>
    <button class="btn btn-brown px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetProductForm()">
        <i class="fas fa-plus me-1"></i> Add Product
    </button>
</div>

<!-- Category Filters -->
<div class="d-flex gap-2 mb-3 overflow-auto pb-2 flex-nowrap" id="categoryFilterBar">
    <button type="button" class="btn filter-cat-btn active rounded-pill px-3 py-1 flex-shrink-0" data-cat="all">
        <i class="fas fa-border-all me-1"></i> All
    </button>
    <?php foreach ($categories as $cat): ?>
        <button type="button" class="btn filter-cat-btn rounded-pill px-3 py-1 flex-shrink-0 text-uppercase" data-cat="<?= $cat['id'] ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </button>
    <?php endforeach; ?>
</div>

<!-- Search Input -->
<div class="mb-4">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
        <input type="text" id="productSearch" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search products...">
    </div>
</div>

<!-- Products Grid -->
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3" id="productsGrid">
    <?php if (empty($products)): ?>
        <div class="col-12 w-100 text-center py-5 text-muted">
            <i class="fas fa-box-open fa-3x mb-3 text-secondary"></i>
            <h5>No products found</h5>
        </div>
    <?php else: ?>
        <?php foreach ($products as $p): ?>
            <?php 
                $cleanImageName = !empty($p['image']) ? basename($p['image']) : '';
                $imageUrl = !empty($cleanImageName) ? BASE_URL . 'assets/images/products/' . $cleanImageName : '';
                $hasImage = !empty($cleanImageName) && file_exists($uploadDir . $cleanImageName);
                $jsonProduct = htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col product-card-item" data-category="<?= $p['category_id'] ?>" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
                <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden position-relative">
                    
                    <!-- Badges Overlay -->
                    <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-1 align-items-end" style="z-index: 2;">
                        <span class="badge <?= getStatusBadge($p['status']) ?> shadow-sm">
                            <?= getStatusLabel($p['status']) ?>
                        </span>
                        <?php if ($p['is_seasonal']): ?>
                            <span class="badge bg-warning text-dark shadow-sm">Seasonal</span>
                        <?php endif; ?>
                        <?php if ($p['is_promotional']): ?>
                            <span class="badge bg-info shadow-sm">Promo</span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Image Container -->
                    <div class="w-100 product-img-box d-flex align-items-center justify-content-center">
                        <?php if ($hasImage): ?>
                            <img src="<?= $imageUrl ?>?v=<?= time() ?>" 
                                 alt="<?= htmlspecialchars($p['name']) ?>" 
                                 class="w-100 h-100" 
                                 style="object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                        <?php endif; ?>
                        
                        <div class="text-center text-secondary <?= $hasImage ? 'd-none' : '' ?>">
                            <i class="fas fa-coffee fa-3x mb-1" style="color: #6B3A2A;"></i>
                            <div class="small fw-semibold text-muted" style="font-size: 0.65rem;">DUO BREW</div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.9rem; line-height: 1.25; min-height: 2.5em; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($p['name']) ?>
                            </h6>
                            <small class="text-muted text-uppercase d-block mb-1" style="font-size: 0.7rem; font-weight: 600;">
                                <?= htmlspecialchars($p['category_name']) ?>
                            </small>
                        </div>
                        <div class="fw-bold fs-6 mb-2" style="color: #6B3A2A;">
                            <?= formatCurrency($p['price']) ?>
                        </div>
                    </div>

                    <!-- Action Buttons Footer -->
                    <div class="card-footer bg-light border-top-0 p-1 d-flex justify-content-around">
                        <button class="btn btn-sm btn-outline-brown border-0 px-2 py-1 btn-edit-product" data-product='<?= $jsonProduct ?>' title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info border-0 px-2 py-1 btn-options-product" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Options">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning border-0 px-2 py-1 btn-archive-product" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger border-0 px-2 py-1 btn-delete-product" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="noProductsMsg" class="text-center py-5 text-muted" style="display: none;">
    <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
    <h5>No matching products found</h5>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="prodAction" value="add">
                    <input type="hidden" name="id" id="prodId" value="0">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" id="prodName" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="prodCategory" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="prodDescription" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" id="prodPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" id="prodImage" class="form-control" accept="image/*" onchange="previewProductImage(this)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="prodStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Image Preview Area -->
                    <div class="col-12 mb-3 text-center d-none" id="previewContainer">
                        <label class="form-label d-block fw-semibold text-start">Image Preview</label>
                        <div class="border rounded p-2 bg-light d-inline-block">
                            <img id="imgPreview" src="" class="img-thumbnail" style="max-height: 120px; object-fit: contain;">
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="is_seasonal" class="form-check-input" id="prodSeasonal" value="1">
                        <label class="form-check-label" for="prodSeasonal">Seasonal Item</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_promotional" class="form-check-input" id="prodPromo" value="1">
                        <label class="form-check-label" for="prodPromo">Promotional Item</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brown">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Options Modal -->
<div class="modal fade" id="optionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Options: <span id="optionsProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" class="mb-4">
                    <input type="hidden" name="action" value="add_option">
                    <input type="hidden" name="product_id" id="optionProductId">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="option_type" class="form-select" required>
                                <option value="size">Size</option>
                                <option value="flavor">Flavor</option>
                                <option value="addon">Add-on</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="option_name" class="form-control" placeholder="Option name" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="price_modifier" class="form-control" placeholder="Price" step="0.01" value="0">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-brown w-100">Add</button>
                        </div>
                    </div>
                </form>
                
                <div id="optionsList">
                    <div class="text-center text-muted py-3">Loading options...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">Archive Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="id" id="archiveId" value="0">
                    <p>Archive <strong id="archiveName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId" value="0">
                    <p>Permanently delete <strong id="deleteName"></strong>? If this item has sales history, it will be automatically archived instead.</p>
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
$baseUrlJs = BASE_URL;
$pageScripts = <<<'EOSCRIPT'
<script>
let activeCategory = 'all';
let searchQuery = '';

function filterGrid() {
    const items = document.querySelectorAll('.product-card-item');
    let visibleCount = 0;
    
    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        const itemName = item.getAttribute('data-name');
        
        const catMatch = activeCategory === 'all' || itemCat === activeCategory;
        const nameMatch = searchQuery === '' || itemName.includes(searchQuery);
        
        if (catMatch && nameMatch) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const noMatch = document.getElementById('noProductsMsg');
    if (noMatch) {
        noMatch.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // Category Filter Pills
    const categoryButtons = document.querySelectorAll('.filter-cat-btn');
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.getAttribute('data-cat');
            filterGrid();
        });
    });

    // Search Input
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchQuery = this.value.toLowerCase().trim();
            filterGrid();
        });
    }

    // Action Event Delegation (Prevents quote escaping issues in inline JS)
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.btn-edit-product');
        if (editBtn) {
            try {
                const product = JSON.parse(editBtn.getAttribute('data-product'));
                editProduct(product);
            } catch (err) {
                console.error("Failed to parse product data", err);
            }
            return;
        }

        const optionsBtn = e.target.closest('.btn-options-product');
        if (optionsBtn) {
            manageOptions(optionsBtn.getAttribute('data-id'), optionsBtn.getAttribute('data-name'));
            return;
        }

        const archiveBtn = e.target.closest('.btn-archive-product');
        if (archiveBtn) {
            confirmArchive(archiveBtn.getAttribute('data-id'), archiveBtn.getAttribute('data-name'));
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-product');
        if (deleteBtn) {
            confirmDelete(deleteBtn.getAttribute('data-id'), deleteBtn.getAttribute('data-name'));
            return;
        }
    });
});

function previewProductImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetProductForm() {
    document.getElementById("prodAction").value = "add";
    document.getElementById("productModalTitle").textContent = "Add Product";
    document.getElementById("prodId").value = "0";
    document.getElementById("prodName").value = "";
    document.getElementById("prodDescription").value = "";
    document.getElementById("prodPrice").value = "";
    document.getElementById("prodCategory").value = "";
    document.getElementById("prodStatus").value = "active";
    document.getElementById("prodSeasonal").checked = false;
    document.getElementById("prodPromo").checked = false;
    document.getElementById("prodImage").value = "";
    document.getElementById("previewContainer").classList.add("d-none");
}

function editProduct(p) {
    document.getElementById("prodAction").value = "edit";
    document.getElementById("productModalTitle").textContent = "Edit Product";
    document.getElementById("prodId").value = p.id;
    document.getElementById("prodName").value = p.name;
    document.getElementById("prodDescription").value = p.description || "";
    document.getElementById("prodPrice").value = p.price;
    document.getElementById("prodCategory").value = p.category_id;
    document.getElementById("prodStatus").value = p.status;
    document.getElementById("prodSeasonal").checked = p.is_seasonal == 1;
    document.getElementById("prodPromo").checked = p.is_promotional == 1;
    document.getElementById("prodImage").value = "";
    
    if (p.image) {
        let cleanName = p.image.split('/').pop().split('\\').pop();
        document.getElementById("imgPreview").src = "../assets/images/products/" + cleanName;
        document.getElementById("previewContainer").classList.remove("d-none");
    } else {
        document.getElementById("previewContainer").classList.add("d-none");
    }
    
    const modal = new bootstrap.Modal(document.getElementById("productModal"));
    modal.show();
}

function confirmArchive(id, name) {
    document.getElementById("archiveId").value = id;
    document.getElementById("archiveName").textContent = name;
    const modal = new bootstrap.Modal(document.getElementById("archiveModal"));
    modal.show();
}

function confirmDelete(id, name) {
    document.getElementById("deleteId").value = id;
    document.getElementById("deleteName").textContent = name;
    const modal = new bootstrap.Modal(document.getElementById("deleteModal"));
    modal.show();
}

function manageOptions(productId, productName) {
    document.getElementById("optionProductId").value = productId;
    document.getElementById("optionsProductName").textContent = productName;
    
    fetch("products_api.php?action=get_options&product_id=" + productId)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById("optionsList");
            if (!Array.isArray(data) || data.length === 0) {
                container.innerHTML = "<div class='text-center text-muted py-3'>No options configured yet</div>";
                return;
            }
            
            let html = "<div class='table-responsive'><table class='table table-sm'>";
            html += "<thead><tr><th>Type</th><th>Name</th><th>Price Modifier</th><th></th></tr></thead><tbody>";
            
            data.forEach(opt => {
                html += `<tr>
                    <td><span class="badge bg-cream text-brown">${opt.option_type}</span></td>
                    <td>${opt.option_name}</td>
                    <td>${parseFloat(opt.price_modifier) > 0 ? "+₱" + parseFloat(opt.price_modifier).toFixed(2) : "—"}</td>
                    <td>
                        <form method="POST" action="" style="display:inline">
                            <input type="hidden" name="action" value="delete_option">
                            <input type="hidden" name="id" value="${opt.id}">
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this option?')">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </td>
                </tr>`;
            });
            
            html += "</tbody></table></div>";
            container.innerHTML = html;
        })
        .catch(err => {
            document.getElementById("optionsList").innerHTML = "<div class='text-danger py-3'>Error loading options</div>";
        });
    
    const modal = new bootstrap.Modal(document.getElementById("optionsModal"));
    modal.show();
}
</script>
EOSCRIPT;

require_once __DIR__ . '/../includes/footer.php'; 
?>