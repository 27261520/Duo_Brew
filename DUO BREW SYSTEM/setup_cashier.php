<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Cashier Role Setup Script
 * 
 * Run this script once to:
 * 1. Insert the 'Cashier' role into the roles table
 * 2. Create a default cashier user account
 * 
 * Usage: Access this file via browser or run: php setup_cashier.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "============================================\n";
echo " Duo Brew - Cashier Role Setup\n";
echo "============================================\n\n";

$db = Database::getInstance();

// Step 1: Check if Cashier role already exists
$existingRole = $db->getRow(
    "SELECT id FROM roles WHERE role_name = 'Cashier'",
    [], ''
);

if ($existingRole) {
    echo "[SKIP] Cashier role already exists (ID: {$existingRole['id']})\n";
    $cashierRoleId = $existingRole['id'];
} else {
    // Insert Cashier role
    $cashierRoleId = $db->insert(
        "INSERT INTO roles (role_name) VALUES ('Cashier')",
        [], ''
    );
    echo "[OK] Cashier role created with ID: $cashierRoleId\n";
}

// Step 2: Create default cashier user
$existingCashier = $db->getRow(
    "SELECT id FROM users WHERE username = 'cashier1'",
    [], ''
);

if ($existingCashier) {
    echo "[SKIP] Cashier user 'cashier1' already exists (ID: {$existingCashier['id']})\n";
} else {
    // Password: cashier123
    $password = password_hash('cashier123', PASSWORD_DEFAULT);
    
    $userId = $db->insert(
        "INSERT INTO users (username, password, full_name, email, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
        ['cashier1', $password, 'Cashier User', 'cashier@duobrew.com', '09171234570', $cashierRoleId],
        'sssssi'
    );
    echo "[OK] Cashier user 'cashier1' created with ID: $userId (password: cashier123)\n";
}

// Summary
echo "\n--------------------------------------------\n";
echo " SETUP COMPLETE\n";
echo "--------------------------------------------\n";
echo " Cashier Login Credentials:\n";
echo "   Username: cashier1\n";
echo "   Password: cashier123\n";
echo "--------------------------------------------\n";

// Show all roles
echo "\nCurrent Roles:\n";
$roles = $db->getRows("SELECT * FROM roles ORDER BY id", [], '');
foreach ($roles as $r) {
    $count = $db->getValue("SELECT COUNT(*) FROM users WHERE role_id = ?", [$r['id']], 'i');
    echo "  - {$r['role_name']} (ID: {$r['id']}, Users: $count)\n";
}

echo "\nDone!\n";

