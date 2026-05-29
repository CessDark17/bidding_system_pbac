<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'includes/config/database.php';

echo "<h2>User Status Check</h2>";

try {
    $stmt = $db->query("SELECT id, username, email, full_name, role, status FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #0a2a4a; color: white;'>";
    echo "<th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $status_color = '';
        if ($user['status'] == 'active') $status_color = 'green';
        elseif ($user['status'] == 'pending') $status_color = 'orange';
        elseif ($user['status'] == 'inactive') $status_color = 'red';
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td style='color: $status_color; font-weight: bold;'>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<h3>Actions:</h3>";
    echo "<form method='POST' action=''>";
    echo "<button type='submit' name='fix_status' style='background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
    echo "Set All Users to ACTIVE</button>";
    echo "</form>";
    
    if (isset($_POST['fix_status'])) {
        $db->exec("UPDATE users SET status = 'active'");
        echo "<p style='color: green; margin-top: 10px;'>✓ All users have been set to ACTIVE status!</p>";
        echo "<meta http-equiv='refresh' content='2'>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>