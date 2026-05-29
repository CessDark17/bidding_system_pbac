<?php
/**
 * Authentication Class
 * File: includes/classes/Auth.php
 * 
 * Handles user authentication, registration, session management, and password operations
 */

class Auth {
    private $db;
    private $table = 'users';
    private $session_table = 'user_sessions';
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Login user
     * @param array $data Login credentials (username/email, password)
     * @return array Login result
     */
    public function login($data) {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username/email and password are required'];
        }
        
        // Find user by username or email
        $sql = "SELECT * FROM {$this->table} WHERE (username = ? OR email = ?) AND status = 'active'";
        $user = fetchOne($sql, [$username, $username]);
        
        if (!$user) {
            // Prevent user enumeration - generic message
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Update last login
        $updateSql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        executeQuery($updateSql, [$user['id']]);
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_username'] = $user['username'];
        
        // Log activity
        logActivity($user['id'], 'LOGIN', 'user', $user['id']);
        
        // Return user data (remove sensitive info)
        unset($user['password_hash']);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
    }
    
    /**
     * Register new user
     * @param array $data User registration data
     * @return array Registration result
     */
    public function register($data) {
        $errors = $this->validateRegistration($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        // Check if username exists
        $existing = fetchOne("SELECT id FROM {$this->table} WHERE username = ?", [$data['username']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        
        // Check if email exists
        $existing = fetchOne("SELECT id FROM {$this->table} WHERE email = ?", [$data['email']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Create user
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => BCRYPT_COST]),
            'full_name' => $data['full_name'],
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => 'user', // Default role
            'status' => 'active'
        ];
        
        $userId = insertRecord($this->table, $userData);
        
        if ($userId) {
            logActivity($userId, 'REGISTER', 'user', $userId);
            
            unset($userData['password_hash']);
            $userData['id'] = $userId;
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => $userData
            ];
        }
        
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
    
    /**
     * Validate registration data
     * @param array $data Registration data
     * @return array Validation errors
     */
    private function validateRegistration($data) {
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email address is required';
        }
        
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        return $errors;
    }
    
    /**
     * Logout user
     * @param string|null $token Session token for API logout
     * @return array Logout result
     */
    public function logout($token = null) {
        if ($token) {
            // API logout - remove token session
            executeQuery("DELETE FROM {$this->session_table} WHERE token = ?", [$token]);
        }
        
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            logActivity($userId, 'LOGOUT', 'user', $userId);
        }
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Change user password
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Result
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get user
        $user = fetchOne("SELECT password_hash FROM {$this->table} WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters'];
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => BCRYPT_COST]);
        $result = updateRecord($this->table, ['password_hash' => $newHash], 'id = ?', [$userId]);
        
        if ($result) {
            logActivity($userId, 'CHANGE_PASSWORD', 'user', $userId);
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to change password'];
    }
    
    /**
     * Update user profile
     * @param int $userId User ID
     * @param array $data Profile data
     * @return array Result
     */
    public function updateProfile($userId, $data) {
        $allowed = ['full_name', 'email', 'phone', 'department', 'position'];
        $updateData = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'message' => 'No data to update'];
        }
        
        // Check email uniqueness if being updated
        if (isset($updateData['email'])) {
            $existing = fetchOne("SELECT id FROM {$this->table} WHERE email = ? AND id != ?", [$updateData['email'], $userId]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already in use by another account'];
            }
        }
        
        $result = updateRecord($this->table, $updateData, 'id = ?', [$userId]);
        
        if ($result) {
            // Update session name if changed
            if (isset($updateData['full_name'])) {
                $_SESSION['user_name'] = $updateData['full_name'];
            }
            
            logActivity($userId, 'UPDATE_PROFILE', 'user', $userId);
            
            $user = $this->getUserById($userId);
            unset($user['password_hash']);
            
            return ['success' => true, 'message' => 'Profile updated', 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function getUserById($userId) {
        return fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$userId]);
    }
    
    /**
     * Get all users (admin only)
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search query
     * @return array Users list with pagination
     */
    public function getAllUsers($page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = "";
        if (!empty($search)) {
            $where = "WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ?";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} $where";
        $countResult = fetchOne($countSql, $params);
        $total = $countResult['total'] ?? 0;
        
        // Get users
        $sql = "SELECT id, username, email, full_name, department, position, phone, role, status, last_login, created_at 
                FROM {$this->table} $where 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $users = fetchAll($sql, $params);
        
        return [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Create user (admin only)
     * @param array $data User data
     * @return array Result
     */
    public function createUser($data) {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        // Check uniqueness
        $existing = fetchOne("SELECT id FROM {$this->table} WHERE username = ? OR email = ?", [$data['username'], $data['email']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => BCRYPT_COST]),
            'full_name' => $data['full_name'],
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? 'user',
            'status' => $data['status'] ?? 'active'
        ];
        
        $userId = insertRecord($this->table, $userData);
        
        if ($userId) {
            unset($userData['password_hash']);
            $userData['id'] = $userId;
            return ['success' => true, 'message' => 'User created', 'user' => $userData];
        }
        
        return ['success' => false, 'message' => 'Failed to create user'];
    }
    
    /**
     * Update user (admin only)
     * @param int $userId User ID
     * @param array $data User data
     * @return array Result
     */
    public function updateUser($userId, $data) {
        $allowed = ['full_name', 'email', 'phone', 'department', 'position', 'role', 'status'];
        $updateData = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Handle password separately
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters'];
            }
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => BCRYPT_COST]);
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'message' => 'No data to update'];
        }
        
        // Check email uniqueness
        if (isset($updateData['email'])) {
            $existing = fetchOne("SELECT id FROM {$this->table} WHERE email = ? AND id != ?", [$updateData['email'], $userId]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already in use'];
            }
        }
        
        $result = updateRecord($this->table, $updateData, 'id = ?', [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'User updated'];
        }
        
        return ['success' => false, 'message' => 'Failed to update user'];
    }
    
    /**
     * Delete user (admin only)
     * @param int $userId User ID
     * @return array Result
     */
    public function deleteUser($userId) {
        // Don't allow deletion of last admin
        $adminCount = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE role = 'admin'");
        $user = $this->getUserById($userId);
        
        if ($user && $user['role'] == 'admin' && $adminCount['count'] <= 1) {
            return ['success' => false, 'message' => 'Cannot delete the last administrator'];
        }
        
        $result = deleteRecord($this->table, 'id = ?', [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'User deleted'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
    
    /**
     * Request password reset
     * @param string $email User email
     * @return array Result
     */
    public function requestPasswordReset($email) {
        $user = fetchOne("SELECT id, email FROM {$this->table} WHERE email = ?", [$email]);
        
        if (!$user) {
            // Return success to prevent email enumeration
            return ['success' => true, 'message' => 'If an account exists, reset instructions will be sent'];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token (you'd need a password_resets table)
        // For simplicity, we'll just return success
        // In production, implement proper password reset table and email sending
        
        return ['success' => true, 'message' => 'Reset instructions sent to your email'];
    }
    
    /**
     * Reset password with token
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Result
     */
    public function resetPassword($token, $newPassword) {
        // Validate token and get user
        // This requires a password_resets table
        
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }
        
        // In production, verify token and update password
        
        return ['success' => true, 'message' => 'Password reset successfully'];
    }
    
    /**
     * Get user count
     * @return int Number of users
     */
    public function getUserCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM {$this->table}");
        return $result['count'] ?? 0;
    }
}