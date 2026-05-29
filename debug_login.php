<?php
// debug_login.php - Test registration and login
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'includes/config/database.php';

echo "<h2>FIBECO - Login/Registration Debug Tool</h2>";

// Check users in database
try {
    $stmt = $db->query("SELECT id, username, email, full_name, role, status, LEFT(password_hash, 30) as hash_preview FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #0a2a4a; color: white;'>";
    echo "<th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th><th>Hash Preview</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td style='color: " . ($user['status'] == 'active' ? 'green' : 'red') . "; font-weight: bold;'>{$user['status']}</td>";
        echo "<td><code>" . htmlspecialchars($user['hash_preview']) . "...</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    
    // Test password verification for a specific user
    if (isset($_POST['test_username']) && isset($_POST['test_password'])) {
        $test_username = $_POST['test_username'];
        $test_password = $_POST['test_password'];
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$test_username, $test_username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<h3>Test Results for: {$test_username}</h3>";
            echo "<ul>";
            echo "<li>User found: Yes</li>";
            echo "<li>Status: <strong>{$user['status']}</strong> " . ($user['status'] == 'active' ? '✓' : '✗ Needs approval') . "</li>";
            echo "<li>Role: {$user['role']}</li>";
            
            if (password_verify($test_password, $user['password_hash'])) {
                echo "<li style='color: green;'>✓ Password verification: SUCCESS!</li>";
                echo "<li>You can login with these credentials!</li>";
            } else {
                echo "<li style='color: red;'>✗ Password verification: FAILED!</li>";
                echo "<li>The password you entered does not match the stored hash.</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>User '{$test_username}' not found in database.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>

<!-- Test Form -->
<h3>Test Login Credentials</h3>
<form method="POST" action="">
    <p>
        <label>Username or Email:</label><br>
        <input type="text" name="test_username" required style="padding: 5px; width: 200px;">
    </p>
    <p>
        <label>Password:</label><br>
        <input type="password" name="test_password" required style="padding: 5px; width: 200px;">
    </p>
    <button type="submit" style="background: #1a6dd4; color: white; padding: 10px 20px; border: none; cursor: pointer;">Test Login</button>
</form>

<hr>

<h3>Quick Fix - Make All Users Active</h3>
<form method="POST" action="">
    <input type="hidden" name="activate_all" value="1">
    <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;" 
            onclick="return confirm('Are you sure? This will activate all users.')">
        Activate All Users
    </button>
</form>

<?php
// Quick fix: Activate all users
if (isset($_POST['activate_all'])) {
    try {
        $db->exec("UPDATE users SET status = 'active' WHERE status = 'pending'");
        echo "<p style='color: green; margin-top: 10px;'>✓ All users have been activated!</p>";
        echo "<meta http-equiv='refresh' content='2'>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<br>
<a href="register.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Register New User</a>
&nbsp;&nbsp;
<a href="login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Login</a>