<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Point of Sale Interface
 */

$pageTitle = 'POS';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

requireStaff();

$db = Database::getInstance();

$categories = $db->getRows(
    "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC", [], ''
);

$products = $db->getRows(
    "SELECT p.*, c.name as category_name, c.id as category_id
     FROM products p 
     JOIN categories c ON p.category_id = c.id 
     WHERE p.status = 'active' 
     ORDER BY c.sort_order ASC, p.name ASC", [], ''
);
?>

<div class="pos-container">
    <!-- Products Area -->
    <div class="pos-products">
        <!-- Category Pills -->
        <div class="category-pills mb-3">
            <button type="button" class="category-pill active" data-category-id="0" onclick="filterByCategory(0)">
                <i class="fas fa-th-large me-1"></i> All
            </button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="category-pill" data-category-id="<?= $cat['id'] ?>" onclick="filterByCategory(<?= $cat['id'] ?>)">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <div class="mb-3">
            <input type="text" class="form-control" placeholder="Search products..." 
                   onkeyup="searchProducts(this.value)">
        </div>

        <!-- Products Grid -->
        <div class="row g-2" id="productsGrid">
            <?php foreach ($products as $p): ?>
                <?php 
                    $cleanImageName = !empty($p['image']) ? basename($p['image']) : '';
                    $imageUrl = !empty($cleanImageName) ? BASE_URL . 'assets/images/products/' . $cleanImageName : '';
                ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2 product-card-wrapper">
                    <div class="product-card" 
                         data-product-id="<?= $p['id'] ?>"
                         data-product-name="<?= htmlspecialchars($p['name']) ?>"
                         data-product-price="<?= $p['price'] ?>"
                         data-category-id="<?= $p['category_id'] ?>"
                         data-category-name="<?= htmlspecialchars($p['category_name']) ?>"
                         onclick="addToCart(this)">
                        <div class="product-image <?= empty($cleanImageName) ? 'no-image' : '' ?>">
                            <?php if (!empty($cleanImageName)): ?>
                                <img src="<?= $imageUrl ?>" 
                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                     onerror="this.style.display='none';this.parentNode.classList.add('no-image')">
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                            <div class="product-price"><?= formatCurrency($p['price']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Area -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <span><i class="fas fa-shopping-cart me-2"></i>Current Order</span>
            <div>
                <span class="badge bg-brown rounded-pill" id="cartCountBadge" style="display:none;">0</span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="px-3 pt-3">
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" id="customerName" placeholder="Customer name" value="Walk-in Customer">
            </div>
            <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="text" class="form-control" id="customerPhone" placeholder="Contact number">
            </div>
        </div>

        <!-- Cart Items -->
        <div class="pos-cart-items" id="cartItems">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <p>Click products to add to cart</p>
            </div>
        </div>

        <!-- Cart Footer -->
        <div class="pos-cart-footer">
            <div class="d-flex justify-content-between mb-2">
                <span>Items:</span>
                <span id="cartItemCount">0</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="fs-5 fw-bold">Total:</span>
                <span class="fs-4 fw-bold text-brown" id="cartTotal">₱0.00</span>
            </div>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-brown btn-lg" id="checkoutBtn" onclick="checkout()" disabled>
                    <i class="fas fa-cash-register me-2"></i>Checkout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Product Options Modal -->
<div class="modal fade" id="optionsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="optionsModalTitle">Customize Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="optionsModalBody">
                <!-- Options loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-brown" onclick="confirmAddToCart()">
                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h2 class="fw-bold text-brown" id="paymentTotal">₱0.00</h2>
                    <p class="text-muted">Total Amount</p>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-outline-brown btn-lg" onclick="selectPayment('cash')">
                        <i class="fas fa-money-bill-wave me-2"></i>Cash
                    </button>
                    <button type="button" class="btn btn-outline-brown btn-lg" onclick="selectPayment('qr')">
                        <i class="fas fa-qrcode me-2"></i>QR Code
                    </button>
                </div>

                <!-- Cash Payment -->
                <div id="cashPayment" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label">Amount Received</label>
                        <input type="number" class="form-control form-control-lg" id="cashReceived" step="0.01" min="0" 
                               onkeyup="calculateChange()" onchange="calculateChange()">
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Change:</span>
                        <span class="fs-4 fw-bold text-success" id="changeAmount">₱0.00</span>
                    </div>
                </div>

                <!-- QR Payment -->
                <div id="qrPayment" style="display:none;" class="text-center">
                    <div class="bg-light p-4 rounded-3 mb-3" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-qrcode fa-6x text-brown"></i>
                    </div>
                    <p class="text-muted">Scan QR code to pay</p>
                    <p class="small text-muted">Or pay cash at the counter</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg" id="confirmPaymentBtn" onclick="processPayment()" disabled>
                    <i class="fas fa-check me-2"></i>Confirm Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptContent">
                <!-- Receipt loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brown" onclick="printReceipt(document.getElementById('receiptContent').innerHTML)">
                    <i class="fas fa-print me-1"></i>Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Cart state
let posCart = [];
let selectedProduct = null;
let selectedOptions = {};
let activePaymentMethod = null;

// Helper function to safely manage Bootstrap modal instances
function getModal(id) {
    const el = document.getElementById(id);
    if (!el || typeof bootstrap === 'undefined') return null;
    return bootstrap.Modal.getOrCreateInstance(el);
}

// Helper to escape HTML characters in strings
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Consolidates identical items in cart state
function addItemToCart(productId, name, price, quantity, options, specialRequests) {
    if (!options) options = '';
    if (!specialRequests) specialRequests = '';
    const existingIndex = posCart.findIndex(item => item.product_id === productId && item.options === options);
    
    if (existingIndex > -1) {
        posCart[existingIndex].quantity += quantity;
    } else {
        posCart.push({
            product_id: productId,
            name: name,
            price: price,
            quantity: quantity,
            options: options,
            special_requests: specialRequests
        });
    }
    updateCartUI(posCart);
}

// Add to cart - show options modal
function addToCart(btn) {
    const card = btn.dataset.productId ? btn : (btn.querySelector(".product-card") || btn.closest(".product-card"));
    if (!card) return;
    
    selectedProduct = {
        id: parseInt(card.dataset.productId),
        name: card.dataset.productName,
        price: parseFloat(card.dataset.productPrice)
    };
    
    // Fetch options
    fetch("<?= BASE_URL ?>admin/products_api.php?action=get_options&product_id=" + selectedProduct.id)
        .then(res => res.json())
        .then(options => {
            if (!Array.isArray(options) || options.length === 0) {
                // No options, add directly
                addItemToCart(selectedProduct.id, selectedProduct.name, selectedProduct.price, 1, "", "");
                showToast(selectedProduct.name + " added to cart!", "success");
                return;
            }
            
            // Build options UI
            let html = "";
            let sizes = options.filter(o => o.option_type === "size");
            let flavors = options.filter(o => o.option_type === "flavor");
            let addons = options.filter(o => o.option_type === "addon");
            
            if (sizes.length > 0) {
                html += `<label class="form-label fw-bold">Size</label>
                         <div class="mb-3">`;
                sizes.forEach(s => {
                    html += `<div class="form-check">
                        <input type="radio" name="opt_size" class="form-check-input option-radio" 
                               value='${JSON.stringify(s).replace(/'/g, "&#39;")}' 
                               ${s.is_default ? "checked" : ""}
                               onchange="updateOptionPrice()">
                        <label class="form-check-label">${escapeHtml(s.option_name)} ${parseFloat(s.price_modifier) > 0 ? "+₱" + parseFloat(s.price_modifier).toFixed(2) : ""}</label>
                    </div>`;
                });
                html += `</div>`;
            }
            
            if (flavors.length > 0) {
                html += `<label class="form-label fw-bold">Flavor</label>
                         <div class="mb-3">`;
                flavors.forEach(f => {
                    html += `<div class="form-check">
                        <input type="radio" name="opt_flavor" class="form-check-input option-radio" 
                               value='${JSON.stringify(f).replace(/'/g, "&#39;")}'>
                        <label class="form-check-label">${escapeHtml(f.option_name)}</label>
                    </div>`;
                });
                html += `</div>`;
            }
            
            if (addons.length > 0) {
                html += `<label class="form-label fw-bold">Add-ons</label>
                         <div class="mb-3">`;
                addons.forEach(a => {
                    html += `<div class="form-check">
                        <input type="checkbox" class="form-check-input option-checkbox" 
                               value='${JSON.stringify(a).replace(/'/g, "&#39;")}'
                               onchange="updateOptionPrice()">
                        <label class="form-check-label">${escapeHtml(a.option_name)} ${parseFloat(a.price_modifier) > 0 ? "+₱" + parseFloat(a.price_modifier).toFixed(2) : ""}</label>
                    </div>`;
                });
                html += `</div>`;
            }
            
            html += `<div class="mb-3">
                <label class="form-label">Quantity</label>
                <div class="input-group">
                    <button type="button" class="btn btn-outline-brown" onclick="changeQty(-1)">−</button>
                    <input type="number" class="form-control text-center" id="optQty" value="1" min="1" readonly>
                    <button type="button" class="btn btn-outline-brown" onclick="changeQty(1)">+</button>
                </div>
            </div>`;
            
            html += `<div class="d-flex justify-content-between">
                <span class="fw-bold">Item Total:</span>
                <span class="fw-bold fs-5 text-brown" id="optionTotal"><?= CURRENCY_SYMBOL ?>${selectedProduct.price.toFixed(2)}</span>
            </div>`;
            
            document.getElementById("optionsModalBody").innerHTML = html;
            document.getElementById("optionsModalTitle").textContent = selectedProduct.name;
            selectedOptions = {};
            
            const modal = getModal("optionsModal");
            if (modal) modal.show();
        })
        .catch(err => {
            // Fallback: add directly
            addItemToCart(selectedProduct.id, selectedProduct.name, selectedProduct.price, 1, "", "");
            showToast(selectedProduct.name + " added!", "success");
        });
}

function changeQty(delta) {
    const input = document.getElementById("optQty");
    if (!input) return;
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
    updateOptionPrice();
}

function updateOptionPrice() {
    if (!selectedProduct) return;
    let basePrice = selectedProduct.price;
    let qty = parseInt(document.getElementById("optQty")?.value || 1);
    
    document.querySelectorAll("input[name=opt_size]:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
        } catch(e) {}
    });
    
    document.querySelectorAll("input[name=opt_flavor]:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
        } catch(e) {}
    });
    
    document.querySelectorAll(".option-checkbox:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
        } catch(e) {}
    });
    
    const totalEl = document.getElementById("optionTotal");
    if (totalEl) {
        totalEl.textContent = "<?= CURRENCY_SYMBOL ?>" + (basePrice * qty).toFixed(2);
    }
}

function confirmAddToCart() {
    if (!selectedProduct) return;
    let basePrice = selectedProduct.price;
    let optionsText = [];
    let qty = parseInt(document.getElementById("optQty")?.value || 1);
    
    document.querySelectorAll("input[name=opt_size]:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
            optionsText.push(opt.option_name);
        } catch(e) {}
    });
    
    document.querySelectorAll("input[name=opt_flavor]:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
            optionsText.push(opt.option_name);
        } catch(e) {}
    });
    
    document.querySelectorAll(".option-checkbox:checked").forEach(el => {
        try {
            const opt = JSON.parse(el.value.replace(/&#39;/g, "'"));
            basePrice += parseFloat(opt.price_modifier || 0);
            optionsText.push(opt.option_name);
        } catch(e) {}
    });
    
    const optString = optionsText.length > 0 ? optionsText.join(", ") : "";
    addItemToCart(selectedProduct.id, selectedProduct.name, basePrice, qty, optString, "");
    
    const modal = getModal("optionsModal");
    if (modal) modal.hide();
    showToast(selectedProduct.name + " added to cart!", "success");
}

function updateCartUI(cart) {
    const container = document.getElementById("cartItems");
    const totalEl = document.getElementById("cartTotal");
    const countEl = document.getElementById("cartItemCount");
    const checkoutBtn = document.getElementById("checkoutBtn");
    const badge = document.getElementById("cartCountBadge");
    
    if (!container) return;
    
    if (!cart || cart.length === 0) {
        container.innerHTML = `<div class="text-center py-5 text-muted">
            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
            <p>Cart is empty</p>
        </div>`;
        if (totalEl) totalEl.textContent = "₱0.00";
        if (countEl) countEl.textContent = "0";
        if (checkoutBtn) checkoutBtn.disabled = true;
        if (badge) badge.style.display = "none";
        return;
    }
    
    let total = 0;
    let itemCount = 0;
    let html = "";
    
    cart.forEach((item, index) => {
        const itemSubtotal = item.price * item.quantity;
        total += itemSubtotal;
        itemCount += item.quantity;
        
        html += `<div class="cart-item fade-in" style="animation-delay: ${index * 0.03}s">
            <div class="item-details">
                <div class="item-name">${escapeHtml(item.name)}</div>
                ${item.options ? `<small class="text-muted">${escapeHtml(item.options)}</small>` : ""}
                <div class="item-price"><?= CURRENCY_SYMBOL ?>${itemSubtotal.toFixed(2)}</div>
            </div>
            <div class="item-qty">
                <button type="button" class="qty-btn" onclick="editItemQty(${index}, -1)">−</button>
                <span class="fw-bold px-2">${item.quantity}</span>
                <button type="button" class="qty-btn" onclick="editItemQty(${index}, 1)">+</button>
                <button type="button" class="qty-btn text-danger ms-1" onclick="removeItem(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
    if (totalEl) totalEl.textContent = "<?= CURRENCY_SYMBOL ?>" + total.toFixed(2);
    if (countEl) countEl.textContent = itemCount;
    if (checkoutBtn) checkoutBtn.disabled = false;
    if (badge) {
        badge.textContent = itemCount;
        badge.style.display = "inline";
    }
}

function editItemQty(index, delta) {
    if (posCart[index]) {
        posCart[index].quantity += delta;
        if (posCart[index].quantity < 1) {
            posCart.splice(index, 1);
        }
        updateCartUI(posCart);
    }
}

function removeItem(index) {
    if (posCart[index]) {
        posCart.splice(index, 1);
        updateCartUI(posCart);
    }
}

function clearCart() {
    if (posCart.length === 0) return;
    if (confirm("Clear entire cart?")) {
        posCart = [];
        updateCartUI(posCart);
    }
}

// Checkout
function checkout() {
    if (posCart.length === 0) return;
    
    const total = posCart.reduce((s, item) => s + (item.price * item.quantity), 0);
    document.getElementById("paymentTotal").textContent = "<?= CURRENCY_SYMBOL ?>" + total.toFixed(2);
    document.getElementById("cashPayment").style.display = "none";
    document.getElementById("qrPayment").style.display = "none";
    document.getElementById("cashReceived").value = "";
    document.getElementById("changeAmount").textContent = "₱0.00";
    document.getElementById("confirmPaymentBtn").disabled = true;
    activePaymentMethod = null;
    
    const modal = getModal("paymentModal");
    if (modal) modal.show();
}

function selectPayment(method) {
    activePaymentMethod = method;
    document.getElementById("cashPayment").style.display = method === "cash" ? "block" : "none";
    document.getElementById("qrPayment").style.display = method === "qr" ? "block" : "none";
    
    if (method === "cash") {
        const total = posCart.reduce((s, item) => s + (item.price * item.quantity), 0);
        document.getElementById("cashReceived").value = Math.ceil(total);
        calculateChange();
    } else if (method === "qr") {
        document.getElementById("confirmPaymentBtn").disabled = false;
    }
}

function calculateChange() {
    const total = posCart.reduce((s, item) => s + (item.price * item.quantity), 0);
    const received = parseFloat(document.getElementById("cashReceived").value) || 0;
    const change = received - total;
    
    document.getElementById("changeAmount").textContent = "<?= CURRENCY_SYMBOL ?>" + Math.max(0, change).toFixed(2);
    document.getElementById("confirmPaymentBtn").disabled = received < total;
}

// Process payment and create order
async function processPayment() {
    if (!activePaymentMethod || posCart.length === 0) return;
    
    const customerName = document.getElementById("customerName").value || "Walk-in Customer";
    const customerPhone = document.getElementById("customerPhone").value || "";
    let cashReceived = null;
    let changeAmount = null;
    
    if (activePaymentMethod === "cash") {
        cashReceived = parseFloat(document.getElementById("cashReceived").value) || 0;
        const total = posCart.reduce((s, item) => s + (item.price * item.quantity), 0);
        changeAmount = cashReceived - total;
    }
    
    const orderData = {
        customer_name: customerName,
        customer_phone: customerPhone,
        order_type: "pos",
        payment_method: activePaymentMethod,
        notes: "",
        cash_received: cashReceived,
        change_amount: changeAmount,
        items: posCart.map(item => ({
            product_id: item.product_id,
            product_name: item.name,
            option_details: item.options || "",
            quantity: item.quantity,
            unit_price: item.price,
            subtotal: item.price * item.quantity,
            special_requests: item.special_requests || ""
        }))
    };
    
    document.getElementById("confirmPaymentBtn").disabled = true;
    document.getElementById("confirmPaymentBtn").innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing...`;
    
    try {
        const response = await apiFetch("<?= BASE_URL ?>pos/pos_api.php?action=create_order", {
            method: "POST",
            body: orderData
        });
        
        if (response.success) {
            showToast("Payment successful!", "success");
            
            posCart = [];
            updateCartUI(posCart);
            
            const paymentModal = getModal("paymentModal");
            if (paymentModal) paymentModal.hide();
            
            document.getElementById("customerName").value = "Walk-in Customer";
            document.getElementById("customerPhone").value = "";
            
            document.getElementById("confirmPaymentBtn").disabled = false;
            document.getElementById("confirmPaymentBtn").innerHTML = `<i class="fas fa-check me-2"></i>Confirm Payment`;
            
            try {
                showReceipt(response);
            } catch (receiptError) {
                console.error("Receipt display error:", receiptError);
            }
        } else {
            showToast(response.message || "Payment failed", "error");
            document.getElementById("confirmPaymentBtn").disabled = false;
            document.getElementById("confirmPaymentBtn").innerHTML = `<i class="fas fa-check me-2"></i>Confirm Payment`;
        }
    } catch (error) {
        console.error("Payment error:", error);
        showToast("Error processing payment", "error");
        document.getElementById("confirmPaymentBtn").disabled = false;
        document.getElementById("confirmPaymentBtn").innerHTML = `<i class="fas fa-check me-2"></i>Confirm Payment`;
    }
}

function showReceipt(data) {
    const order = data.order;
    const items = data.items;
    
    let html = `
        <div class="receipt">
            <div class="receipt-header">
                <h5 class="mb-0"><?= BUSINESS_NAME ?></h5>
                <small><?= BUSINESS_ADDRESS ?></small><br>
                <small>Tel: <?= BUSINESS_CONTACT ?></small>
                <hr>
                <strong>Order #${order.order_number}</strong><br>
                <small>${escapeHtml(order.customer_name)}</small><br>
                <small>${new Date().toLocaleString()}</small>
            </div>
            <div class="receipt-items">`;
    
    items.forEach(item => {
        html += `
            <div class="receipt-item">
                <span>${escapeHtml(item.product_name)} x${item.quantity}</span>
                <span><?= CURRENCY_SYMBOL ?>${parseFloat(item.subtotal).toFixed(2)}</span>
            </div>`;
    });
    
    html += `</div>
            <div class="receipt-item receipt-total">
                <span>TOTAL</span>
                <span><?= CURRENCY_SYMBOL ?>${parseFloat(order.total_amount).toFixed(2)}</span>
            </div>
            <div class="receipt-item">
                <span>Payment: ${order.payment_method.toUpperCase()}</span>
            </div>`;
    
    if (order.cash_received) {
        html += `
            <div class="receipt-item">
                <span>Cash</span>
                <span><?= CURRENCY_SYMBOL ?>${parseFloat(order.cash_received).toFixed(2)}</span>
            </div>
            <div class="receipt-item">
                <span>Change</span>
                <span><?= CURRENCY_SYMBOL ?>${parseFloat(order.change_amount).toFixed(2)}</span>
            </div>`;
    }
    
    html += `
            <hr>
            <p class="text-center small mb-0">Thank you for choosing <?= BUSINESS_NAME ?>!</p>
        </div>`;
    
    document.getElementById("receiptContent").innerHTML = html;
    const receiptModal = getModal("receiptModal");
    if (receiptModal) receiptModal.show();
    
    setTimeout(() => {
        if (confirm("Print receipt?")) {
            printReceipt(html);
        }
    }, 500);
}
</script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>