<?php
/**
 * Duo Brew Menu Migration Runner
 * Executes the migrate_menu.sql script to replace all products
 * with the new Duo Brew menu.
 * 
 * Run this file once, then delete or secure it.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

// Detect MySQL port
$port = 3306;
$testConn = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 1);
if ($testConn) {
    $port = 3307;
    fclose($testConn);
}

try {
    $conn = new mysqli($host, $user, $pass, '', $port);
    if ($conn->connect_error) {
        $conn = new mysqli($host, $user, $pass);
    }
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    echo "<h2>Duo Brew System - Menu Migration</h2>";

    // Select database
    $conn->select_db('duo_brew_db');
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/sql/migrate_menu.sql');
    
    // Remove the USE statement and SELECT verification queries at the end
    // (we already selected the DB, and SELECT just shows output)
    $lines = explode("\n", $sql);
    $cleanedLines = [];
    $inSelectBlock = false;
    foreach ($lines as $line) {
        if (strpos($line, '-- Show summary') !== false || 
            strpos($line, '-- Show categories') !== false ||
            strpos($line, '-- Show count') !== false ||
            strpos($line, '-- Show sample') !== false) {
            $inSelectBlock = true;
        }
        if ($inSelectBlock && trim($line) === '') {
            $inSelectBlock = false;
            continue;
        }
        if ($inSelectBlock) continue;
        
        // Skip USE statements
        if (preg_match('/^USE\s/i', trim($line))) continue;
        
        $cleanedLines[] = $line;
    }
    $sql = implode("\n", $cleanedLines);
    
    // Split into individual statements
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        
        if ($conn->query($stmt)) {
            $successCount++;
        } else {
            // Ignore "already exists" errors for non-critical statements
            if (strpos($conn->error, 'already exists') === false) {
                $errors[] = "Error: " . $conn->error . "<br>SQL: " . htmlspecialchars(substr($stmt, 0, 100)) . "...";
                $errorCount++;
            } else {
                $successCount++;
            }
        }
    }
    
    if ($errorCount === 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong style='color: #155724;'>Migration Successful!</strong><br>";
        echo "All old products have been removed and the new Duo Brew menu has been added.<br><br>";
        
        // Show counts
        $result = $conn->query("SELECT COUNT(*) as cnt FROM categories");
        $row = $result->fetch_assoc();
        echo "Categories: <strong>{$row['cnt']}</strong><br>";
        
        $result = $conn->query("SELECT COUNT(*) as cnt FROM products");
        $row = $result->fetch_assoc();
        echo "Products: <strong>{$row['cnt']}</strong><br>";
        
        $result = $conn->query("SELECT COUNT(*) as cnt FROM product_options");
        $row = $result->fetch_assoc();
        echo "Product Options: <strong>{$row['cnt']}</strong><br>";
        
        echo "</div>";
        
        // Show categories table
        echo "<h3>Menu Structure</h3>";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 800px;'>";
        echo "<tr style='background: #6B3A2A; color: #fff;'><th>Category</th><th>Products</th><th>Sort Order</th></tr>";
        
        $result = $conn->query("
            SELECT c.name, c.sort_order, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.sort_order
            ORDER BY c.sort_order
        ");
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['name']}</td><td>{$row['product_count']}</td><td>{$row['sort_order']}</td></tr>";
        }
        echo "</table>";
        
        // Sample products with sizes
        echo "<h3>Sample Products with Pricing</h3>";
        echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 900px;'>";
        echo "<tr style='background: #6B3A2A; color: #fff;'><th>Product</th><th>Size</th><th>Total Price</th></tr>";
        
        $result = $conn->query("
            SELECT p.name as product, po.option_name as size, 
                   (p.price + po.price_modifier) as total_price
            FROM products p
            JOIN product_options po ON po.product_id = p.id
            WHERE po.option_type = 'size'
              AND p.id IN (1, 10, 21, 32, 43, 57, 62, 68, 73, 78, 80, 82)
            ORDER BY p.id, po.id
        ");
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['product']}</td><td>{$row['size']}</td><td>₱" . number_format($row['total_price'], 2) . "</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong style='color: #721c24;'>Migration Completed with Errors</strong><br>";
        echo "Successful statements: {$successCount}<br>";
        echo "Failed statements: {$errorCount}<br>";
        echo "</div>";
        
        foreach ($errors as $error) {
            echo "<div style='color: red; margin: 5px 0;'>{$error}</div>";
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='pos/index.php' style='display: inline-block; padding: 10px 20px; background: #6B3A2A; color: #fff; text-decoration: none; border-radius: 5px;'>Go to POS</a>";
    echo " &nbsp; ";
    echo "<a href='self-order/index.php' style='display: inline-block; padding: 10px 20px; background: #D4A574; color: #fff; text-decoration: none; border-radius: 5px;'>Go to Self-Ordering</a>";
    echo " &nbsp; ";
    echo "<a href='admin/products.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px;'>Admin Products</a>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 10px; background: #fff3cd; border-radius: 5px;'>";
    echo "<strong>⚠ Important:</strong> Delete or rename the <code>setup_migration.php</code> file after migration for security.";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Migration error: " . $e->getMessage());
}
?>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 20px; background: #f8f9fa; color: #333; }
    h2, h3 { color: #6B3A2A; border-bottom: 3px solid #D4A574; padding-bottom: 10px; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: .9em; }
    a { text-decoration: none; }
    a:hover { opacity: 0.85; }
    table tr:nth-child(even) { background: #f8f4ef; }
    table tr:hover { background: #efe6dc; }
    td { font-size: .9em; }
</style>

