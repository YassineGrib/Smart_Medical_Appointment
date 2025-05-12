<?php
/**
 * Admin Users
 *
 * Manages admin users with different roles
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin role to access this page
requireRole('admin');

// Get database connection
$conn = getDbConnection();

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission';
    } else {
        // Handle different form actions
        if (isset($_POST['create_user'])) {
            // Create new user
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password']; // Don't sanitize password
            $confirmPassword = $_POST['confirm_password']; // Don't sanitize password
            $role = sanitizeInput($_POST['role']);

            // Validate form data
            if (empty($username)) {
                $errors[] = 'Username is required';
            }

            if (empty($email) || !isValidEmail($email)) {
                $errors[] = 'Valid email address is required';
            }

            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }

            if (empty($role)) {
                $errors[] = 'Role is required';
            }

            // Check if username already exists
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    $errors[] = 'Username already exists';
                }
            }

            // Check if email already exists
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    $errors[] = 'Email already exists';
                }
            }

            // Save user if no errors
            if (empty($errors)) {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashedPassword, $email, $role);

                if ($stmt->execute()) {
                    $success = 'User created successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to create user: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['update_user'])) {
            // Update existing user
            $id = (int)$_POST['user_id'];
            $email = sanitizeInput($_POST['email']);
            $role = sanitizeInput($_POST['role']);
            $password = $_POST['password']; // Don't sanitize password
            $confirmPassword = $_POST['confirm_password']; // Don't sanitize password

            // Validate form data
            if (empty($email) || !isValidEmail($email)) {
                $errors[] = 'Valid email address is required';
            }

            if (empty($role)) {
                $errors[] = 'Role is required';
            }

            // Check if email already exists (for other users)
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $id);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    $errors[] = 'Email already exists';
                }
            }

            // Update user if no errors
            if (empty($errors)) {
                // Check if password should be updated
                if (!empty($password)) {
                    // Validate password
                    if (strlen($password) < 8) {
                        $errors[] = 'Password must be at least 8 characters long';
                    } elseif ($password !== $confirmPassword) {
                        $errors[] = 'Passwords do not match';
                    } else {
                        // Hash password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        $stmt = $conn->prepare("UPDATE admin_users SET email = ?, role = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $email, $role, $hashedPassword, $id);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE admin_users SET email = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $email, $role, $id);
                }

                if (empty($errors)) {
                    if ($stmt->execute()) {
                        $success = 'User updated successfully';
                        $action = 'list'; // Return to list view
                    } else {
                        $errors[] = 'Failed to update user: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        } elseif (isset($_POST['delete_user'])) {
            // Delete user
            $id = (int)$_POST['user_id'];

            // Prevent deleting the current user
            if ($id === (int)$_SESSION['user_id']) {
                $errors[] = 'You cannot delete your own account';
            } else {
                $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $success = 'User deleted successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to delete user: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

// Get user details for edit/view
$user = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT id, username, email, role, last_login, created_at, updated_at FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $errors[] = 'User not found';
        $action = 'list';
    }

    $stmt->close();
}

// Get users for list view
$users = [];
$totalUsers = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($conn && $action === 'list') {
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    $stmt->bind_result($totalUsers);
    $stmt->fetch();
    $stmt->close();

    // Get users with pagination
    $stmt = $conn->prepare("
        SELECT id, username, email, role, last_login, created_at
        FROM admin_users
        ORDER BY username
        LIMIT ?, ?
    ");

    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $stmt->close();
}

// Calculate pagination
$totalPages = ceil($totalUsers / $limit);

// Set page title
$pageTitle = 'Users';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php
                    switch ($action) {
                        case 'new':
                            echo 'Add New User';
                            break;
                        case 'edit':
                            echo 'Edit User';
                            break;
                        case 'view':
                            echo 'View User';
                            break;
                        default:
                            echo 'Manage Users';
                    }
                    ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'list'): ?>
                        <a href="users.php?action=new" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add New User
                        </a>
                    <?php else: ?>
                        <a href="users.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'new' || $action === 'edit'): ?>
                <!-- User Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo $action === 'new' ? 'Add New User' : 'Edit User'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="users.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <?php endif; ?>

                            <?php if ($action === 'new'): ?>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <small class="form-text text-muted">Username cannot be changed after creation.</small>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' ? $user['email'] : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin" <?php echo ($action === 'edit' && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="doctor" <?php echo ($action === 'edit' && $user['role'] === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="receptionist" <?php echo ($action === 'edit' && $user['role'] === 'receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                                    <option value="staff" <?php echo ($action === 'edit' && $user['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="password"><?php echo $action === 'new' ? 'Password' : 'New Password'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo $action === 'new' ? 'required' : ''; ?>>
                                <?php if ($action === 'edit'): ?>
                                    <small class="form-text text-muted">Leave blank to keep current password.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo $action === 'new' ? 'required' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="<?php echo $action === 'new' ? 'create_user' : 'update_user'; ?>" class="btn btn-primary">
                                    <?php echo $action === 'new' ? 'Add User' : 'Update User'; ?>
                                </button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'view' && $user): ?>
                <!-- User Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>User Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Username</th>
                                        <td><?php echo $user['username']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo $user['email']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td><span class="badge badge-info"><?php echo ucfirst($user['role']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Last Login</th>
                                        <td><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit User
                            </a>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteViewModal">
                                    <i class="fas fa-trash"></i> Delete User
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger" disabled title="You cannot delete your own account">
                                    <i class="fas fa-trash"></i> Delete User
                                </button>
                            <?php endif; ?>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteViewModal" tabindex="-1" role="dialog" aria-labelledby="deleteViewModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteViewModalLabel">Confirm Delete</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete this user?
                                        <p class="mt-2">
                                            <strong>Username:</strong> <?php echo $user['username']; ?><br>
                                            <strong>Email:</strong> <?php echo $user['email']; ?><br>
                                            <strong>Role:</strong> <?php echo ucfirst($user['role']); ?>
                                        </p>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <form method="post" action="users.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
