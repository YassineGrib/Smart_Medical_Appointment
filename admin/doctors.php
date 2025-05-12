<?php
/**
 * Admin Doctors
 *
 * Manages doctor profiles and their schedules
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
        if (isset($_POST['create_doctor'])) {
            // Create new doctor
            $name = sanitizeInput($_POST['name']);
            $specialtyId = (int)$_POST['specialty_id'];
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);

            // Process schedule
            $schedule = [];
            for ($day = 1; $day <= 7; $day++) {
                if (isset($_POST["day_$day"]) && $_POST["day_$day"] === 'on') {
                    $schedule[$day] = [
                        'start' => $_POST["start_time_$day"],
                        'end' => $_POST["end_time_$day"]
                    ];
                }
            }
            $scheduleJson = json_encode($schedule);

            // Validate form data
            if (empty($name)) {
                $errors[] = 'Doctor name is required';
            }

            if ($specialtyId <= 0) {
                $errors[] = 'Specialty selection is required';
            }

            if (!empty($email) && !isValidEmail($email)) {
                $errors[] = 'Valid email address is required';
            }

            // Save doctor if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("
                    INSERT INTO doctors (
                        name, specialty_id, phone, email, schedule
                    ) VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "sisss",
                    $name, $specialtyId, $phone, $email, $scheduleJson
                );

                if ($stmt->execute()) {
                    $success = 'Doctor created successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to create doctor: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['update_doctor'])) {
            // Update existing doctor
            $id = (int)$_POST['doctor_id'];
            $name = sanitizeInput($_POST['name']);
            $specialtyId = (int)$_POST['specialty_id'];
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);

            // Process schedule
            $schedule = [];
            for ($day = 1; $day <= 7; $day++) {
                if (isset($_POST["day_$day"]) && $_POST["day_$day"] === 'on') {
                    $schedule[$day] = [
                        'start' => $_POST["start_time_$day"],
                        'end' => $_POST["end_time_$day"]
                    ];
                }
            }
            $scheduleJson = json_encode($schedule);

            // Validate form data
            if (empty($name)) {
                $errors[] = 'Doctor name is required';
            }

            if ($specialtyId <= 0) {
                $errors[] = 'Specialty selection is required';
            }

            if (!empty($email) && !isValidEmail($email)) {
                $errors[] = 'Valid email address is required';
            }

            // Update doctor if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("
                    UPDATE doctors SET
                        name = ?,
                        specialty_id = ?,
                        phone = ?,
                        email = ?,
                        schedule = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "sisssi",
                    $name, $specialtyId, $phone, $email, $scheduleJson, $id
                );

                if ($stmt->execute()) {
                    $success = 'Doctor updated successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to update doctor: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['delete_doctor'])) {
            // Delete doctor
            $id = (int)$_POST['doctor_id'];

            // Check if doctor has appointments
            $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($appointmentCount);
            $stmt->fetch();
            $stmt->close();

            if ($appointmentCount > 0) {
                $errors[] = "Cannot delete doctor with existing appointments. Please reassign or delete the appointments first.";
            } else {
                $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $success = 'Doctor deleted successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to delete doctor: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

// Get doctor details for edit/view
$doctor = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("
        SELECT d.*, s.name AS specialty_name
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        WHERE d.id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $doctor = $result->fetch_assoc();
        $doctor['schedule'] = json_decode($doctor['schedule'], true);
    } else {
        $errors[] = 'Doctor not found';
        $action = 'list';
    }

    $stmt->close();
}

// Get specialties for dropdown
$specialties = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $specialties[] = $row;
    }

    $stmt->close();
}

// Get doctors for list view
$doctors = [];
$totalDoctors = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($conn && $action === 'list') {
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(*) FROM doctors");
    $stmt->execute();
    $stmt->bind_result($totalDoctors);
    $stmt->fetch();
    $stmt->close();

    // Get doctors with pagination
    $stmt = $conn->prepare("
        SELECT d.*, s.name AS specialty_name
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        ORDER BY d.name
        LIMIT ?, ?
    ");

    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    $stmt->close();
}

// Calculate pagination
$totalPages = ceil($totalDoctors / $limit);

// Set page title
$pageTitle = 'Doctors';
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
                            echo 'Add New Doctor';
                            break;
                        case 'edit':
                            echo 'Edit Doctor';
                            break;
                        case 'view':
                            echo 'View Doctor';
                            break;
                        default:
                            echo 'Manage Doctors';
                    }
                    ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'list'): ?>
                        <a href="doctors.php?action=new" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add New Doctor
                        </a>
                    <?php else: ?>
                        <a href="doctors.php" class="btn btn-sm btn-secondary">
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
                <!-- Doctors List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Doctors List</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($doctors)): ?>
                            <p class="text-center text-muted">No doctors found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Specialty</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($doctors as $doc): ?>
                                            <tr>
                                                <td><?php echo $doc['id']; ?></td>
                                                <td><?php echo $doc['name']; ?></td>
                                                <td><?php echo $doc['specialty_name']; ?></td>
                                                <td><?php echo $doc['phone']; ?></td>
                                                <td><?php echo $doc['email']; ?></td>
                                                <td>
                                                    <a href="doctors.php?action=view&id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="doctors.php?action=edit&id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $doc['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $doc['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $doc['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $doc['id']; ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete this doctor?
                                                                    <p class="mt-2">
                                                                        <strong>Name:</strong> <?php echo $doc['name']; ?><br>
                                                                        <strong>Specialty:</strong> <?php echo $doc['specialty_name']; ?>
                                                                    </p>
                                                                    <div class="alert alert-warning">
                                                                        <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. All appointments with this doctor must be reassigned or deleted first.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <form method="post" action="doctors.php">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                        <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">
                                                                        <button type="submit" name="delete_doctor" class="btn btn-danger">Delete</button>
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
                <!-- Doctor Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo $action === 'new' ? 'Add New Doctor' : 'Edit Doctor'; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="doctors.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name">Doctor Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $action === 'edit' ? $doctor['name'] : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="specialty_id">Specialty</label>
                                    <select class="form-control" id="specialty_id" name="specialty_id" required>
                                        <option value="">Select Specialty</option>
                                        <?php foreach ($specialties as $specialty): ?>
                                            <option value="<?php echo $specialty['id']; ?>" <?php echo ($action === 'edit' && $doctor['specialty_id'] == $specialty['id']) ? 'selected' : ''; ?>>
                                                <?php echo $specialty['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $action === 'edit' ? $doctor['phone'] : ''; ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' ? $doctor['email'] : ''; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Working Schedule</label>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-md-3"><strong>Day</strong></div>
                                            <div class="col-md-4"><strong>Start Time</strong></div>
                                            <div class="col-md-4"><strong>End Time</strong></div>
                                            <div class="col-md-1"><strong>Active</strong></div>
                                        </div>

                                        <?php
                                        $days = [
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                            7 => 'Sunday'
                                        ];

                                        foreach ($days as $dayNum => $dayName):
                                            $isActive = $action === 'edit' && isset($doctor['schedule'][$dayNum]);
                                            $startTime = $isActive ? $doctor['schedule'][$dayNum]['start'] : '09:00';
                                            $endTime = $isActive ? $doctor['schedule'][$dayNum]['end'] : '17:00';
                                        ?>
                                            <div class="row mb-2 align-items-center">
                                                <div class="col-md-3"><?php echo $dayName; ?></div>
                                                <div class="col-md-4">
                                                    <input type="time" class="form-control" name="start_time_<?php echo $dayNum; ?>" value="<?php echo $startTime; ?>" <?php echo !$isActive ? 'disabled' : ''; ?>>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="time" class="form-control" name="end_time_<?php echo $dayNum; ?>" value="<?php echo $endTime; ?>" <?php echo !$isActive ? 'disabled' : ''; ?>>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input day-toggle" id="day_<?php echo $dayNum; ?>" name="day_<?php echo $dayNum; ?>" <?php echo $isActive ? 'checked' : ''; ?>>
                                                        <label class="custom-control-label" for="day_<?php echo $dayNum; ?>"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="<?php echo $action === 'new' ? 'create_doctor' : 'update_doctor'; ?>" class="btn btn-primary">
                                    <?php echo $action === 'new' ? 'Add Doctor' : 'Update Doctor'; ?>
                                </button>
                                <a href="doctors.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'view' && $doctor): ?>
                <!-- Doctor Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Doctor Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Name</th>
                                        <td><?php echo $doctor['name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Specialty</th>
                                        <td><?php echo $doctor['specialty_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php echo !empty($doctor['phone']) ? $doctor['phone'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo !empty($doctor['email']) ? $doctor['email'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($doctor['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($doctor['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5>Working Schedule</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Working Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $days = [
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                            7 => 'Sunday'
                                        ];

                                        foreach ($days as $dayNum => $dayName):
                                            $isWorkingDay = isset($doctor['schedule'][$dayNum]);
                                        ?>
                                            <tr>
                                                <td><?php echo $dayName; ?></td>
                                                <td>
                                                    <?php if ($isWorkingDay): ?>
                                                        <?php echo date('g:i A', strtotime($doctor['schedule'][$dayNum]['start'])); ?> -
                                                        <?php echo date('g:i A', strtotime($doctor['schedule'][$dayNum]['end'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not Available</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="doctors.php?action=edit&id=<?php echo $doctor['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Doctor
                            </a>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteViewModal">
                                <i class="fas fa-trash"></i> Delete Doctor
                            </button>
                            <a href="doctors.php" class="btn btn-secondary">
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
                                        Are you sure you want to delete this doctor?
                                        <p class="mt-2">
                                            <strong>Name:</strong> <?php echo $doctor['name']; ?><br>
                                            <strong>Specialty:</strong> <?php echo $doctor['specialty_name']; ?>
                                        </p>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> This action cannot be undone. All appointments with this doctor must be reassigned or deleted first.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <form method="post" action="doctors.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                            <button type="submit" name="delete_doctor" class="btn btn-danger">Delete</button>
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

<script>
// Enable/disable time inputs based on day toggle
document.addEventListener('DOMContentLoaded', function() {
    const dayToggles = document.querySelectorAll('.day-toggle');

    dayToggles.forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const dayNum = this.id.replace('day_', '');
            const startTime = document.querySelector(`[name="start_time_${dayNum}"]`);
            const endTime = document.querySelector(`[name="end_time_${dayNum}"]`);

            startTime.disabled = !this.checked;
            endTime.disabled = !this.checked;
        });
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>