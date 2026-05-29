<?php
/**
 * API Configuration File
 * File: api/config.php
 * 
 * Handles API routing, CORS, authentication, and response formatting
 */

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once '../includes/config/database.php';
require_once '../includes/config/constants.php';
require_once '../includes/config/functions.php';
require_once '../includes/classes/Auth.php';
require_once '../includes/classes/Bidding.php';
require_once '../includes/classes/Procurement.php';
require_once '../includes/classes/FileUploader.php';
require_once '../includes/classes/DataExtractor.php';

// API Response Helper Class
class APIResponse {
    /**
     * Send success response
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    /**
     * Send error response
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $errors Additional errors
     */
    public static function error($message = 'Error', $code = 400, $errors = []) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    /**
     * Send validation error response
     * @param array $errors Validation errors
     */
    public static function validationError($errors) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
}

// JWT Token Helper (simplified implementation)
class JWTToken {
    private static $secret = 'FIBECO_BIDDING_SECRET_KEY_2024';
    private static $algorithm = 'HS256';
    
    /**
     * Generate JWT token
     * @param array $payload Token payload
     * @param int $expiry Expiry time in seconds
     * @return string JWT token
     */
    public static function generate($payload, $expiry = TOKEN_EXPIRY) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Verify and decode JWT token
     * @param string $token JWT token
     * @return array|false Decoded payload or false
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        
        if ($payload['exp'] < time()) {
            return false; // Token expired
        }
        
        return $payload;
    }
    
    /**
     * Get token from Authorization header
     * @return string|null Token or null
     */
    public static function getFromHeader() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}

// API Router
class APIRouter {
    private $routes = [];
    
    /**
     * Register a route
     * @param string $method HTTP method
     * @param string $path Route path
     * @param callable $handler Handler function
     */
    public function register($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    /**
     * Dispatch request to appropriate handler
     * @param string $method HTTP method
     * @param string $uri Request URI
     */
    public function dispatch($method, $uri) {
        // Remove query string if present
        $uri = strtok($uri, '?');
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            
            // Simple path matching (supports parameters like /users/:id)
            $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9-]+)', $route['path']);
            $pattern = str_replace('/', '\/', $pattern);
            
            if (preg_match('/^' . $pattern . '$/', $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }
        
        APIResponse::error('Endpoint not found', 404);
    }
}

// Initialize router
$router = new APIRouter();

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Remove /api/ prefix from URI
$request_uri = preg_replace('/^\/api\//', '', $request_uri);

// ======================================================
// AUTHENTICATION ROUTES
// ======================================================

$router->register('POST', 'auth/login', function() {
    $data = json_decode(file_get_contents('php://input'), true);
    $auth = new Auth();
    $result = $auth->login($data);
    
    if ($result['success']) {
        // Generate JWT token
        $token = JWTToken::generate([
            'user_id' => $result['user']['id'],
            'username' => $result['user']['username'],
            'role' => $result['user']['role']
        ]);
        
        APIResponse::success([
            'token' => $token,
            'user' => $result['user']
        ], 'Login successful');
    } else {
        APIResponse::error($result['message'], 401);
    }
});

$router->register('POST', 'auth/register', function() {
    $data = json_decode(file_get_contents('php://input'), true);
    $auth = new Auth();
    $result = $auth->register($data);
    
    if ($result['success']) {
        APIResponse::success($result['user'], 'Registration successful');
    } else {
        APIResponse::error($result['message'], 400, $result['errors'] ?? []);
    }
});

$router->register('POST', 'auth/logout', function() {
    $token = JWTToken::getFromHeader();
    if ($token) {
        $auth = new Auth();
        $auth->logout($token);
    }
    APIResponse::success(null, 'Logged out successfully');
});

$router->register('GET', 'auth/verify', function() {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('No token provided');
    }
    
    $payload = JWTToken::verify($token);
    if (!$payload) {
        APIResponse::unauthorized('Invalid or expired token');
    }
    
    APIResponse::success(['user_id' => $payload['user_id'], 'role' => $payload['role']], 'Token valid');
});

$router->register('GET', 'auth/me', function() {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('No token provided');
    }
    
    $payload = JWTToken::verify($token);
    if (!$payload) {
        APIResponse::unauthorized('Invalid or expired token');
    }
    
    $auth = new Auth();
    $user = $auth->getUserById($payload['user_id']);
    
    if ($user) {
        APIResponse::success($user, 'User retrieved');
    } else {
        APIResponse::error('User not found', 404);
    }
});

// ======================================================
// BIDDING ROUTES
// ======================================================

// Public Bidding Routes (No authentication required for GET)
$router->register('GET', 'bidding/public', function() {
    $bidding = new Bidding();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $result = $bidding->getPublicBiddingList($page, $limit, $search, $status);
    APIResponse::success($result, 'Public bidding records retrieved');
});

$router->register('GET', 'bidding/public/:id', function($id) {
    $bidding = new Bidding();
    $record = $bidding->getPublicBiddingById($id);
    
    if ($record) {
        APIResponse::success($record, 'Record retrieved');
    } else {
        APIResponse::error('Record not found', 404);
    }
});

// Sealed Bidding Routes (Authentication required)
$router->register('GET', 'bidding/sealed', function() {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('Authentication required for sealed bidding');
    }
    
    $payload = JWTToken::verify($token);
    if (!$payload) {
        APIResponse::unauthorized('Invalid or expired token');
    }
    
    $bidding = new Bidding();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    $result = $bidding->getSealedBiddingList($page, $limit, $payload['user_id'], $payload['role']);
    APIResponse::success($result, 'Sealed bidding records retrieved');
});

$router->register('GET', 'bidding/sealed/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('Authentication required for sealed bidding');
    }
    
    $payload = JWTToken::verify($token);
    if (!$payload) {
        APIResponse::unauthorized('Invalid or expired token');
    }
    
    $bidding = new Bidding();
    $record = $bidding->getSealedBiddingById($id, $payload['user_id'], $payload['role']);
    
    if ($record) {
        APIResponse::success($record, 'Record retrieved');
    } else {
        APIResponse::error('Record not found or access denied', 404);
    }
});

// Admin-only bidding routes
$router->register('POST', 'bidding/public', function() {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $bidding = new Bidding();
    $result = $bidding->createPublicBidding($data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Public bidding record created');
    } else {
        APIResponse::error($result['message'], 400, $result['errors'] ?? []);
    }
});

$router->register('PUT', 'bidding/public/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $bidding = new Bidding();
    $result = $bidding->updatePublicBidding($id, $data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Public bidding record updated');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('DELETE', 'bidding/public/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $bidding = new Bidding();
    $result = $bidding->deletePublicBidding($id);
    
    if ($result['success']) {
        APIResponse::success(null, 'Public bidding record deleted');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('POST', 'bidding/sealed', function() {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $bidding = new Bidding();
    $result = $bidding->createSealedBidding($data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Sealed bidding record created');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('PUT', 'bidding/sealed/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $bidding = new Bidding();
    $result = $bidding->updateSealedBidding($id, $data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Sealed bidding record updated');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('DELETE', 'bidding/sealed/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $bidding = new Bidding();
    $result = $bidding->deleteSealedBidding($id);
    
    if ($result['success']) {
        APIResponse::success(null, 'Sealed bidding record deleted');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

// ======================================================
// PROCUREMENT MONITORING ROUTES
// ======================================================

$router->register('GET', 'procurement', function() {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $procurement = new Procurement();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $result = $procurement->getList($page, $limit, $search);
    APIResponse::success($result, 'Procurement records retrieved');
});

$router->register('GET', 'procurement/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $procurement = new Procurement();
    $record = $procurement->getById($id);
    
    if ($record) {
        APIResponse::success($record, 'Record retrieved');
    } else {
        APIResponse::error('Record not found', 404);
    }
});

$router->register('POST', 'procurement', function() {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $procurement = new Procurement();
    $result = $procurement->create($data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Procurement record created');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('PUT', 'procurement/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $procurement = new Procurement();
    $result = $procurement->update($id, $data);
    
    if ($result['success']) {
        APIResponse::success($result['record'], 'Procurement record updated');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

$router->register('DELETE', 'procurement/:id', function($id) {
    $token = JWTToken::getFromHeader();
    if (!$token || JWTToken::verify($token)['role'] !== 'admin') {
        APIResponse::unauthorized('Admin access required');
    }
    
    $procurement = new Procurement();
    $result = $procurement->delete($id);
    
    if ($result['success']) {
        APIResponse::success(null, 'Procurement record deleted');
    } else {
        APIResponse::error($result['message'], 400);
    }
});

// ======================================================
// STATISTICS & DASHBOARD ROUTES
// ======================================================

$router->register('GET', 'stats/overview', function() {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('Authentication required');
    }
    
    $payload = JWTToken::verify($token);
    if (!$payload) {
        APIResponse::unauthorized('Invalid or expired token');
    }
    
    $bidding = new Bidding();
    $procurement = new Procurement();
    
    $stats = [
        'public_bidding_count' => $bidding->getPublicCount(),
        'sealed_bidding_count' => $bidding->getSealedCount(),
        'procurement_count' => $procurement->getCount(),
        'total_abc' => $bidding->getTotalABC(),
        'total_awarded' => $bidding->getTotalAwarded()
    ];
    
    if ($payload['role'] === 'admin') {
        $auth = new Auth();
        $stats['total_users'] = $auth->getUserCount();
        $stats['pending_documents'] = $bidding->getPendingDocumentsCount();
    }
    
    APIResponse::success($stats, 'Statistics retrieved');
});

$router->register('GET', 'stats/monthly', function() {
    $token = JWTToken::getFromHeader();
    if (!$token) {
        APIResponse::unauthorized('Authentication required');
    }
    
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $bidding = new Bidding();
    $stats = $bidding->getMonthlyStats($year);
    
    APIResponse::success($stats, 'Monthly statistics retrieved');
});

// ======================================================
// DISPATCH REQUEST
// ======================================================

$router->dispatch($method, $request_uri);