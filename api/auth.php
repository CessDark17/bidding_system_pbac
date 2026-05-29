<?php
/**
 * Authentication API Endpoints
 * File: api/auth.php
 * 
 * Handles user authentication, registration, profile management, and password reset
 */

require_once 'config.php';

// Additional auth-specific routes
class AuthAPI {
    
    /**
     * Change password endpoint
     * POST /api/auth/change-password
     */
    public static function changePassword() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload) {
            APIResponse::unauthorized('Invalid or expired token');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['current_password'])) {
            APIResponse::validationError(['current_password' => 'Current password is required']);
        }
        if (empty($data['new_password'])) {
            APIResponse::validationError(['new_password' => 'New password is required']);
        }
        if (strlen($data['new_password']) < 8) {
            APIResponse::validationError(['new_password' => 'New password must be at least 8 characters']);
        }
        if (isset($data['confirm_password']) && $data['new_password'] !== $data['confirm_password']) {
            APIResponse::validationError(['confirm_password' => 'Passwords do not match']);
        }
        
        $auth = new Auth();
        $result = $auth->changePassword($payload['user_id'], $data['current_password'], $data['new_password']);
        
        if ($result['success']) {
            // Log activity
            logActivity($payload['user_id'], 'CHANGE_PASSWORD', 'user', $payload['user_id']);
            APIResponse::success(null, 'Password changed successfully');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Update profile endpoint
     * PUT /api/auth/profile
     */
    public static function updateProfile() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload) {
            APIResponse::unauthorized('Invalid or expired token');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $allowed_fields = ['full_name', 'email', 'phone', 'department', 'position'];
        
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize($data[$field]);
            }
        }
        
        if (empty($update_data)) {
            APIResponse::validationError(['No valid fields to update']);
        }
        
        // Validate email if being updated
        if (isset($update_data['email']) && !filter_var($update_data['email'], FILTER_VALIDATE_EMAIL)) {
            APIResponse::validationError(['email' => 'Invalid email format']);
        }
        
        $auth = new Auth();
        $result = $auth->updateProfile($payload['user_id'], $update_data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'UPDATE_PROFILE', 'user', $payload['user_id']);
            APIResponse::success($result['user'], 'Profile updated successfully');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Get user profile endpoint
     * GET /api/auth/profile
     */
    public static function getProfile() {
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
            // Remove sensitive data
            unset($user['password_hash']);
            APIResponse::success($user, 'Profile retrieved');
        } else {
            APIResponse::error('User not found', 404);
        }
    }
    
    /**
     * Request password reset
     * POST /api/auth/forgot-password
     */
    public static function forgotPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email'])) {
            APIResponse::validationError(['email' => 'Email is required']);
        }
        
        $auth = new Auth();
        $result = $auth->requestPasswordReset($data['email']);
        
        // Always return success to prevent email enumeration
        APIResponse::success(null, 'If an account exists with that email, you will receive password reset instructions.');
    }
    
    /**
     * Reset password with token
     * POST /api/auth/reset-password
     */
    public static function resetPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['token'])) {
            APIResponse::validationError(['token' => 'Reset token is required']);
        }
        if (empty($data['new_password'])) {
            APIResponse::validationError(['new_password' => 'New password is required']);
        }
        if (strlen($data['new_password']) < 8) {
            APIResponse::validationError(['new_password' => 'Password must be at least 8 characters']);
        }
        if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
            APIResponse::validationError(['confirm_password' => 'Passwords do not match']);
        }
        
        $auth = new Auth();
        $result = $auth->resetPassword($data['token'], $data['new_password']);
        
        if ($result['success']) {
            APIResponse::success(null, 'Password reset successfully');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Refresh token endpoint
     * POST /api/auth/refresh
     */
    public static function refreshToken() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload) {
            APIResponse::unauthorized('Invalid or expired token');
        }
        
        // Generate new token
        $new_token = JWTToken::generate([
            'user_id' => $payload['user_id'],
            'username' => $payload['username'],
            'role' => $payload['role']
        ]);
        
        APIResponse::success(['token' => $new_token], 'Token refreshed');
    }
    
    /**
     * Get all users (admin only)
     * GET /api/auth/users
     */
    public static function getUsers() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        $auth = new Auth();
        $result = $auth->getAllUsers($page, $limit, $search);
        
        APIResponse::success($result, 'Users retrieved');
    }
    
    /**
     * Create user (admin only)
     * POST /api/auth/users
     */
    public static function createUser() {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'full_name', 'password', 'role'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        if (!empty($errors)) {
            APIResponse::validationError($errors);
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            APIResponse::validationError(['email' => 'Invalid email format']);
        }
        
        if (strlen($data['password']) < 8) {
            APIResponse::validationError(['password' => 'Password must be at least 8 characters']);
        }
        
        $auth = new Auth();
        $result = $auth->createUser($data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'CREATE_USER', 'user', $result['user']['id']);
            APIResponse::success($result['user'], 'User created successfully');
        } else {
            APIResponse::error($result['message'], 400, $result['errors'] ?? []);
        }
    }
    
    /**
     * Update user (admin only)
     * PUT /api/auth/users/:id
     */
    public static function updateUser($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $allowed_fields = ['full_name', 'email', 'phone', 'department', 'position', 'role', 'status'];
        
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize($data[$field]);
            }
        }
        
        // Allow password update separately
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                APIResponse::validationError(['password' => 'Password must be at least 8 characters']);
            }
            $update_data['password'] = $data['password'];
        }
        
        if (empty($update_data)) {
            APIResponse::validationError(['No fields to update']);
        }
        
        $auth = new Auth();
        $result = $auth->updateUser($id, $update_data);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'UPDATE_USER', 'user', $id);
            APIResponse::success($result['user'], 'User updated successfully');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
    
    /**
     * Delete user (admin only)
     * DELETE /api/auth/users/:id
     */
    public static function deleteUser($id) {
        $token = JWTToken::getFromHeader();
        if (!$token) {
            APIResponse::unauthorized('No token provided');
        }
        
        $payload = JWTToken::verify($token);
        if (!$payload || $payload['role'] !== 'admin') {
            APIResponse::unauthorized('Admin access required');
        }
        
        // Prevent self-deletion
        if ($id == $payload['user_id']) {
            APIResponse::error('Cannot delete your own account', 400);
        }
        
        $auth = new Auth();
        $result = $auth->deleteUser($id);
        
        if ($result['success']) {
            logActivity($payload['user_id'], 'DELETE_USER', 'user', $id);
            APIResponse::success(null, 'User deleted successfully');
        } else {
            APIResponse::error($result['message'], 400);
        }
    }
}

// Register auth-specific routes in the router
$router->register('POST', 'auth/change-password', [AuthAPI::class, 'changePassword']);
$router->register('GET', 'auth/profile', [AuthAPI::class, 'getProfile']);
$router->register('PUT', 'auth/profile', [AuthAPI::class, 'updateProfile']);
$router->register('POST', 'auth/forgot-password', [AuthAPI::class, 'forgotPassword']);
$router->register('POST', 'auth/reset-password', [AuthAPI::class, 'resetPassword']);
$router->register('POST', 'auth/refresh', [AuthAPI::class, 'refreshToken']);
$router->register('GET', 'auth/users', [AuthAPI::class, 'getUsers']);
$router->register('POST', 'auth/users', [AuthAPI::class, 'createUser']);
$router->register('PUT', 'auth/users/:id', [AuthAPI::class, 'updateUser']);
$router->register('DELETE', 'auth/users/:id', [AuthAPI::class, 'deleteUser']);

// Dispatch the request
$router->dispatch($method, $request_uri);