<?php
// test_debug.php - Debug PHP configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Debug Information</h1>";

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if files exist
$files = [
    'register.php',
    'login.php',
    'includes/config/database.php',
    'includes/config/constants.php',
    'includes/config/functions.php',
    'includes/classes/Auth.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    echo "<p>" . $file . ": " . ($exists ? "✓ Exists" : "✗ MISSING") . "</p>";
}

// Test database connection
require_once 'includes/config/database.php';
if ($db) {
    echo "<p style='color:green'>✓ Database connected</p>";
} else {
    echo "<p style='color:red'>✗ Database connection failed</p>";
}
?>