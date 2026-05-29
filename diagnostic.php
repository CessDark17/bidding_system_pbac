<?php
// diagnostic.php - Run this to diagnose issues
echo "<h1>FIBECO Bidding System Diagnostic</h1>";

// 1. PHP Version
echo "<h3>PHP Version:</h3>";
echo phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<span style='color:red'>Warning: PHP 7.4+ recommended</span><br>";
}

// 2. Required Extensions
echo "<h3>Required Extensions:</h3>";
$extensions = ['pdo_mysql', 'mysqli', 'json', 'session', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo $ext . ": " . ($loaded ? "✓" : "✗") . "<br>";
}

// 3. Database Connection
echo "<h3>Database Connection:</h3>";
try {
    require_once 'includes/config/database.php';
    if ($db) {
        echo "Database connected successfully!<br>";
        
        // Check if tables exist
        $tables = ['users', 'public_bidding', 'sealed_bidding', 'procurement_monitoring'];
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            echo "Table '$table': " . ($result->rowCount() > 0 ? "✓" : "✗") . "<br>";
        }
    } else {
        echo "<span style='color:red'>Database connection failed!</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Error: " . $e->getMessage() . "</span><br>";
}

// 4. Directory Permissions
echo "<h3>Directory Permissions:</h3>";
$dirs = ['uploads', 'uploads/bidding-documents', 'exports', 'logs'];
foreach ($dirs as $dir) {
    if (file_exists($dir)) {
        echo "$dir: Writable - " . (is_writable($dir) ? "✓" : "✗") . "<br>";
    } else {
        echo "$dir: <span style='color:red'>Not found - create it!</span><br>";
    }
}

// 5. Configuration
echo "<h3>Configuration:</h3>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : '<span style="color:red">NOT DEFINED</span>') . "<br>";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : '<span style="color:red">NOT DEFINED</span>') . "<br>";
?>