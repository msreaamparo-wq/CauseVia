<?php include 'header.php'; ?>

<?php
// Handle password change
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Get current user info
    $user_id = $_SESSION['user_id'];
    $query = "SELECT password, email FROM users WHERE id = $1";
    $user = fetchSingle($query, [$user_id]);
    
    if (!$user) {
        $message = 'User not found!';
        $message_type = 'danger';
    } elseif (!empty($current_password) && !empty($new_password)) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $message = 'Current password is incorrect!';
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match!';
            $message_type = 'danger';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters!';
            $message_type = 'danger';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = $1 WHERE id = $2";
            $result = executeQuery($query, [$hashed_password, $user_id]);
            
            if ($result) {
                $message = 'Password updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating password!';
                $message_type = 'danger';
            }
        }
    }
    
    // Update email if changed
    if (!empty($email) && $email !== $user['email']) {
        $query = "UPDATE users SET email = $1 WHERE id = $2";
        $result = executeQuery($query, [$email, $user_id]);
        
        if ($result) {
            $_SESSION['email'] = $email;
            if (empty($message)) {
                $message = 'Email updated successfully!';
                $message_type = 'success';
            }
        }
    }
}

// Get current user info
$user_id = $_SESSION['user_id'];
$query = "SELECT full_name, username, email, role FROM users WHERE id = $1";
$user_info = fetchSingle($query, [$user_id]);
?>

<div class="container-fluid">
    <h1 class="page-title">Settings</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Profile Information -->
            <div class="card card-custom mb-4">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px;">
                            <span class="text-white fs-3">
                                <?php echo strtoupper(substr($user_info['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <h4 class="mt-3"><?php echo htmlspecialchars($user_info['full_name']); ?></h4>
                        <p class="text-muted">Administrator</p>
                    </div>
                    
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Full Name:</th>
                            <td><?php echo htmlspecialchars($user_info['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($user_info['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user_info['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td><span class="badge bg-primary"><?php echo ucfirst($user_info['role']); ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Update Email -->
            <div class="card card-custom mb-4">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">Update Email</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-envelope"></i> Update Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header card-header-custom">
                    <h5 class="mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3">
                                <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                <h6>Database</h6>
                                <p class="text-muted mb-0">PostgreSQL</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3">
                                <i class="fab fa-php fa-2x text-info mb-2"></i>
                                <h6>PHP Version</h6>
                                <p class="text-muted mb-0"><?php echo phpversion(); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3">
                                <i class="fas fa-server fa-2x text-success mb-2"></i>
                                <h6>Server</h6>
                                <p class="text-muted mb-0">Apache</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3">
                                <i class="fas fa-calendar-alt fa-2x text-warning mb-2"></i>
                                <h6>Last Login</h6>
                                <p class="text-muted mb-0"><?php echo date('M d, Y'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>