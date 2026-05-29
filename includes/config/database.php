<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Database Configuration File
 * FIBECO Bidding System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'bidding_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Enable error reporting for development
define('DB_DEBUG', true);

// Global database connection variable
$db = null;

/**
 * Get PDO database connection
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        if (DB_DEBUG) {
            die("Database Connection Error: " . $e->getMessage());
        }
        die("Sorry, we're experiencing technical difficulties. Please try again later.");
    }
}

// Initialize global connection
try {
    $db = getDBConnection();
} catch (Exception $e) {
    $db = null;
}

/**
 * Execute a query with parameters
 */
function executeQuery($sql, $params = []) {
    global $db;
    
    if (!$db) {
        error_log("Database not connected");
        return false;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        if (DB_DEBUG) {
            echo "SQL Error: " . $e->getMessage();
        }
        return false;
    }
}

/**
 * Get single record
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Get multiple records
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Insert record and return last insert ID
 */
function insertRecord($table, $data) {
    global $db;
    
    if (!$db) {
        return false;
    }
    
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Update record
 */
function updateRecord($table, $data, $where, $whereParams = []) {
    global $db;
    
    if (!$db) {
        return false;
    }
    
    $set = [];
    $params = [];
    
    foreach ($data as $column => $value) {
        $set[] = "`$column` = ?";
        $params[] = $value;
    }
    
    $params = array_merge($params, $whereParams);
    
    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
    
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Delete record
 */
function deleteRecord($table, $where, $params = []) {
    global $db;
    
    if (!$db) {
        return false;
    }
    
    $sql = "DELETE FROM `$table` WHERE $where";
    
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Begin transaction
 */
function beginTransaction() {
    global $db;
    if ($db) {
        $db->beginTransaction();
    }
}

/**
 * Commit transaction
 */
function commitTransaction() {
    global $db;
    if ($db) {
        $db->commit();
    }
}

/**
 * Rollback transaction
 */
function rollbackTransaction() {
    global $db;
    if ($db) {
        $db->rollBack();
    }
}
?>