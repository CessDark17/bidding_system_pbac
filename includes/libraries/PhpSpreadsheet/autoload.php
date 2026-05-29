<?php
/**
 * Simple autoloader for PhpSpreadsheet
 * File: includes/libraries/PhpSpreadsheet/autoload.php
 */

spl_autoload_register(function ($class) {
    // Define the base directory for the PhpSpreadsheet namespace
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/src/PhpSpreadsheet/';
    
    // Check if the class uses the PhpSpreadsheet namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Also load dependencies if needed
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}