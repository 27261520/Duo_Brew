/**
 * Duo Brew POS & Self-Ordering System
 * Main JavaScript File
 */

'use strict';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // ----- Sidebar Toggle -----
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('wrapper');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('toggled');
        });
        
        // Auto close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            const isMobile = window.innerWidth <= 768;
            if (isMobile && !e.target.closest('#sidebar') && !e.target.closest('#menuToggle')) {
                sidebar.classList.remove('toggled');
            }
        });
    }

    // ----- Auto-dismiss alerts -----
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ----- Tooltip Initialization -----
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el);
    });

    // ----- Popover Initialization -----
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(el) {
        return new bootstrap.Popover(el);
    });

    // ----- Confirm Delete Dialogs -----
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Are you sure you want to proceed?')) {
                e.preventDefault();
            }
        });
    });

});

/**
 * Show a Bootstrap toast notification
 */
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const colors = {
        success: '#28a745',
        error: '#dc3545', 
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center border-0 fade-in';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.style.cssText = `border-left: 4px solid ${colors[type] || colors.info};`;
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icons[type] || icons.info} me-2" style="color: ${colors[type] || colors.info}"></i>
                ${message}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Create toast container if not exists
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Format number as currency
 */
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format date
 */
function formatDate(dateString, format = 'short') {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    
    if (format === 'long') {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleDateString('en-PH', options);
}

/**
 * AJAX Fetch Helper
 */
async function apiFetch(url, options = {}) {
    try {
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        };
        
        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }
        
        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Fetch Error:', error);
        showToast('Network error occurred', 'error');
        return { success: false, message: error.message };
    }
}

/**
 * Auto-refresh dashboard data
 */
function startAutoRefresh(interval = 30000) {
    setInterval(function() {
        const dashboardStats = document.getElementById('dashboardStats');
        if (dashboardStats) {
            refreshDashboardStats();
        }
    }, interval);
}

/**
 * Refresh dashboard statistics via AJAX
 */
async function refreshDashboardStats() {
    try {
        const data = await apiFetch('api/dashboard.php');
        
        if (data.success) {
            // Update stat values
            Object.keys(data.stats).forEach(key => {
                const el = document.getElementById(`stat_${key}`);
                if (el) {
                    el.textContent = data.stats[key];
                }
            });
            
            // Update recent orders table
            const ordersTable = document.getElementById('recentOrders');
            if (ordersTable && data.recentOrders) {
                ordersTable.innerHTML = data.recentOrders.map(order => `
                    <tr>
                        <td>${order.order_number}</td>
                        <td>${order.customer_name}</td>
                        <td><span class="badge ${order.status_badge}">${order.status_label}</span></td>
                        <td>${formatCurrency(order.total_amount)}</td>
                        <td>${formatDate(order.created_at)}</td>
                    </tr>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Auto-refresh error:', error);
    }
}

/**
 * Mark a single notification as read via AJAX
 */
async function markNotificationRead(notifId) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('id', notifId);
        
        const res = await fetch('notifications.php', {
            method: 'POST',
            body: formData
        });
        
        // Remove the unread styling from the dropdown item
        const item = document.querySelector(`[data-notif-id="${notifId}"]`);
        if (item) {
            item.classList.remove('unread');
        }
        
        // Update the badge count
        updateNotificationBadge();
    } catch (e) {
        console.error('Error marking notification as read:', e);
    }
}

/**
 * Mark all notifications as read via AJAX
 */
async function markAllRead() {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_all_read');
        
        const res = await fetch('notifications.php', {
            method: 'POST',
            body: formData
        });
        
        // Remove all unread styling
        document.querySelectorAll('.notification-list .dropdown-item.unread').forEach(item => {
            item.classList.remove('unread');
        });
        
        // Hide badge
        updateNotificationBadge();
        
        // Show toast
        showToast('All notifications marked as read', 'success');
    } catch (e) {
        console.error('Error marking all as read:', e);
        showToast('Failed to mark all as read', 'error');
    }
}

/**
 * Open notification detail view
 */
function viewNotification(notifId) {
    // First mark as read
    markNotificationRead(notifId);
    // Navigate to notifications page to view details
    window.location.href = 'notifications.php?view=' + notifId;
}

/**
 * Update the notification badge count
 */
async function updateNotificationBadge() {
    try {
        const res = await fetch('notifications.php?action=get_unread_count');
        const data = await res.json();
        
        const badge = document.querySelector('#notificationDropdown .badge');
        if (badge) {
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (e) {
        console.error('Error updating badge:', e);
    }
}

/**
 * Print receipt
 */
function printReceipt(receiptContent) {
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    if (!printWindow) {
        showToast('Please allow popups to print receipt', 'warning');
        return;
    }
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body { font-family: 'Courier New', monospace; font-size: 12px; padding: 20px; }
                .receipt { max-width: 280px; margin: 0 auto; }
                .receipt-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
                .receipt-items { border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
                .receipt-item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                .receipt-total { font-weight: bold; font-size: 16px; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <div class="receipt">
                ${receiptContent}
            </div>
            <script>
                window.print();
                setTimeout(function() { window.close(); }, 1000);
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

/**
 * Search products (for POS)
 */
function searchProducts(query) {
    const wrappers = document.querySelectorAll('.product-card-wrapper');
    const searchTerm = query.toLowerCase().trim();
    
    wrappers.forEach(wrapper => {
        const card = wrapper.querySelector('.product-card');
        if (!card) return;
        const name = card.dataset.productName?.toLowerCase() || '';
        const category = card.dataset.categoryName?.toLowerCase() || '';
        const matches = !searchTerm || name.includes(searchTerm) || category.includes(searchTerm);
        wrapper.style.display = matches ? '' : 'none';
    });
}

/**
 * Filter products by category
 */
function filterByCategory(categoryId) {
    const wrappers = document.querySelectorAll('.product-card-wrapper');
    
    // Update active pill
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.classList.toggle('active', pill.dataset.categoryId == categoryId);
    });
    
    wrappers.forEach(wrapper => {
        const card = wrapper.querySelector('.product-card');
        if (!card) return;
        if (categoryId == 0 || card.dataset.categoryId == categoryId) {
            wrapper.style.display = '';
            card.classList.add('fade-in');
        } else {
            wrapper.style.display = 'none';
            card.classList.remove('fade-in');
        }
    });
}

