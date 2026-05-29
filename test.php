<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config/database.php';

try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    echo "<h2>Database Tables:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . implode('', $table) . "</li>";
    }
    echo "</ul>";
    
    // Check users table
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch();
    echo "<p>Total users: " . $count['count'] . "</p>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>