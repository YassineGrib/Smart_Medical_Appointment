<?php
/**
 * Admin Appointments
 *
 * Manages patient appointments with CRUD functionality
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
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$errors = [];
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission';
    } else {
        // Handle different form actions
        if (isset($_POST['create_appointment'])) {
            // Create new appointment
            $patientName = sanitizeInput($_POST['patient_name']);
            $patientPhone = sanitizeInput($_POST['patient_phone']);
            $patientEmail = sanitizeInput($_POST['patient_email']);
            $doctorId = (int)$_POST['doctor_id'];
            $appointmentDate = $_POST['appointment_date'];
            $startTime = $_POST['start_time'];
            $notes = sanitizeInput($_POST['notes']);

            // Calculate end time (30 minutes after start time)
            $endTime = date('H:i:s', strtotime($startTime . ' + ' . APPOINTMENT_DURATION . ' minutes'));

            // Validate form data
            if (empty($patientName)) {
                $errors[] = 'Patient name is required';
            }

            if (empty($patientPhone) || !isValidPhone($patientPhone)) {
                $errors[] = 'Valid phone number is required';
            }

            if (empty($patientEmail) || !isValidEmail($patientEmail)) {
                $errors[] = 'Valid email address is required';
            }

            if ($doctorId <= 0) {
                $errors[] = 'Doctor selection is required';
            }

            if (empty($appointmentDate)) {
                $errors[] = 'Appointment date is required';
            }

            if (empty($startTime)) {
                $errors[] = 'Appointment time is required';
            }

            // Check if time slot is available
            if (empty($errors) && !isTimeSlotAvailable($doctorId, $appointmentDate, $startTime, $endTime)) {
                $errors[] = 'Selected time slot is not available. Please choose another time.';
            }

            // Save appointment if no errors
            if (empty($errors)) {
                // Generate tracking code
                $trackingCode = generateTrackingCode();

                // Insert appointment into database
                $stmt = $conn->prepare("
                    INSERT INTO appointments (
                        tracking_code, patient_name, patient_phone, patient_email,
                        doctor_id, appointment_date, start_time, end_time,
                        status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                ");

                $stmt->bind_param(
                    "ssssissss",
                    $trackingCode, $patientName, $patientPhone, $patientEmail,
                    $doctorId, $appointmentDate, $startTime, $endTime,
                    $notes
                );

                if ($stmt->execute()) {
                    $success = 'Appointment created successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to create appointment: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['update_appointment'])) {
            // Update existing appointment
            $id = (int)$_POST['appointment_id'];
            $patientName = sanitizeInput($_POST['patient_name']);
            $patientPhone = sanitizeInput($_POST['patient_phone']);
            $patientEmail = sanitizeInput($_POST['patient_email']);
            $doctorId = (int)$_POST['doctor_id'];
            $appointmentDate = $_POST['appointment_date'];
            $startTime = $_POST['start_time'];
            $status = $_POST['status'];
            $notes = sanitizeInput($_POST['notes']);

            // Calculate end time (30 minutes after start time)
            $endTime = date('H:i:s', strtotime($startTime . ' + ' . APPOINTMENT_DURATION . ' minutes'));

            // Validate form data
            if (empty($patientName)) {
                $errors[] = 'Patient name is required';
            }

            if (empty($patientPhone) || !isValidPhone($patientPhone)) {
                $errors[] = 'Valid phone number is required';
            }

            if (empty($patientEmail) || !isValidEmail($patientEmail)) {
                $errors[] = 'Valid email address is required';
            }

            if ($doctorId <= 0) {
                $errors[] = 'Doctor selection is required';
            }

            if (empty($appointmentDate)) {
                $errors[] = 'Appointment date is required';
            }

            if (empty($startTime)) {
                $errors[] = 'Appointment time is required';
            }

            // Get current appointment details to check if time slot changed
            $currentAppointment = null;
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT doctor_id, appointment_date, start_time FROM appointments WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $currentAppointment = $result->fetch_assoc();
                } else {
                    $errors[] = 'Appointment not found';
                }

                $stmt->close();
            }

            // Check if time slot is available (only if doctor, date, or time changed)
            if (empty($errors) && $currentAppointment) {
                $timeChanged = $currentAppointment['doctor_id'] != $doctorId ||
                               $currentAppointment['appointment_date'] != $appointmentDate ||
                               $currentAppointment['start_time'] != $startTime;

                if ($timeChanged && !isTimeSlotAvailable($doctorId, $appointmentDate, $startTime, $endTime, $id)) {
                    $errors[] = 'Selected time slot is not available. Please choose another time.';
                }
            }

            // Update appointment if no errors
            if (empty($errors)) {
                $stmt = $conn->prepare("
                    UPDATE appointments SET
                        patient_name = ?,
                        patient_phone = ?,
                        patient_email = ?,
                        doctor_id = ?,
                        appointment_date = ?,
                        start_time = ?,
                        end_time = ?,
                        status = ?,
                        notes = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "sssisssssi",
                    $patientName, $patientPhone, $patientEmail,
                    $doctorId, $appointmentDate, $startTime, $endTime,
                    $status, $notes, $id
                );

                if ($stmt->execute()) {
                    $success = 'Appointment updated successfully';
                    $action = 'list'; // Return to list view
                } else {
                    $errors[] = 'Failed to update appointment: ' . $stmt->error;
                }

                $stmt->close();
            }
        } elseif (isset($_POST['delete_appointment'])) {
            // Delete appointment
            $id = (int)$_POST['appointment_id'];

            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $success = 'Appointment deleted successfully';
                $action = 'list'; // Return to list view
            } else {
                $errors[] = 'Failed to delete appointment: ' . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Get appointment details for edit/view
$appointment = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("
        SELECT a.*, d.name AS doctor_name, s.name AS specialty_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN specialties s ON d.specialty_id = s.id
        WHERE a.id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
    } else {
        $errors[] = 'Appointment not found';
        $action = 'list';
    }

    $stmt->close();
}

// Get doctors for dropdown
$doctors = [];
if ($conn) {
    $stmt = $conn->prepare("
        SELECT d.id, d.name, s.name AS specialty
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        ORDER BY d.name
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    $stmt->close();
}

// Get appointments for list view
$appointments = [];
$totalAppointments = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($conn && $action === 'list') {
    // Build query based on filters
    $query = "
        SELECT a.*, d.name AS doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
    ";

    $countQuery = "SELECT COUNT(*) FROM appointments a";
    $whereClause = [];
    $params = [];
    $types = "";

    if (!empty($status)) {
        $whereClause[] = "a.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($date)) {
        $whereClause[] = "a.appointment_date = ?";
        $params[] = $date;
        $types .= "s";
    }

    if (!empty($whereClause)) {
        $query .= " WHERE " . implode(" AND ", $whereClause);
        $countQuery .= " WHERE " . implode(" AND ", $whereClause);
    }

    // Add order by and limit
    $query .= " ORDER BY a.appointment_date DESC, a.start_time DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";

    // Get total count for pagination
    $stmt = $conn->prepare($countQuery);
    if (!empty($whereClause) && !empty($types)) {
        // Only bind parameters if we have where clauses and types
        $paramTypes = substr($types, 0, strlen($types) - 2); // Remove the "ii" for pagination
        $countParams = array_slice($params, 0, count($params) - 2); // Remove pagination params

        // Make sure we have a non-empty type string before binding
        if (!empty($paramTypes)) {
            $stmt->bind_param($paramTypes, ...$countParams);
        }
    }
    $stmt->execute();
    $stmt->bind_result($totalAppointments);
    $stmt->fetch();
    $stmt->close();

    // Get appointments
    $stmt = $conn->prepare($query);
    // We always have parameters for pagination (LIMIT ?, ?)
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();
}

// Calculate pagination
$totalPages = ceil($totalAppointments / $limit);

// Set page title
$pageTitle = 'Appointments';
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
                            echo 'Create New Appointment';
                            break;
                        case 'edit':
                            echo 'Edit Appointment';
                            break;
                        case 'view':
                            echo 'View Appointment';
                            break;
                        default:
                            echo 'Manage Appointments';
                    }
                    ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($action === 'list'): ?>
                        <a href="appointments.php?action=new" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> New Appointment
                        </a>
                    <?php else: ?>
                        <a href="appointments.php" class="btn btn-sm btn-secondary">
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
                <!-- Filter Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Appointments</h6>
                    </div>
                    <div class="card-body">
                        <form method="get" action="appointments.php" class="form-inline">
                            <div class="form-group mr-3 mb-2">
                                <label for="status" class="mr-2"><i class="fas fa-tag mr-1"></i> Status:</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group mr-3 mb-2">
                                <label for="date" class="mr-2"><i class="fas fa-calendar-day mr-1"></i> Date:</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>">
                            </div>
                            <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search mr-1"></i> Apply Filters</button>
                            <a href="appointments.php" class="btn btn-secondary mb-2 ml-2"><i class="fas fa-undo mr-1"></i> Reset</a>
                        </form>
                    </div>
                </div>

                <!-- Appointments List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list mr-2"></i>Appointments List</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointments)): ?>
                            <p class="text-center text-muted">No appointments found</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tracking Code</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $apt): ?>
                                            <tr>
                                                <td><?php echo $apt['id']; ?></td>
                                                <td><?php echo $apt['tracking_code']; ?></td>
                                                <td><?php echo $apt['patient_name']; ?></td>
                                                <td><?php echo $apt['doctor_name']; ?></td>
                                                <td><?php echo formatDate($apt['appointment_date']); ?></td>
                                                <td><?php echo formatTime($apt['start_time']); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($apt['status']) {
                                                        case 'pending':
                                                            $statusClass = 'badge-warning';
                                                            break;
                                                        case 'confirmed':
                                                            $statusClass = 'badge-info';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'badge-success';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'badge-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php
                                                        $statusIcon = '';
                                                        switch ($apt['status']) {
                                                            case 'pending':
                                                                $statusIcon = '<i class="fas fa-clock mr-1"></i>';
                                                                break;
                                                            case 'confirmed':
                                                                $statusIcon = '<i class="fas fa-check-circle mr-1"></i>';
                                                                break;
                                                            case 'completed':
                                                                $statusIcon = '<i class="fas fa-check-double mr-1"></i>';
                                                                break;
                                                            case 'cancelled':
                                                                $statusIcon = '<i class="fas fa-times-circle mr-1"></i>';
                                                                break;
                                                        }
                                                        echo $statusIcon . ucfirst($apt['status']);
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="appointments.php?action=view&id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="appointments.php?action=edit&id=<?php echo $apt['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $apt['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>

                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $apt['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $apt['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $apt['id']; ?>">Confirm Delete</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Are you sure you want to delete this appointment?
                                                                    <p class="mt-2">
                                                                        <strong>Patient:</strong> <?php echo $apt['patient_name']; ?><br>
                                                                        <strong>Date:</strong> <?php echo formatDate($apt['appointment_date']); ?><br>
                                                                        <strong>Time:</strong> <?php echo formatTime($apt['start_time']); ?>
                                                                    </p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancel</button>
                                                                    <form method="post" action="appointments.php">
                                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                                        <button type="submit" name="delete_appointment" class="btn btn-danger"><i class="fas fa-trash mr-1"></i> Delete</button>
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
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&date=<?php echo $date; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&date=<?php echo $date; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&date=<?php echo $date; ?>" aria-label="Next">
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
                <!-- Appointment Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php if ($action === 'new'): ?>
                                <i class="fas fa-plus-circle mr-2"></i>Create New Appointment
                            <?php else: ?>
                                <i class="fas fa-edit mr-2"></i>Edit Appointment
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="post" action="appointments.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="patient_name"><i class="fas fa-user mr-1"></i> Patient Name</label>
                                    <input type="text" class="form-control" id="patient_name" name="patient_name" value="<?php echo $action === 'edit' ? $appointment['patient_name'] : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="patient_phone"><i class="fas fa-phone mr-1"></i> Phone Number</label>
                                    <input type="tel" class="form-control" id="patient_phone" name="patient_phone" value="<?php echo $action === 'edit' ? $appointment['patient_phone'] : ''; ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="patient_email"><i class="fas fa-envelope mr-1"></i> Email Address</label>
                                <input type="email" class="form-control" id="patient_email" name="patient_email" value="<?php echo $action === 'edit' ? $appointment['patient_email'] : ''; ?>" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="doctor_id"><i class="fas fa-user-md mr-1"></i> Doctor</label>
                                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['id']; ?>" <?php echo ($action === 'edit' && $appointment['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                                <?php echo $doctor['name']; ?> (<?php echo $doctor['specialty']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="appointment_date"><i class="fas fa-calendar-alt mr-1"></i> Appointment Date</label>
                                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="<?php echo $action === 'edit' ? $appointment['appointment_date'] : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="start_time"><i class="fas fa-clock mr-1"></i> Appointment Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $action === 'edit' ? substr($appointment['start_time'], 0, 5) : ''; ?>" required>
                                </div>
                            </div>

                            <?php if ($action === 'edit'): ?>
                                <div class="form-group">
                                    <label for="status"><i class="fas fa-tag mr-1"></i> Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="notes"><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $action === 'edit' ? $appointment['notes'] : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="<?php echo $action === 'new' ? 'create_appointment' : 'update_appointment'; ?>" class="btn btn-primary">
                                    <?php if ($action === 'new'): ?>
                                        <i class="fas fa-plus mr-1"></i> Create Appointment
                                    <?php else: ?>
                                        <i class="fas fa-save mr-1"></i> Update Appointment
                                    <?php endif; ?>
                                </button>
                                <a href="appointments.php" class="btn btn-secondary"><i class="fas fa-times mr-1"></i> Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'view' && $appointment): ?>
                <!-- Appointment Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle mr-2"></i>Appointment Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-calendar-check mr-2"></i>Appointment Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Tracking Code</th>
                                        <td><?php echo $appointment['tracking_code']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Time</th>
                                        <td><?php echo formatTime($appointment['start_time']); ?> - <?php echo formatTime($appointment['end_time']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($appointment['status']) {
                                                case 'pending':
                                                    $statusClass = 'badge-warning';
                                                    break;
                                                case 'confirmed':
                                                    $statusClass = 'badge-info';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'badge-success';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($appointment['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated</th>
                                        <td><?php echo date('M j, Y g:i A', strtotime($appointment['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5><i class="fas fa-user mr-2"></i>Patient Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Name</th>
                                        <td><?php echo $appointment['patient_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php echo $appointment['patient_phone']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo $appointment['patient_email']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Doctor</th>
                                        <td><?php echo $appointment['doctor_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Specialty</th>
                                        <td><?php echo $appointment['specialty_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td><?php echo !empty($appointment['notes']) ? nl2br($appointment['notes']) : 'No notes'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="appointments.php?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Appointment
                            </a>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteViewModal">
                                <i class="fas fa-trash"></i> Delete Appointment
                            </button>
                            <a href="appointments.php" class="btn btn-secondary">
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
                                        Are you sure you want to delete this appointment?
                                        <p class="mt-2">
                                            <strong>Patient:</strong> <?php echo $appointment['patient_name']; ?><br>
                                            <strong>Date:</strong> <?php echo formatDate($appointment['appointment_date']); ?><br>
                                            <strong>Time:</strong> <?php echo formatTime($appointment['start_time']); ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <form method="post" action="appointments.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" name="delete_appointment" class="btn btn-danger">Delete</button>
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