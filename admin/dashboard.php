<?php
/**
 * Admin Dashboard
 *
 * Main admin interface with statistics and quick access to features
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

// Get appointment statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0,
    'completed' => 0,
    'today' => 0
];

if ($conn) {
    // Total appointments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments");
    $stmt->execute();
    $stmt->bind_result($stats['total']);
    $stmt->fetch();
    $stmt->close();

    // Pending appointments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
    $stmt->execute();
    $stmt->bind_result($stats['pending']);
    $stmt->fetch();
    $stmt->close();

    // Confirmed appointments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'");
    $stmt->execute();
    $stmt->bind_result($stats['confirmed']);
    $stmt->fetch();
    $stmt->close();

    // Cancelled appointments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'");
    $stmt->execute();
    $stmt->bind_result($stats['cancelled']);
    $stmt->fetch();
    $stmt->close();

    // Completed appointments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'completed'");
    $stmt->execute();
    $stmt->bind_result($stats['completed']);
    $stmt->fetch();
    $stmt->close();

    // Today's appointments
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $stmt->bind_result($stats['today']);
    $stmt->fetch();
    $stmt->close();
}

// Get recent appointments
$recentAppointments = [];
if ($conn) {
    $stmt = $conn->prepare("
        SELECT a.id, a.tracking_code, a.patient_name, a.appointment_date, a.start_time, a.status, d.name AS doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $recentAppointments[] = $row;
    }

    $stmt->close();
}

// Include header
$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group mr-2">
                        <a href="appointments.php?status=pending" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list"></i> Pending Appointments
                        </a>
                        <a href="appointments.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar-day"></i> Today's Schedule
                        </a>
                    </div>
                    <a href="appointments.php?action=new" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> New Appointment
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="text-dark font-weight-bold mb-3">
                        <i class="fas fa-chart-bar mr-2"></i>Appointment Statistics
                    </h5>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase">
                                    Total Appointments
                                </div>
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-calendar-alt text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php" class="text-primary">
                                    <i class="fas fa-arrow-right mr-1"></i>View All
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase">
                                    Pending
                                </div>
                                <div class="icon-circle bg-warning">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php?status=pending" class="text-warning">
                                    <i class="fas fa-arrow-right mr-1"></i>View Pending
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase">
                                    Confirmed
                                </div>
                                <div class="icon-circle bg-info">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['confirmed']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php?status=confirmed" class="text-info">
                                    <i class="fas fa-arrow-right mr-1"></i>View Confirmed
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase">
                                    Completed
                                </div>
                                <div class="icon-circle bg-success">
                                    <i class="fas fa-check-double text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php?status=completed" class="text-success">
                                    <i class="fas fa-arrow-right mr-1"></i>View Completed
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase">
                                    Cancelled
                                </div>
                                <div class="icon-circle bg-danger">
                                    <i class="fas fa-times-circle text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['cancelled']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php?status=cancelled" class="text-danger">
                                    <i class="fas fa-arrow-right mr-1"></i>View Cancelled
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card shadow h-100 rounded-lg border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase">
                                    Today
                                </div>
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-calendar-day text-white"></i>
                                </div>
                            </div>
                            <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['today']; ?></div>
                            <div class="mt-2 text-muted small">
                                <a href="appointments.php?date=<?php echo date('Y-m-d'); ?>" class="text-primary">
                                    <i class="fas fa-arrow-right mr-1"></i>View Today
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Appointments</h6>
                    <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAppointments)): ?>
                        <p class="text-center text-muted">No appointments found</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
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
                                    <?php foreach ($recentAppointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo $appointment['tracking_code']; ?></td>
                                            <td><?php echo $appointment['patient_name']; ?></td>
                                            <td><?php echo $appointment['doctor_name']; ?></td>
                                            <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                                            <td><?php echo formatTime($appointment['start_time']); ?></td>
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
                                            <td>
                                                <a href="appointments.php?action=view&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="appointments.php?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Preview -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt mr-2"></i>Appointment Calendar</h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="calendarFilterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="calendarFilterDropdown">
                            <h6 class="dropdown-header">Filter By:</h6>
                            <div class="dropdown-divider"></div>
                            <div class="px-3 py-2">
                                <div class="form-group">
                                    <label for="doctor-filter"><i class="fas fa-user-md mr-1"></i> Doctor:</label>
                                    <select class="form-control form-control-sm" id="doctor-filter">
                                        <option value="0">All Doctors</option>
                                        <?php
                                        // Get doctors for filter
                                        if ($conn) {
                                            $stmt = $conn->prepare("SELECT id, name FROM doctors ORDER BY name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                            }

                                            $stmt->close();
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="specialty-filter"><i class="fas fa-stethoscope mr-1"></i> Specialty:</label>
                                    <select class="form-control form-control-sm" id="specialty-filter">
                                        <option value="0">All Specialties</option>
                                        <?php
                                        // Get specialties for filter
                                        if ($conn) {
                                            $stmt = $conn->prepare("SELECT id, name FROM specialties ORDER BY name");
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                                            }

                                            $stmt->close();
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <button id="apply-calendar-filter" class="btn btn-primary btn-sm btn-block">
                                        <i class="fas fa-check mr-1"></i> Apply Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex flex-wrap">
                            <div class="mr-3 mb-2">
                                <span class="badge badge-pill badge-warning">&nbsp;&nbsp;&nbsp;</span> Pending
                            </div>
                            <div class="mr-3 mb-2">
                                <span class="badge badge-pill badge-info">&nbsp;&nbsp;&nbsp;</span> Confirmed
                            </div>
                            <div class="mr-3 mb-2">
                                <span class="badge badge-pill badge-success">&nbsp;&nbsp;&nbsp;</span> Completed
                            </div>
                            <div class="mr-3 mb-2">
                                <span class="badge badge-pill badge-danger">&nbsp;&nbsp;&nbsp;</span> Cancelled
                            </div>
                        </div>
                    </div>
                    <div id="calendar"></div>

                    <!-- Appointment Details Modal -->
                    <div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog" aria-labelledby="appointmentModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="appointmentModalLabel">Appointment Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="appointment-details">
                                        <p><strong><i class="fas fa-user mr-2"></i>Patient:</strong> <span id="modal-patient"></span></p>
                                        <p><strong><i class="fas fa-user-md mr-2"></i>Doctor:</strong> <span id="modal-doctor"></span></p>
                                        <p><strong><i class="fas fa-stethoscope mr-2"></i>Specialty:</strong> <span id="modal-specialty"></span></p>
                                        <p><strong><i class="fas fa-calendar-day mr-2"></i>Date:</strong> <span id="modal-date"></span></p>
                                        <p><strong><i class="fas fa-clock mr-2"></i>Time:</strong> <span id="modal-time"></span></p>
                                        <p><strong><i class="fas fa-tag mr-2"></i>Status:</strong> <span id="modal-status"></span></p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="#" class="btn btn-primary" id="modal-view-link">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </a>
                                    <a href="#" class="btn btn-success" id="modal-edit-link">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        <i class="fas fa-times mr-1"></i> Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
