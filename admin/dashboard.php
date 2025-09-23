<?php
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$admin_username = $_SESSION['username'];

try {
    $db = Database::getInstance();
    
    // Get system statistics
    $stats = [
        'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
        'active_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
        'total_login_attempts' => $db->fetch("SELECT COUNT(*) as count FROM login_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
        'failed_logins' => $db->fetch("SELECT COUNT(*) as count FROM login_attempts WHERE attempt_type = 'failure' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
        'password_checks' => $db->fetch("SELECT COUNT(*) as count FROM password_evaluations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'],
        'avg_password_strength' => $db->fetch("SELECT AVG(strength_score) as avg FROM password_evaluations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['avg'] ?? 0,
    ];
    
    // Get all users
    $users = $db->fetchAll("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_active,
            u.is_verified,
            u.is_2fa_enabled,
            u.created_at,
            u.last_login,
            COUNT(la.id) as login_attempts,
            SUM(CASE WHEN la.attempt_type = 'success' THEN 1 ELSE 0 END) as successful_logins,
            SUM(CASE WHEN la.attempt_type = 'failure' THEN 1 ELSE 0 END) as failed_logins
        FROM users u
        LEFT JOIN login_attempts la ON u.id = la.user_id AND la.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    
    // Get recent login attempts
    $recent_attempts = $db->fetchAll("
        SELECT 
            la.id,
            la.user_id,
            u.username,
            la.username,
            la.attempt_type,
            la.ip_address,
            la.created_at,
            la.user_agent
        FROM login_attempts la
        LEFT JOIN users u ON la.user_id = u.id
        ORDER BY la.created_at DESC
        LIMIT 50
    ");
    
    // Get password strength statistics
    $strength_stats = $db->fetchAll("
        SELECT 
            CASE 
                WHEN strength_score >= 0.8 THEN 'Very Strong'
                WHEN strength_score >= 0.6 THEN 'Strong'
                WHEN strength_score >= 0.4 THEN 'Medium'
                WHEN strength_score >= 0.2 THEN 'Weak'
                ELSE 'Very Weak'
            END as strength_level,
            COUNT(*) as count,
            AVG(strength_score) * 100 as avg_score
        FROM password_evaluations
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY strength_level
        ORDER BY avg_score DESC
    ");
    
    // Get suspicious activity (multiple failed logins from same IP)
    $suspicious_ips = $db->fetchAll("
        SELECT 
            ip_address,
            COUNT(*) as attempts,
            MAX(created_at) as last_attempt,
            COUNT(DISTINCT username) as usernames_tried
        FROM login_attempts
        WHERE attempt_type = 'failure' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address
        HAVING attempts >= 5
        ORDER BY attempts DESC
        LIMIT 20
    ");
    
} catch (Exception $e) {
    logError('Admin dashboard error: ' . $e->getMessage());
    $stats = array_fill_keys(['total_users', 'active_users', 'total_login_attempts', 'failed_logins', 'password_checks', 'avg_password_strength'], 0);
    $users = [];
    $recent_attempts = [];
    $strength_stats = [];
    $suspicious_ips = [];
}

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $action = $_POST['action'];
    $user_id = intval($_POST['user_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'toggle_status':
                $db->query("UPDATE users SET is_active = !is_active WHERE id = ? AND id != ?", [$user_id, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'User status updated']);
                break;
                
            case 'reset_password':
                // In production, this would send a password reset email
                $new_password = bin2hex(random_bytes(8));
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$password_hash, $user_id]);
                echo json_encode(['success' => true, 'message' => "Password reset to: $new_password", 'new_password' => $new_password]);
                break;
                
            case 'delete_user':
                if ($user_id != $_SESSION['user_id']) {
                    $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        logError('Admin action error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Operation failed']);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><i class="fas fa-user-shield"></i> Admin Dashboard</a>
            <div class="navbar-menu">
                <span>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</span>
                <a href="../dashboard.php">User Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- System Statistics -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> System Overview (Last 30 Days)</h2>
                <p>Key metrics and system health indicators</p>
            </div>
            
            <div class="dashboard-grid">
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_login_attempts']); ?></div>
                    <div class="stat-label">Login Attempts</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['failed_logins']); ?></div>
                    <div class="stat-label">Failed Logins</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['password_checks']); ?></div>
                    <div class="stat-label">Password Checks</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_password_strength'] * 100, 1); ?>%</div>
                    <div class="stat-label">Avg Password Strength</div>
                </div>
            </div>
        </div>

        <!-- Password Strength Distribution -->
        <?php if (!empty($strength_stats)): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-key"></i> Password Strength Distribution</h2>
                <p>Password security levels across all users</p>
            </div>
            
            <div class="strength-distribution">
                <?php foreach ($strength_stats as $stat): ?>
                <div class="strength-stat-item">
                    <div class="strength-stat-label">
                        <span class="strength-label <?php echo strtolower(str_replace(' ', '-', $stat['strength_level'])); ?>">
                            <?php echo $stat['strength_level']; ?>
                        </span>
                    </div>
                    <div class="strength-stat-bar">
                        <div class="strength-stat-fill <?php echo strtolower(str_replace(' ', '-', $stat['strength_level'])); ?>" 
                             style="width: <?php echo ($stat['count'] / array_sum(array_column($strength_stats, 'count'))) * 100; ?>%"></div>
                    </div>
                    <div class="strength-stat-count">
                        <?php echo number_format($stat['count']); ?> passwords (<?php echo number_format($stat['avg_score'], 1); ?>%)
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Suspicious Activity -->
        <?php if (!empty($suspicious_ips)): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Suspicious Activity (Last 24 Hours)</h2>
                <p>IP addresses with multiple failed login attempts</p>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Failed Attempts</th>
                            <th>Usernames Tried</th>
                            <th>Last Attempt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suspicious_ips as $ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                            <td><span class="badge badge-danger"><?php echo $ip['attempts']; ?></span></td>
                            <td><?php echo $ip['usernames_tried']; ?></td>
                            <td><?php echo date('M j, H:i:s', strtotime($ip['last_attempt'])); ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="blockIP('<?php echo htmlspecialchars($ip['ip_address']); ?>')">
                                    Block IP
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Management -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <p>Manage user accounts and permissions</p>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr id="user-<?php echo $user['id']; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="status-compact">
                                    <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>

                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <!-- Role Management -->
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="changeUserRole(<?php echo $user['id']; ?>, 'promote_admin')" title="Promote to Admin">
                                                <i class="fas fa-user-shield"></i> Make Admin
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning" onclick="changeUserRole(<?php echo $user['id']; ?>, 'demote_user')" title="Demote to User">
                                                <i class="fas fa-user-minus"></i> Remove Admin
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Status Management -->
                                        <?php if ($user['is_active']): ?>
                                            <button class="btn btn-sm btn-secondary" onclick="changeUserRole(<?php echo $user['id']; ?>, 'deactivate_user')" title="Deactivate User">
                                                <i class="fas fa-user-times"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" onclick="changeUserRole(<?php echo $user['id']; ?>, 'activate_user')" title="Activate User">
                                                <i class="fas fa-user-check"></i> Activate
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Verification Management -->
                                        <?php if (!$user['is_verified']): ?>
                                            <button class="btn btn-sm btn-info" onclick="changeUserRole(<?php echo $user['id']; ?>, 'verify_user')" title="Manually Verify User">
                                                <i class="fas fa-check-circle"></i> Verify
                                            </button>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <small><em><i class="fas fa-crown"></i> Current Admin</em></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Login Attempts -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-sign-in-alt"></i> Recent Login Attempts</h2>
                <p>Latest authentication activities across all users</p>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_attempts, 0, 20) as $attempt): ?>
                        <tr>
                            <td><?php echo date('M j, H:i:s', strtotime($attempt['created_at'])); ?></td>
                            <td>
                                <?php if ($attempt['username']): ?>
                                    <?php echo htmlspecialchars($attempt['username']); ?>
                                <?php else: ?>
                                    <em>Unknown User</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $attempt['attempt_type'] === 'success' ? 'success' : 
                                         ($attempt['attempt_type'] === 'failure' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $attempt['attempt_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($attempt['ip_address']); ?></td>
                            <td><small><?php echo htmlspecialchars(substr($attempt['user_agent'], 0, 50)); ?>...</small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Role and user management functions
        function changeUserRole(userId, action) {
            const actionNames = {
                'promote_admin': 'promote this user to admin',
                'demote_user': 'demote this admin to regular user',
                'activate_user': 'activate this user account',
                'deactivate_user': 'deactivate this user account',
                'verify_user': 'manually verify this user'
            };
            
            const confirmMessage = actionNames[action] || 'perform this action';
            
            if (!confirm(`Are you sure you want to ${confirmMessage}?`)) return;
            
            performRoleAction(action, userId, function(data) {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Reload page to reflect changes
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            });
        }

        function performRoleAction(action, userId, callback) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', userId);
            formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
            
            fetch('manage_roles.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(data);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while updating user role', 'error');
            });
        }

        function blockIP(ipAddress) {
            if (!confirm(`Block IP address ${ipAddress}? This will prevent all access from this IP.`)) return;
            
            // In a real implementation, this would call a backend API to block the IP
            showMessage(`IP ${ipAddress} blocking is not implemented in this demo`, 'info');
        }

        function showMessage(text, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.admin-message');
            existingMessages.forEach(msg => msg.remove());
            
            const message = document.createElement('div');
            message.className = `message admin-message ${type}`;
            message.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${text}`;
            
            const container = document.querySelector('.container');
            container.insertBefore(message, container.firstChild);
            
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 5000);
        }

        // Auto-refresh data every 60 seconds (increased from 30 to reduce server load)
        setInterval(() => {
            location.reload();
        }, 60000);
        
        // Add some visual feedback for button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn')) {
                e.target.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    e.target.style.transform = 'scale(1)';
                }, 100);
            }
        });
    </script>
    </script>

    <style>
        .strength-distribution {
            margin-top: 1rem;
        }

        .strength-stat-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .strength-stat-label {
            min-width: 120px;
        }

        .strength-stat-bar {
            flex: 1;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .strength-stat-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .strength-stat-fill.very-strong {
            background: #10b981;
        }

        .strength-stat-fill.strong {
            background: #22c55e;
        }

        .strength-stat-fill.medium {
            background: #eab308;
        }

        .strength-stat-fill.weak {
            background: #f59e0b;
        }

        .strength-stat-fill.very-weak {
            background: #ef4444;
        }

        .strength-stat-count {
            min-width: 150px;
            font-size: 0.875rem;
            color: var(--secondary-color);
        }

        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        /* Compact table styles */
        .status-compact {
            text-align: center;
            min-width: 100px;
        }

        .status-compact .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            display: inline-block;
            width: 80px;
        }

        .status-compact .badge-sm {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            width: 70px;
        }

        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-top: none;
        }

        .table th:nth-child(1),
        .table th:nth-child(4),
        .table th:nth-child(5),
        .table th:nth-child(6) {
            text-align: center;
        }

        .table th:nth-child(2),
        .table th:nth-child(3) {
            text-align: left;
        }

        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table td:nth-child(2), 
        .table td:nth-child(3) {
            text-align: left;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fefce8;
            color: #a16207;
        }

        .badge-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        @media (max-width: 768px) {
            .strength-stat-item {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .table {
                font-size: 0.75rem;
            }
        }
    </style>
</body>
</html>