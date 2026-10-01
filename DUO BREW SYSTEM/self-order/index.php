<?php
$pageTitle = 'Self Ordering';
$publicPage = true;
$showSidebar = false;
require_once __DIR__ . '/../includes/header.php';
$db = Database::getInstance();
$categories = $db->getRows("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC");
$products = $db->getRows("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY c.sort_order ASC, p.name ASC");
$icons = [1=>['fa-mug-hot','#C4946A'],2=>['fa-glass-whiskey','#A8D8B9'],3=>['fa-snowflake','#B8D4E3'],4=>['fa-cookie-bite','#E8C48A'],5=>['fa-candy-cane','#F0B0B0']];
$currencySymbol = CURRENCY_SYMBOL;
?>
<style>
*{box-sizing:border-box}body{background:#f8f4ef;font-family:Inter,sans-serif}

/* Welcome Splash Overlay */
.welcome-splash {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: linear-gradient(135deg, #3D2013 0%, #5A2E1F 50%, #7A4A2A 100%);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    transition: opacity 0.4s ease, visibility 0.4s ease;
}
.welcome-splash.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}
.welcome-card {
    text-align: center;
    width: 100%;
    max-width: 440px;
    padding: 2.5rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 28px;
    backdrop-filter: blur(10px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.4);
}
.welcome-icon {
    width: 110px; height: 110px;
    background: linear-gradient(135deg, #D4A574, #C49564);
    border-radius: 50%;
    display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 3.8rem; color: #3D2013;
    box-shadow: 0 10px 30px rgba(212, 165, 116, 0.4);
    margin-bottom: 1.5rem;
}
.welcome-title { font-size: 2.6rem; font-weight: 900; letter-spacing: 0.5px; color: #fff; margin-bottom: 0.2rem; }
.welcome-subtitle { font-size: 1.1rem; color: #D4A574; font-weight: 500; margin-bottom: 2rem; }
.welcome-btn {
    background: #D4A574;
    color: #3D2013;
    font-weight: 800;
    font-size: 1.15rem;
    padding: 1rem 2.2rem;
    border-radius: 50px;
    display: inline-block;
    box-shadow: 0 8px 25px rgba(212, 165, 116, 0.3);
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
}
@keyframes pulseBtn {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(212, 165, 116, 0.7); }
    70% { transform: scale(1.04); box-shadow: 0 0 0 18px rgba(212, 165, 116, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(212, 165, 116, 0); }
}
.pulse-animation { animation: pulseBtn 2s infinite; }

.option-btn {
    background: #D4A574;
    color: #3D2013;
    border: none;
    transition: all 0.2s ease;
}
.option-btn:hover {
    background: #e8ba89;
    color: #3D2013;
    transform: translateY(-2px);
}

/* Page Elements */
.hd{background:linear-gradient(135deg,#5A2E1F,#7A4A2A);padding:.9rem 0;position:sticky;top:0;z-index:1000;box-shadow:0 2px 20px rgba(90,46,31,.25)}
.br{display:flex;align-items:center;gap:10px}.ico{width:40px;height:40px;background:linear-gradient(135deg,#D4A574,#C49564);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#5A2E1F}.hd h5{color:#fff;margin:0;font-weight:700;font-size:1rem}.hd small{color:#D4A574}
.hb{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:50px;padding:.4rem 1rem;font-size:.8rem}.hb:hover{background:rgba(255,255,255,.2);color:#fff}
.mh{text-align:center;padding:2rem 0 1rem}.mh h1{font-weight:800;color:#5A2E1F;font-size:1.8rem;margin-bottom:.3rem}.mh p{color:#b09585;font-size:.9rem}
.cs{display:flex;gap:8px;overflow-x:auto;padding:0 0 1rem}.cs::-webkit-scrollbar{display:none}
.cp{flex:0 0 auto;padding:.5rem 1.2rem;border-radius:50px;font-size:.85rem;font-weight:600;border:none;cursor:pointer;white-space:nowrap}
.cp.active{background:#5A2E1F;color:#fff}.cp:not(.active){background:#fff;color:#5A2E1F;border:1.5px solid #e0d6ce}
.pg{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}@media(min-width:576px){.pg{grid-template-columns:repeat(3,1fr)}}@media(min-width:768px){.pg{grid-template-columns:repeat(4,1fr)}}@media(min-width:992px){.pg{grid-template-columns:repeat(5,1fr)}}
.pc{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);cursor:pointer;height:100%;display:flex;flex-direction:column;transition:transform .15s ease,box-shadow .15s ease}
.pc:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(90,46,31,.12)}
.pc .pi{height:100px;display:flex;align-items:center;justify-content:center;font-size:2.6rem;position:relative}.pc .pt{position:absolute;top:8px;right:8px;font-size:.55rem;padding:.15rem .55rem;border-radius:50px;background:rgba(0,0,0,.4);color:#fff;font-weight:600}
.pc .pn{padding:.75rem .9rem .9rem;flex:1;display:flex;flex-direction:column}.pc .nm{font-weight:700;color:#3D2013;font-size:.9rem;margin-bottom:.2rem}.pc .de{font-size:.7rem;color:#b5a59a;margin-bottom:.4rem;flex:1}.pc .pr{font-weight:800;color:#7A4A2A;font-size:1.05rem;margin-top:auto}
.cf{position:fixed;bottom:20px;right:20px;z-index:999;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#5A2E1F,#7A4A2A);color:#fff;border:none;box-shadow:0 4px 20px rgba(90,46,31,.35);display:none;align-items:center;justify-content:center;font-size:1.3rem;cursor:pointer}
.cf:hover{color:#fff;transform:scale(1.08)}
.modal-content{border-radius:16px!important;border:none!important}.modal-header{border:none;padding:1.2rem 1.5rem 0}.modal-body{padding:1.2rem 1.5rem}.modal-footer{border:none;padding:.5rem 1.5rem 1.2rem}
.btn-brown{background:#7A4A2A;border-color:#7A4A2A;color:#fff}.btn-brown:hover{background:#5A2E1F;border-color:#5A2E1F;color:#fff}.btn-outline-brown{border-color:#7A4A2A;color:#7A4A2A}.btn-outline-brown:hover{background:#7A4A2A;color:#fff}
.oc{background:#fff;border-radius:16px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06)}.oc .on{font-size:1.3rem;font-weight:800;color:#5A2E1F}
.ti{border-radius:50px;padding:.8rem 1.2rem;border:2px solid #e0d6ce}.ti:focus{border-color:#7A4A2A;box-shadow:none}
.btr{border-radius:50px;padding:.8rem 1.5rem;background:linear-gradient(135deg,#5A2E1F,#7A4A2A);border:none;color:#fff;font-weight:600}
.tl{display:flex;padding:1rem 0}.ts{flex:1;text-align:center;position:relative}.ts .dt{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .3rem;font-size:.75rem;border:3px solid #e9ecef;background:#fff;color:#ccc;z-index:2}
.ts .lb{font-size:.6rem;font-weight:600;color:#ccc}.ts.dn .dt{background:#28A745;border-color:#28A745;color:#fff}.ts.act .dt{background:#5A2E1F;border-color:#5A2E1F;color:#fff;box-shadow:0 4px 12px rgba(90,46,31,.3)}.ts::after{content:"";position:absolute;top:16px;left:50%;width:100%;height:3px;background:#e9ecef;z-index:1}.ts:last-child::after{display:none}.ts.dn::after{background:#28A745}
</style>

<!-- Multi-Step Welcome Overlay -->
<div id="welcomeSplash" class="welcome-splash" onclick="goToStep2()">
    <div class="welcome-card" onclick="event.stopPropagation()">
        <div class="welcome-icon">
            <i class="fas fa-mug-hot"></i>
        </div>
        <h1 class="welcome-title">Duo Brew</h1>
        <p class="welcome-subtitle">Crafted Beverages & Sweet Treats</p>
        
        <!-- STEP 1: Tap to Start -->
        <div id="splashStep1">
            <div class="welcome-btn pulse-animation w-100" onclick="goToStep2()">
                <i class="fas fa-hand-pointer me-2"></i> Tap to Start Order
            </div>
        </div>

        <!-- STEP 2: Choose Dining Type -->
        <div id="splashStep2" style="display: none;">
            <p class="text-white-50 mb-3 fw-bold small text-uppercase" style="letter-spacing:1px">Select Dining Option</p>
            <div class="d-grid gap-3">
                <button class="btn option-btn btn-lg rounded-pill fw-bold py-3 shadow" onclick="startOrdering('dine_in')">
                    <i class="fas fa-utensils me-2"></i> Dine In
                </button>
                <button class="btn option-btn btn-lg rounded-pill fw-bold py-3 shadow" onclick="startOrdering('take_out')">
                    <i class="fas fa-shopping-bag me-2"></i> Take Out
                </button>
            </div>
        </div>
    </div>
</div>

<div class="hd">
<div class="container">
<div class="d-flex align-items-center justify-content-between">
<div class="br"><div class="ico"><i class="fas fa-mug-hot"></i></div><div><h5>Duo Brew</h5><small>Self-Ordering</small></div></div>
<div class="d-flex gap-2">
<button class="hb" onclick="resetKiosk()"><i class="fas fa-home me-1"></i> Start Over</button>
<button class="hb" onclick="showTrack()"><i class="fas fa-search me-1"></i> Track</button>
<button class="hb" onclick="showCart()"><i class="fas fa-shopping-cart me-1"></i> Cart <span class="badge bg-warning text-dark ms-1" id="cartCount">0</span></button>
</div>
</div>
</div>
</div>
<div class="container mh"><h1>Our Menu &#10024;</h1><p>Tap to customize &amp; order</p></div>
<div class="container">
<div class="cs">
<button class="cp active" onclick="filter(0)">All</button>
<?php foreach ($categories as $c): ?>
<button class="cp" onclick="filter(<?= $c['id'] ?>)"><?= htmlspecialchars($c['name']) ?></button>
<?php endforeach; ?>
</div>
</div>
<div class="container pb-5">
<div class="pg">
<?php foreach ($products as $p): $icon=$icons[$p['category_id']][0]??'fa-mug-hot'; $bg=$icons[$p['category_id']][1]??'#E8D5C4'; ?>
<div class="mi" data-cat="<?= $p['category_id'] ?>">
<div class="pc" onclick="openModal(<?= $p['id'] ?>)">
<div class="pi" style="background:<?= $bg ?>22"><i class="fas <?= $icon ?>" style="color:<?= $bg ?>"></i><span class="pt"><?= htmlspecialchars($p['cat_name']) ?></span></div>
<div class="pn"><div class="nm"><?= htmlspecialchars($p['name']) ?></div>
<?php if ($p['description']): ?><div class="de"><?= htmlspecialchars($p['description']) ?></div><?php endif; ?>
<div class="pr"><?= $currencySymbol ?><?= number_format($p['price'], 2) ?></div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
<button class="cf" onclick="showCart()" id="cartFloat" style="display:none"><i class="fas fa-shopping-cart"></i><span class="badge bg-danger rounded-pill" id="floatBdg">0</span></button>
<div class="modal fade" id="pModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-body p-4" id="pBody">
<div class="text-center py-4"><div class="spinner-border text-brown"></div></div>
</div>
</div>
</div>
</div>
<div class="modal fade" id="cModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body" id="cBody"></div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue</button>
<button type="button" class="btn btn-brown" onclick="checkout()" id="chkBtn"><i class="fas fa-check me-1"></i> Place Order</button>
</div>
</div>
</div>
</div>
<div class="modal fade" id="tModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title fw-bold"><i class="fas fa-search me-2"></i>Track Order</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div class="input-group mb-3">
<input type="text" class="form-control ti" id="tNum" placeholder="e.g. DB-20240101-0001">
<button class="btn btr" onclick="track()"><i class="fas fa-search me-1"></i> Track</button>
</div>
<div id="tRes"></div>
</div>
</div>
</div>
</div>
<div class="modal fade" id="sModal" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content text-center p-3">
<div class="modal-body">
<div style="font-size:3.5rem;color:#28A745"><i class="fas fa-check-circle"></i></div>
<h4 class="fw-bold mt-2" style="color:#5A2E1F">Order Placed!</h4>
<p class="text-muted mb-1">Your order number is:</p>
<h2 class="fw-bold" style="color:#5A2E1F" id="oNum">-</h2>
<p class="small text-muted">Save this number to track your order</p>
<div class="d-grid gap-2 mt-3">
<button class="btn btn-brown" onclick="doneTr()"><i class="fas fa-search me-1"></i> Track Order</button>
<button class="btn btn-outline-brown" onclick="doneCl()">Continue Ordering</button>
</div>
</div>
</div>
</div>
</div>
<?php
$map = json_encode(array_map(function($p) {
    return ['id' => $p['id'], 'name' => $p['name'], 'price' => floatval($p['price']), 'desc' => $p['description'] ?? ''];
}, $products));
$pageScripts = '
<script>
let cart = [];
let selectedOrderType = "dine_in";
const pmap = ' . $map . ';
const CUR = ' . json_encode($currencySymbol) . ';

function goToStep2() {
    document.getElementById("splashStep1").style.display = "none";
    document.getElementById("splashStep2").style.display = "block";
}

function startOrdering(type) {
    selectedOrderType = type || "dine_in";
    const splash = document.getElementById("welcomeSplash");
    if (splash) {
        splash.classList.add("hidden");
    }
}

function resetKiosk() {
    cart = [];
    refreshCartCount();
    document.getElementById("splashStep1").style.display = "block";
    document.getElementById("splashStep2").style.display = "none";
    const splash = document.getElementById("welcomeSplash");
    if (splash) {
        splash.classList.remove("hidden");
    }
}

function filter(id) {
    document.querySelectorAll(".mi").forEach(i => {
        i.style.display = (id === 0 || parseInt(i.dataset.cat) === id) ? "" : "none";
    });
}

function openModal(id) {
    const p = pmap.find(x => x.id == id);
    if (!p) return;
    fetch("' . BASE_URL . 'admin/products_api.php?action=get_options&product_id=" + id)
        .then(r => r.json())
        .then(opts => {
            opts = Array.isArray(opts) ? opts : [];
            const sz = opts.filter(o => o.option_type === "size");
            const fl = opts.filter(o => o.option_type === "flavor");
            const ad = opts.filter(o => o.option_type === "addon");
            let h = `<div class="d-flex justify-content-between align-items-start mb-2"><div><h5 class="fw-bold mb-1" style="color:#5A2E1F">${p.name}</h5>${p.desc ? `<p class="text-muted small mb-0">${p.desc}</p>` : ""}</div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><h3 class="fw-bold mb-3" style="color:#7A4A2A">${CUR}${p.price.toFixed(2)}</h3>`;
            if (sz.length) {
                h += `<label class="form-label fw-bold small">Size</label><div class="mb-3 d-flex gap-2 flex-wrap">`;
                sz.forEach(s => {
                    h += `<div class="form-check"><input type="radio" name="sz" class="btn-check" id="sz${s.id}" value=\'${JSON.stringify(s)}\' ${s.is_default ? "checked" : ""} onchange="upd(${p.price})"><label class="btn btn-outline-brown rounded-pill px-3" for="sz${s.id}">${s.option_name}${parseFloat(s.price_modifier) > 0 ? " +" + CUR + parseFloat(s.price_modifier).toFixed(2) : ""}</label></div>`;
                });
                h += `</div>`;
            }
            if (fl.length) {
                h += `<label class="form-label fw-bold small">Flavor</label><div class="mb-3 d-flex gap-2 flex-wrap">`;
                fl.forEach(f => {
                    h += `<div class="form-check"><input type="radio" name="fl" class="btn-check" id="fl${f.id}" value=\'${JSON.stringify(f)}\' onchange="upd(${p.price})"><label class="btn btn-outline-brown rounded-pill px-3" for="fl${f.id}">${f.option_name}</label></div>`;
                });
                h += `</div>`;
            }
            if (ad.length) {
                h += `<label class="form-label fw-bold small">Add-ons</label><div class="mb-3 d-flex gap-2 flex-wrap">`;
                ad.forEach(a => {
                    h += `<div class="form-check"><input type="checkbox" class="btn-check" id="ad${a.id}" value=\'${JSON.stringify(a)}\' onchange="upd(${p.price})"><label class="btn btn-outline-brown rounded-pill px-3" for="ad${a.id}">${a.option_name}${parseFloat(a.price_modifier) > 0 ? " +" + CUR + parseFloat(a.price_modifier).toFixed(2) : ""}</label></div>`;
                });
                h += `</div>`;
            }
            h += `<div class="mb-3"><label class="form-label fw-bold small">Qty</label><div class="input-group" style="max-width:130px"><button class="btn btn-outline-brown" onclick="qty(-1)">-</button><input type="number" class="form-control text-center" id="qty" value="1" min="1" readonly><button class="btn btn-outline-brown" onclick="qty(1)">+</button></div></div><div class="mb-3"><label class="form-label fw-bold small">Special Requests</label><textarea class="form-control" id="sp" rows="2" placeholder="e.g. Less ice, extra toppings..." style="font-size:.85rem"></textarea></div><div class="d-flex justify-content-between align-items-center pt-3 border-top"><div><small class="text-muted">Total:</small><h4 class="fw-bold mb-0" id="tot" style="color:#7A4A2A">${CUR}${p.price.toFixed(2)}</h4></div><button class="btn btn-brown btn-lg rounded-pill px-4" onclick="add(${p.id})"><i class="fas fa-cart-plus me-1"></i> Add</button></div>`;
            const body = document.getElementById("pBody");
            body.innerHTML = h;
            body.dataset.price = p.price;
            body.dataset.name = p.name;
            new bootstrap.Modal(document.getElementById("pModal")).show();
        })
        .catch(() => {
            let h = `<div class="d-flex justify-content-between align-items-start mb-3"><h5 class="fw-bold mb-0" style="color:#5A2E1F">${p.name}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><h3 class="fw-bold mb-3" style="color:#7A4A2A">${CUR}${p.price.toFixed(2)}</h3><div class="mb-3"><label class="form-label fw-bold small">Qty</label><div class="input-group" style="max-width:130px"><button class="btn btn-outline-brown" onclick="qty(-1)">-</button><input type="number" class="form-control text-center" id="qty" value="1" min="1" readonly><button class="btn btn-outline-brown" onclick="qty(1)">+</button></div></div><div class="mb-3"><label class="form-label fw-bold small">Special Requests</label><textarea class="form-control" id="sp" rows="2" style="font-size:.85rem"></textarea></div><div class="d-flex justify-content-between align-items-center pt-3 border-top"><div><small class="text-muted">Total:</small><h4 class="fw-bold mb-0" id="tot" style="color:#7A4A2A">${CUR}${p.price.toFixed(2)}</h4></div><button class="btn btn-brown btn-lg rounded-pill px-4" onclick="add(${p.id})"><i class="fas fa-cart-plus me-1"></i> Add</button></div>`;
            const body = document.getElementById("pBody");
            body.innerHTML = h;
            body.dataset.price = p.price;
            body.dataset.name = p.name;
            new bootstrap.Modal(document.getElementById("pModal")).show();
        });
}

function upd(base) {
    let t = base;
    document.querySelectorAll("#pBody input:checked").forEach(el => {
        try { t += parseFloat(JSON.parse(el.value).price_modifier); } catch (e) {}
    });
    const q = parseInt(document.getElementById("qty")?.value || 1);
    document.getElementById("tot").textContent = CUR + (t * q).toFixed(2);
}

function qty(d) {
    const i = document.getElementById("qty");
    let v = parseInt(i.value) + d;
    if (v < 1) v = 1;
    i.value = v;
    upd(parseFloat(document.getElementById("pBody").dataset.price));
}

function add(id) {
    const body = document.getElementById("pBody");
    const basePrice = parseFloat(body.dataset.price);
    const name = body.dataset.name;
    let t = basePrice;
    let opts = [];
    document.querySelectorAll("#pBody input:checked").forEach(el => {
        try {
            const o = JSON.parse(el.value);
            t += parseFloat(o.price_modifier);
            opts.push(o.option_name);
        } catch (e) {}
    });
    const q = parseInt(document.getElementById("qty")?.value || 1);
    const sp = document.getElementById("sp")?.value || "";
    for (let i = 0; i < q; i++) {
        cart.push({ pid: id, name, price: t, qty: 1, opts: opts.join(", "), sp });
    }
    refreshCartCount();
    bootstrap.Modal.getInstance(document.getElementById("pModal")).hide();
    if (typeof showToast === "function") showToast(name + " added!", "success");
}

function refreshCartCount() {
    const c = cart.reduce((s, i) => s + i.qty, 0);
    document.getElementById("cartCount").textContent = c;
    document.getElementById("floatBdg").textContent = c;
    document.getElementById("cartFloat").style.display = c > 0 ? "flex" : "none";
}

function showCart() {
    const b = document.getElementById("cBody");
    if (!cart.length) {
        b.innerHTML = `<div class="text-center py-5 text-muted"><i class="fas fa-shopping-cart fa-3x mb-2"></i><p>Your cart is empty</p></div>`;
    } else {
        let h = `<table class="table table-sm align-middle"><thead><tr><th>Item</th><th>Options</th><th class="text-center">Qty</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>`;
        let total = 0;
        cart.forEach((i, idx) => {
            total += i.price * i.qty;
            h += `<tr><td class="fw-medium">${i.name}</td><td><small class="text-muted">${i.opts || "—"}</small></td><td class="text-center">${i.qty}</td><td class="text-end fw-bold">${CUR}${i.price.toFixed(2)}</td><td><button class="btn btn-sm btn-outline-danger" onclick="rm(${idx})"><i class="fas fa-times"></i></button></td></tr>`;
        });
        h += `</tbody></table><div class="d-flex justify-content-between align-items-center pt-2 border-top"><h5 class="fw-bold mb-0">Total</h5><h4 class="fw-bold mb-0" style="color:#7A4A2A">${CUR}${total.toFixed(2)}</h4></div><div class="mt-3"><label class="form-label fw-bold small">Your Name</label><input type="text" class="form-control" id="cname" value="Customer"></div>`;
        b.innerHTML = h;
    }
    new bootstrap.Modal(document.getElementById("cModal")).show();
}

function rm(idx) {
    cart.splice(idx, 1);
    refreshCartCount();
    showCart();
}

async function checkout() {
    if (!cart.length) return;
    const nm = document.getElementById("cname")?.value || "Customer";
    const data = {
        customer_name: nm,
        customer_phone: "",
        order_type: selectedOrderType || "self",
        payment_method: "cash",
        notes: "Self-order (" + (selectedOrderType === "dine_in" ? "Dine In" : "Take Out") + ")",
        items: cart.map(i => ({
            product_id: i.pid,
            product_name: i.name,
            option_details: i.opts,
            quantity: i.qty,
            unit_price: i.price,
            subtotal: i.price * i.qty,
            special_requests: i.sp || ""
        }))
    };
    const btn = document.getElementById("chkBtn");
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Placing...`;
    try {
        const res = await fetch("' . BASE_URL . 'pos/pos_api.php?action=create_order", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const r = await res.json();
        if (r.success) {
            document.getElementById("oNum").textContent = r.order.order_number;
            cart = [];
            refreshCartCount();
            bootstrap.Modal.getInstance(document.getElementById("cModal")).hide();
            new bootstrap.Modal(document.getElementById("sModal")).show();
        } else {
            if (typeof showToast === "function") showToast(r.message || "Order failed", "error");
            else alert(r.message || "Order failed");
        }
    } catch (e) {
        if (typeof showToast === "function") showToast("Error placing order", "error");
        else alert("Error placing order");
    }
    btn.disabled = false;
    btn.innerHTML = `<i class="fas fa-check me-1"></i> Place Order`;
}

function doneTr() {
    bootstrap.Modal.getInstance(document.getElementById("sModal")).hide();
    setTimeout(() => showTrack(), 300);
}

function doneCl() {
    bootstrap.Modal.getInstance(document.getElementById("sModal")).hide();
    resetKiosk();
}

function showTrack() {
    document.getElementById("tNum").value = "";
    document.getElementById("tRes").innerHTML = "";
    new bootstrap.Modal(document.getElementById("tModal")).show();
}

async function track() {
    const n = document.getElementById("tNum").value.trim();
    if (!n) {
        if (typeof showToast === "function") showToast("Enter order number", "warning");
        return;
    }
    const resBox = document.getElementById("tRes");
    resBox.innerHTML = `<div class="text-center py-3"><div class="spinner-border text-brown"></div></div>`;
    try {
        const res = await fetch("' . BASE_URL . 'api/track_order.php?order_number=" + encodeURIComponent(n));
        const d = await res.json();
        if (d.found) {
            const st = ["pending", "preparing", "ready", "completed"];
            const idx = st.indexOf(d.order.status);
            resBox.innerHTML = `<div class="oc text-center"><div class="on">${d.order.order_number}</div><p class="text-muted">${d.order.customer_name}</p><div class="tl">${st.map((s, i) => `<div class="ts ${i < idx ? "dn" : i == idx ? "act" : ""}"><div class="dt"><i class="fas ${["fa-clock", "fa-fire", "fa-check-circle", "fa-check-double"][i]}"></i></div><div class="lb">${["PENDING", "PREPARING", "READY", "DONE"][i]}</div></div>`).join("")}</div><span class="badge bg-${d.order.status === "pending" ? "warning text-dark" : d.order.status === "preparing" ? "info" : d.order.status === "ready" ? "success" : d.order.status === "completed" ? "secondary" : "danger"}">${d.order.status.toUpperCase()}</span><hr><div class="small text-muted">Ordered: ${new Date(d.order.created_at).toLocaleString("en-PH")} | Total: ${CUR}${parseFloat(d.order.total_amount).toFixed(2)}</div></div>`;
        } else {
            resBox.innerHTML = `<div class="alert alert-danger text-center">Order not found</div>`;
        }
    } catch (e) {
        resBox.innerHTML = `<div class="alert alert-danger">Error tracking order</div>`;
    }
}
</script>
';
require_once __DIR__ . '/../includes/footer.php';
?>