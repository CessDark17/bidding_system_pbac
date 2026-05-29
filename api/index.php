<?php
/**
 * API Entry Point
 * File: api/index.php
 * 
 * Routes all API requests to the appropriate handler
 */

// Route the request based on the URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove the script name from the URI
$path = str_replace(dirname($script_name), '', $request_uri);
$path = ltrim($path, '/');

// Remove query string
$path = strtok($path, '?');

// Load the appropriate API file based on the first segment
$segments = explode('/', $path);
$resource = $segments[0] ?? '';

switch ($resource) {
    case 'auth':
        require_once 'auth.php';
        break;
    case 'bidding':
        require_once 'bidding.php';
        break;
    case 'upload':
        require_once 'upload.php';
        break;
    case '':
        // API info endpoint
        header('Content-Type: application/json');
        echo json_encode([
            'name' => 'FIBECO Bidding System API',
            'version' => '1.0.0',
            'endpoints' => [
                'auth' => '/api/auth/*',
                'bidding' => '/api/bidding/*',
                'upload' => '/api/upload/*'
            ],
            'documentation' => 'See API documentation for details'
        ]);
        break;
    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint not found']);
        break;
}