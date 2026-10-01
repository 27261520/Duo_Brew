<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Database Setup Script
 * 
 * Run this file once to initialize the database.
 * Delete or secure this file after setup.
 */

// Database connection without database selection
$host = '127.0.0.1';
$user = 'root';
$pass = '';

// Try alternate ports if default fails
$port = 3306;

// Check if MySQL is running on a different port (common in some XAMPP setups)
$testConn = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 1);
if ($testConn) {
    $port = 3307;
    fclose($testConn);
}

try {
    $conn = new mysqli($host, $user, $pass, '', $port);
    
    if ($conn->connect_error) {
        // Try without port if failed
        $conn = new mysqli($host, $user, $pass);
    }
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Duo Brew System - Database Setup</h2>";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/sql/database.sql');
    
    // Split into individual statements
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        
        if ($conn->query($stmt)) {
            $successCount++;
        } else {
            // Ignore "already exists" errors
            if (strpos($conn->error, 'already exists') === false) {
                echo "<div style='color: red;'>Error: " . $conn->error . "<br>SQL: " . htmlspecialchars(substr($stmt, 0, 100)) . "...</div>";
                $errorCount++;
            } else {
                $successCount++;
            }
        }
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong style='color: #155724;'>Setup Complete!</strong><br>";
    echo "Database 'duo_brew_db' has been created with all tables and seed data.<br><br>";
    echo "<strong>Default Login Credentials:</strong><br>";
    echo "Admin: username = <code>admin</code>, password = <code>admin123</code><br>";
    echo "Staff: username = <code>staff1</code>, password = <code>admin123</code><br>";
    echo "Staff: username = <code>staff2</code>, password = <code>admin123</code><br>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #6B3A2A; color: #fff; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
    echo " &nbsp; ";
    echo "<a href='auth/login.php' style='display: inline-block; padding: 10px 20px; background: #D4A574; color: #fff; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
    echo "</div>";
    
    echo "<div style='margin-top: 30px; padding: 10px; background: #fff3cd; border-radius: 5px;'>";
    echo "<strong>⚠ Important:</strong> Delete or rename the <code>setup.php</code> file after setup for security.";
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Setup error: " . $e->getMessage());
}
?>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; background: #f8f9fa; color: #333; }
    h2 { color: #6B3A2A; border-bottom: 3px solid #D4A574; padding-bottom: 10px; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    div { margin: 5px 0; }
</style>

