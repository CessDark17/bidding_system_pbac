<?php
/**
 * User Management
 * File: admin/users.php
 * 
 * Manage system users: create, edit, delete, change roles
 */

require_once '../includes/middleware/admin.php';
require_once '../includes/config/database.php';
require_once '../includes/config/functions.php';
require_once '../includes/classes/User.php';

$pageTitle = 'User Management';
$userClass = new User();

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Add/Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $data = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'full_name' => sanitize($_POST['full_name']),
            'department' => sanitize($_POST['department'] ?? ''),
            'position' => sanitize($_POST['position'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'role' => sanitize($_POST['role']),
            'status' => sanitize($_POST['status'] ?? 'active')
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        if ($_POST['user_id']) {
            // Update existing user
            if ($userClass->update($_POST['user_id'], $data)) {
                $message = 'User updated successfully.';
                logActivity($_SESSION['user_id'], 'UPDATE_USER', 'user', $_POST['user_id']);
            } else {
                $error = $userClass->getError() ?: 'Failed to update user.';
            }
        } else {
            // Create new user
            $user_id = $userClass->create($data);
            if ($user_id) {
                $message = 'User created successfully.';
                logActivity($_SESSION['user_id'], 'CREATE_USER', 'user', $user_id);
            } else {
                $error = $userClass->getError() ?: 'Failed to create user.';
            }
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        if ($userClass->delete($user_id)) {
            $message = 'User deleted successfully.';
            logActivity($_SESSION['user_id'], 'DELETE_USER', 'user', $user_id);
        } else {
            $error = 'Failed to delete user.';
        }
    }
}

// Get user for editing
$editUser = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $editUser = $userClass->getById((int)$_GET['id']);
    if (!$editUser) {
        $error = 'User not found.';
        $action = 'list';
    }
}

// Get all users
$users = $userClass->getAll();

include '../includes/templates/admin-header.php';
?>

<div class="admin-users">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="fas fa-users me-2"></i>User Management
        </h1>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New User
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($action == 'add' || $action == 'edit'): ?>
        <!-- Add/Edit User Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id'] ?? ''; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter a username.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label required">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($editUser['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($editUser['department'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position" 
                                   value="<?php echo htmlspecialchars($editUser['position'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label required">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo (($editUser['role'] ?? '') == 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="viewer" <?php echo (($editUser['role'] ?? '') == 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                                <option value="admin" <?php echo (($editUser['role'] ?? '') == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label required">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo (($editUser['status'] ?? '') == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($editUser['status'] ?? '') == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="suspended" <?php echo (($editUser['status'] ?? '') == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <?php echo $action == 'add' ? 'Password' : 'New Password (leave blank to keep current)'; ?>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   <?php echo $action == 'add' ? 'required' : ''; ?>>
                            <div class="form-text">Password must be at least 8 characters.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   <?php echo $action == 'add' ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?php echo $action == 'add' ? 'Create User' : 'Update User'; ?>
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            var password = document.getElementById('password').value;
            var confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        </script>
        
    <?php else: ?>
        <!-- User List Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 data-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $roleBadge = [
                                            'admin' => 'danger',
                                            'user' => 'primary',
                                            'viewer' => 'secondary'
                                        ];
                                        $badgeClass = $roleBadge[$user['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo getStatusBadge($user['status']); ?></td>
                                    <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=edit&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-danger confirm-delete" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/templates/admin-footer.php'; ?>