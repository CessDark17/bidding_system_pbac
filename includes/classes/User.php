<?php
/**
 * User Class
 * File: includes/classes/User.php
 * 
 * Handles user-specific operations (extends Auth for basic operations)
 */

class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function getById($userId) {
        $sql = "SELECT id, username, email, full_name, department, position, phone, role, status, last_login, created_at 
                FROM {$this->table} WHERE id = ?";
        return fetchOne($sql, [$userId]);
    }
    
    /**
     * Get user by username
     * @param string $username Username
     * @return array|null User data or null
     */
    public function getByUsername($username) {
        $sql = "SELECT id, username, email, full_name, department, position, phone, role, status, last_login, created_at 
                FROM {$this->table} WHERE username = ?";
        return fetchOne($sql, [$username]);
    }
    
    /**
     * Get user by email
     * @param string $email Email address
     * @return array|null User data or null
     */
    public function getByEmail($email) {
        $sql = "SELECT id, username, email, full_name, department, position, phone, role, status, last_login, created_at 
                FROM {$this->table} WHERE email = ?";
        return fetchOne($sql, [$email]);
    }
    
    /**
     * Get all users with pagination
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search query
     * @param string $role Filter by role
     * @param string $status Filter by status
     * @return array Users list with pagination
     */
    public function getAll($page = 1, $limit = 20, $search = '', $role = '', $status = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = "WHERE 1=1";
        
        if (!empty($search)) {
            $where .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($role)) {
            $where .= " AND role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $where .= " AND status = ?";
            $params[] = $status;
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
     * Create new user
     * @param array $data User data
     * @return array Result
     */
    public function create($data) {
        $errors = $this->validateUserData($data, true);
        
        if (!empty($errors)) {
            $this->lastError = $errors;
            return false;
        }
        
        // Check if username exists
        $existing = $this->getByUsername($data['username']);
        if ($existing) {
            $this->lastError = ['username' => 'Username already exists'];
            return false;
        }
        
        // Check if email exists
        $existing = $this->getByEmail($data['email']);
        if ($existing) {
            $this->lastError = ['email' => 'Email already registered'];
            return false;
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
            return $userId;
        }
        
        $this->lastError = ['general' => 'Failed to create user'];
        return false;
    }
    
    /**
     * Update user
     * @param int $userId User ID
     * @param array $data Update data
     * @return bool Success
     */
    public function update($userId, $data) {
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
                $this->lastError = ['password' => 'Password must be at least 8 characters'];
                return false;
            }
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => BCRYPT_COST]);
        }
        
        if (empty($updateData)) {
            $this->lastError = ['general' => 'No data to update'];
            return false;
        }
        
        // Check email uniqueness if being updated
        if (isset($updateData['email'])) {
            $existing = fetchOne("SELECT id FROM {$this->table} WHERE email = ? AND id != ?", [$updateData['email'], $userId]);
            if ($existing) {
                $this->lastError = ['email' => 'Email already in use'];
                return false;
            }
        }
        
        $result = updateRecord($this->table, $updateData, 'id = ?', [$userId]);
        
        if ($result) {
            return true;
        }
        
        $this->lastError = ['general' => 'Failed to update user'];
        return false;
    }
    
    /**
     * Delete user
     * @param int $userId User ID
     * @return bool Success
     */
    public function delete($userId) {
        // Don't allow deletion of last admin
        $adminCount = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE role = 'admin'");
        $user = $this->getById($userId);
        
        if ($user && $user['role'] == 'admin' && $adminCount['count'] <= 1) {
            $this->lastError = ['general' => 'Cannot delete the last administrator'];
            return false;
        }
        
        $result = deleteRecord($this->table, 'id = ?', [$userId]);
        
        if ($result) {
            return true;
        }
        
        $this->lastError = ['general' => 'Failed to delete user'];
        return false;
    }
    
    /**
     * Update user status (activate/deactivate/suspend)
     * @param int $userId User ID
     * @param string $status New status
     * @return bool Success
     */
    public function updateStatus($userId, $status) {
        $allowed = ['active', 'inactive', 'suspended'];
        
        if (!in_array($status, $allowed)) {
            $this->lastError = ['status' => 'Invalid status value'];
            return false;
        }
        
        // Don't allow deactivating last admin
        if ($status != 'active') {
            $adminCount = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE role = 'admin' AND status = 'active'");
            $user = $this->getById($userId);
            
            if ($user && $user['role'] == 'admin' && $adminCount['count'] <= 1) {
                $this->lastError = ['general' => 'Cannot deactivate the last active administrator'];
                return false;
            }
        }
        
        $result = updateRecord($this->table, ['status' => $status], 'id = ?', [$userId]);
        
        if ($result) {
            return true;
        }
        
        $this->lastError = ['general' => 'Failed to update status'];
        return false;
    }
    
    /**
     * Update user role
     * @param int $userId User ID
     * @param string $role New role
     * @return bool Success
     */
    public function updateRole($userId, $role) {
        $allowed = ['admin', 'user', 'viewer'];
        
        if (!in_array($role, $allowed)) {
            $this->lastError = ['role' => 'Invalid role value'];
            return false;
        }
        
        // Don't allow removing last admin role
        if ($role != 'admin') {
            $adminCount = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE role = 'admin'");
            $user = $this->getById($userId);
            
            if ($user && $user['role'] == 'admin' && $adminCount['count'] <= 1) {
                $this->lastError = ['general' => 'Cannot change role of the last administrator'];
                return false;
            }
        }
        
        $result = updateRecord($this->table, ['role' => $role], 'id = ?', [$userId]);
        
        if ($result) {
            return true;
        }
        
        $this->lastError = ['general' => 'Failed to update role'];
        return false;
    }
    
    /**
     * Get user count by role
     * @param string $role Role filter
     * @return int User count
     */
    public function getCountByRole($role = null) {
        if ($role) {
            $result = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE role = ?", [$role]);
        } else {
            $result = fetchOne("SELECT COUNT(*) as count FROM {$this->table}");
        }
        return $result['count'] ?? 0;
    }
    
    /**
     * Get active user count
     * @return int Active user count
     */
    public function getActiveCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'active'");
        return $result['count'] ?? 0;
    }
    
    /**
     * Search users by name or email
     * @param string $query Search query
     * @param int $limit Max results
     * @return array Matching users
     */
    public function search($query, $limit = 10) {
        $searchParam = "%$query%";
        $sql = "SELECT id, username, email, full_name, department, role 
                FROM {$this->table} 
                WHERE (username LIKE ? OR full_name LIKE ? OR email LIKE ?) 
                AND status = 'active'
                LIMIT ?";
        
        return fetchAll($sql, [$searchParam, $searchParam, $searchParam, $limit]);
    }
    
    /**
     * Validate user data
     * @param array $data User data
     * @param bool $isNew Whether this is for new user creation
     * @return array Validation errors
     */
    private function validateUserData($data, $isNew = false) {
        $errors = [];
        
        if ($isNew || isset($data['username'])) {
            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif (strlen($data['username']) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            }
        }
        
        if ($isNew || isset($data['email'])) {
            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Valid email is required';
            }
        }
        
        if ($isNew || isset($data['full_name'])) {
            if (empty($data['full_name'])) {
                $errors['full_name'] = 'Full name is required';
            }
        }
        
        if ($isNew && isset($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        }
        
        if (isset($data['password']) && isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        return $errors;
    }
    
    /**
     * Get last error
     * @return array|string Last error
     */
    public function getError() {
        return $this->lastError ?? null;
    }
    
    /**
     * Set error message
     * @param string|array $error Error message
     */
    public function setError($error) {
        $this->lastError = $error;
    }
}