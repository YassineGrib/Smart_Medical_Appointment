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
            <div class="row">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Appointments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Confirmed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['confirmed']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Completed</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-double fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Cancelled</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['cancelled']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Today's Appointments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['today']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                </div>
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
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Appointment Calendar</h6>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
