<?php
session_start();
require_once 'includes/config/database.php';

echo "<h2>Database Data Check</h2>";

// Check Sealed Bidding
$stmt = $db->query("SELECT COUNT(*) as count FROM sealed_bidding");
$sealedCount = $stmt->fetch();
echo "<p>Sealed Bidding Records: <strong>{$sealedCount['count']}</strong></p>";

// Check Public Bidding
$stmt = $db->query("SELECT COUNT(*) as count FROM public_bidding");
$publicCount = $stmt->fetch();
echo "<p>Public Bidding Records: <strong>{$publicCount['count']}</strong></p>";

// Check Procurement Monitoring
$stmt = $db->query("SELECT COUNT(*) as count FROM procurement_monitoring");
$procCount = $stmt->fetch();
echo "<p>Procurement Monitoring Records: <strong>{$procCount['count']}</strong></p>";

// Show sample data
echo "<h3>Sample Sealed Bidding Data:</h3>";
$stmt = $db->query("SELECT * FROM sealed_bidding LIMIT 3");
print "<pre>";
print_r($stmt->fetchAll());
print "</pre>";
?>