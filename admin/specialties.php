<?php
/**
 * Admin Specialties
 *
 * Manages medical specialties
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require login to access this page
requireLogin();

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
        if (isset($_POST['create_specialty'])) {
            // Create new specialty
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);

            // Validate form data
            if (empty($name)) {
                $errors[] = 'Specialty name is required';
            }

            // Save specialty if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO specialties (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);

                if ($stmt->execute()) {
                    $success = 'Specialty created successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to create specialty: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['update_specialty'])) {
            // Update existing specialty
            $id = (int)$_POST['specialty_id'];
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);

            // Validate form data
            if (empty($name)) {
                $errors[] = 'Specialty name is required';
            }

            // Update specialty if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE specialties SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);

                if ($stmt->execute()) {
                    $success = 'Specialty updated successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to update specialty: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['delete_specialty'])) {
            // Delete specialty
            $id = (int)$_POST['specialty_id'];

            // Check if specialty has doctors
            $stmt = $conn->prepare("SELECT COUNT(*) FROM doctors WHERE specialty_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($doctorCount);
            $stmt->fetch();
            $stmt->close();

            if ($doctorCount > 0) {
                $errors[] = "Cannot delete specialty with associated doctors. Please reassign or delete the doctors first.";
            } else {
                $stmt = $conn->prepare("DELETE FROM specialties WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $success = 'Specialty deleted successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to delete specialty: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

// Get specialty details for edit/view
$specialty = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM specialties WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $specialty = $result->fetch_assoc();
    } else {
        $errors[] = 'Specialty not found';
        $action = 'list';
    }

    $stmt->close();
}

// Get specialties for list view
$specialties = [];
$totalSpecialties = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($conn && $action === 'list') {
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) FROM specialties");
    $stmt->execute();
    $stmt->bind_result($totalSpecialties);
    $stmt->fetch();
    $stmt->close();

    // Get specialties with pagination
    $stmt = $conn->prepare("
        SELECT s.*, (SELECT COUNT(*) FROM doctors WHERE specialty_id = s.id) AS doctor_count
        FROM specialties s
        ORDER BY s.name
        LIMIT ?, ?
    ");

    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $specialties[] = $row;
    }

    $stmt->close();
}

// Calculate pagination
$totalPages = ceil($totalSpecialties / $limit);

// Set page title
$pageTitle = 'Specialties';
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
                            echo 'Add New Specialty';
                            break;
                        case 'edit':
                            echo 'Edit Specialty';
                            break;
                        case 'view':
                            echo 'View Specialty';
                            break;
                        default:
                            echo 'Manage Specialties';
                    }
                    ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'list'): ?>
                        <a href="specialties.php?action=new" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add New Specialty
                        </a>
                    <?php else: ?>
                        <a href="specialties.php" class="btn btn-sm btn-secondary">
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

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Specialties List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Specialties List</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($specialties)): ?>
                            <p class="text-center text-muted">No specialties found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Doctors</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($specialties as $spec): ?>
                                            <tr>
                                                <td><?php echo $spec['id']; ?></td>
                                                <td><?php echo $spec['name']; ?></td>
                                                <td><?php echo !empty($spec['description']) ? substr($spec['description'], 0, 100) . (strlen($spec['description']) > 100 ? '...' : '') : 'No description'; ?></td>
                                                <td><?php echo $spec['doctor_count']; ?></td>
                                                <td>
                                                    <a href="specialties.php?action=view&id=<?php echo $spec['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="specialties.php?action=edit&id=<?php echo $spec['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $spec['id']; ?>" <?php echo $spec['doctor_count'] > 0 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $spec['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $spec['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $spec['id']; ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete this specialty?
                                                                    <p class="mt-2">
                                                                        <strong>Name:</strong> <?php echo $spec['name']; ?>
                                                                    </p>
                                                                    <div class="alert alert-warning">
                                                                        <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="specialties.php">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                        <input type="hidden" name="specialty_id" value="<?php echo $spec['id']; ?>">
                                                                        <button type="submit" name="delete_specialty" class="btn btn-danger">Delete</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'new' || $action === 'edit'): ?>
                <!-- Specialty Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo $action === 'new' ? 'Add New Specialty' : 'Edit Specialty'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="specialties.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="specialty_id" value="<?php echo $specialty['id']; ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="name">Specialty Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $action === 'edit' ? $specialty['name'] : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo $action === 'edit' ? $specialty['description'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="<?php echo $action === 'new' ? 'create_specialty' : 'update_specialty'; ?>" class="btn btn-primary">
                                    <?php echo $action === 'new' ? 'Add Specialty' : 'Update Specialty'; ?>
                                </button>
                                <a href="specialties.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'view' && $specialty): ?>
                <!-- Specialty Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Specialty Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Name</th>
                                        <td><?php echo $specialty['name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td><?php echo !empty($specialty['description']) ? nl2br($specialty['description']) : 'No description'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($specialty['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($specialty['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Associated Doctors</h5>
                                <?php
                                // Get doctors with this specialty
                                $doctors = [];
                                if ($conn) {
                                    $stmt = $conn->prepare("SELECT id, name, phone, email FROM doctors WHERE specialty_id = ? ORDER BY name LIMIT 10");
                                    $stmt->bind_param("i", $specialty['id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($row = $result->fetch_assoc()) {
                                        $doctors[] = $row;
                                    }

                                    $stmt->close();
                                }
                                ?>

                                <?php if (empty($doctors)): ?>
                                    <div class="alert alert-info">
                                        No doctors associated with this specialty.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Contact</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($doctors as $doctor): ?>
                                                    <tr>
                                                        <td><?php echo $doctor['name']; ?></td>
                                                        <td>
                                                            <?php if (!empty($doctor['phone'])): ?>
                                                                <i class="fas fa-phone-alt mr-1"></i> <?php echo $doctor['phone']; ?><br>
                                                            <?php endif; ?>
                                                            <?php if (!empty($doctor['email'])): ?>
                                                                <i class="fas fa-envelope mr-1"></i> <?php echo $doctor['email']; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="doctors.php?action=view&id=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php
                                    // Get total doctor count
                                    $totalDoctors = 0;
                                    if ($conn) {
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM doctors WHERE specialty_id = ?");
                                        $stmt->bind_param("i", $specialty['id']);
                                        $stmt->execute();
                                        $stmt->bind_result($totalDoctors);
                                        $stmt->fetch();
                                        $stmt->close();

                                        if ($totalDoctors > 10) {
                                            echo '<div class="text-center mt-2">';
                                            echo '<a href="doctors.php" class="btn btn-link">View all ' . $totalDoctors . ' doctors</a>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="specialties.php?action=edit&id=<?php echo $specialty['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Specialty
                            </a>
                            <?php if (empty($doctors)): ?>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteViewModal">
                                    <i class="fas fa-trash"></i> Delete Specialty
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger" disabled title="Cannot delete specialty with associated doctors">
                                    <i class="fas fa-trash"></i> Delete Specialty
                                </button>
                            <?php endif; ?>
                            <a href="specialties.php" class="btn btn-secondary">
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
                                        Are you sure you want to delete this specialty?
                                        <p class="mt-2">
                                            <strong>Name:</strong> <?php echo $specialty['name']; ?>
                                        </p>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <form method="post" action="specialties.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="specialty_id" value="<?php echo $specialty['id']; ?>">
                                            <button type="submit" name="delete_specialty" class="btn btn-danger">Delete</button>
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