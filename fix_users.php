<?php
// fix_users.php - Run this to add missing users
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config/database.php';

echo "<h2>FIBECO - Fix Users Table</h2>";

try {
    // Check if admin exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert admin user (password: Admin@123)
        $sql = "INSERT INTO `users` (username, email, password_hash, full_name, role, status, created_at) 
                VALUES ('admin', 'admin@fibeco.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active', NOW())";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>✓ Admin user already exists</p>";
    }
    
    // Check if test user exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'princess'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert regular user (password: User@123)
        $sql = "INSERT INTO `users` (username, email, password_hash, full_name, role, status, created_at) 
                VALUES ('princess', 'princessannjuban@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Princess Ann', 'user', 'active', NOW())";
        $db->exec($sql);
        echo "<p style='color: green;'>✓ Regular user (princess) created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>✓ Regular user already exists</p>";
    }
    
    // Show all users
    $stmt = $db->query("SELECT id, username, email, full_name, role, status FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #0a2a4a; color: white;'><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> Username: <code>admin</code> | Password: <code>Admin@123</code></li>";
    echo "<li><strong>Regular User:</strong> Username: <code>john_doe</code> | Password: <code>User@123</code></li>";
    echo "</ul>";
    
    echo "<br>";
    echo "<a href='login.php' style='background: #1a6dd4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page →</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>