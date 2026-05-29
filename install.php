<?php
// install.php - Run this once to set up the database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>FIBECO Bidding System - Database Installation</h1>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'bidding_system';

// Enable mysqli exception handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create connection without database
    $conn = new mysqli($host, $user, $pass);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>✓ Database '$dbname' created or already exists</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating database: " . $conn->error . "</p>";
    }
    
    // Select the database
    $conn->select_db($dbname);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/sql/database.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>SQL file not found at: $sqlFile</p>");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // First, modify the SQL content to add IF NOT EXISTS to all CREATE TABLE statements
    // This prevents "table already exists" errors
    $sqlContent = preg_replace('/CREATE TABLE (\w+)/i', 'CREATE TABLE IF NOT EXISTS $1', $sqlContent);
    
    // Split by semicolon but respect quoted strings
    $queries = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $length = strlen($sqlContent);
    
    for ($i = 0; $i < $length; $i++) {
        $char = $sqlContent[$i];
        
        // Handle string literals
        if ($char === "'" || $char === '"') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($stringChar === $char && ($i > 0 && $sqlContent[$i-1] !== '\\')) {
                $inString = false;
            }
        }
        
        // Handle semicolon delimiter
        if ($char === ';' && !$inString) {
            $current = trim($current);
            if (!empty($current)) {
                $queries[] = $current;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    // Add last query if any
    $current = trim($current);
    if (!empty($current)) {
        $queries[] = $current;
    }
    
    // Execute queries with try-catch for each
    $success = 0;
    $failed = 0;
    $warnings = 0;
    $errors = [];
    $warningsList = [];
    
    foreach ($queries as $index => $query) {
        // Skip comments and empty queries
        if (empty($query) || preg_match('/^\s*--/', $query)) {
            continue;
        }
        
        try {
            if ($conn->query($query) === TRUE) {
                $success++;
            } else {
                $failed++;
                $errors[] = "Error in query " . ($index + 1) . ": " . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            // Check if it's a "table already exists" error (error code 1050)
            if ($e->getCode() == 1050) {
                $warnings++;
                $warningsList[] = "Table already exists (skipped): " . $e->getMessage();
            } 
            // Check for "database already exists" error
            elseif ($e->getCode() == 1007) {
                $warnings++;
                $warningsList[] = "Database already exists (skipped)";
            }
            else {
                $failed++;
                $errors[] = "Error in query " . ($index + 1) . ": " . $e->getMessage() . "<br>Query: " . substr($query, 0, 100) . "...";
            }
        }
    }
    
    echo "<h2>Installation Results</h2>";
    echo "<p>Successful queries: <strong style='color:green'>$success</strong></p>";
    
    if ($warnings > 0) {
        echo "<p>Warnings (skipped): <strong style='color:orange'>$warnings</strong></p>";
    }
    
    echo "<p>Failed queries: <strong style='color:red'>$failed</strong></p>";
    
    // Show warnings if any
    if (!empty($warningsList)) {
        echo "<h3>Warnings (Tables already exist - This is normal for re-installation):</h3>";
        echo "<ul>";
        foreach (array_slice($warningsList, 0, 10) as $warning) {
            echo "<li style='color:orange'>$warning</li>";
        }
        if (count($warningsList) > 10) {
            echo "<li>...and " . (count($warningsList) - 10) . " more warnings</li>";
        }
        echo "</ul>";
    }
    
    // Show errors if any
    if (!empty($errors)) {
        echo "<h3>Critical Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color:red'>$error</li>";
        }
        echo "</ul>";
    }
    
    // Verify tables
    echo "<h2>Verification</h2>";
    $tables = ['users', 'public_bidding', 'sealed_bidding', 'procurement_monitoring'];
    $allExist = true;
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'><th>Table</th><th>Status</th><th>Row Count</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result->num_rows > 0;
            $allExist = $allExist && $exists;
            
            $count = 0;
            if ($exists) {
                $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
                if ($countResult) {
                    $count = $countResult->fetch_assoc()['cnt'];
                }
            }
            
            $color = $exists ? 'green' : 'red';
            $status = $exists ? '✓ Exists' : '✗ Missing';
            $bgColor = $exists ? '#e8f5e8' : '#ffe8e8';
            
            echo "<tr style='background-color: $bgColor;'>
                    <td><strong>$table</strong></td>
                    <td style='color:$color; font-weight:bold;'>$status</td>
                    <td>$count</td>
                  </tr>";
        } catch (Exception $e) {
            echo "<tr>
                    <td><strong>$table</strong></td>
                    <td style='color:red'>✗ Error checking</td>
                    <td>N/A</td>
                  </tr>";
            $allExist = false;
        }
    }
    
    echo "</table>";
    
    // Final status
    if ($allExist && $failed == 0) {
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin-top: 20px;'>";
        echo "<p style='color: #155724; font-size: 18px; margin: 0;'>✓ Database installation successful!</p>";
        echo "</div>";
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Homepage →</a>";
        echo "<a href='login.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Login →</a>";
        echo "</div>";
    } elseif ($allExist && $warnings > 0 && $failed == 0) {
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px; padding: 15px; margin-top: 20px;'>";
        echo "<p style='color: #856404; font-size: 16px; margin: 0;'>⚠ Database already exists. Tables verified successfully!</p>";
        echo "</div>";
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Homepage →</a>";
        echo "<a href='login.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Login →</a>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-top: 20px;'>";
        echo "<p style='color: #721c24; font-size: 16px; margin: 0;'>✗ Some tables are missing or errors occurred. Please check the messages above.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-top: 20px;'>";
    echo "<p style='color: #721c24; font-size: 16px; margin: 0;'>✗ Fatal Error: " . $e->getMessage() . "</p>";
    echo "</div>";
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>