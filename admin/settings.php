<?php
/**
 * Admin Settings
 * 
 * Manages system settings
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
$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission';
    } else {
        // Get form data
        $clinicName = sanitizeInput($_POST['clinic_name']);
        $clinicAddress = sanitizeInput($_POST['clinic_address']);
        $clinicPhone = sanitizeInput($_POST['clinic_phone']);
        $clinicEmail = sanitizeInput($_POST['clinic_email']);
        $appointmentDuration = (int)$_POST['appointment_duration'];
        $clinicStartTime = $_POST['clinic_start_time'];
        $clinicEndTime = $_POST['clinic_end_time'];
        $emailNotifications = isset($_POST['email_notifications']) ? 'true' : 'false';
        $smsNotifications = isset($_POST['sms_notifications']) ? 'true' : 'false';
        
        // Get clinic days
        $clinicDays = [];
        for ($day = 1; $day <= 7; $day++) {
            if (isset($_POST["day_$day"])) {
                $clinicDays[] = $day;
            }
        }
        $clinicDaysJson = json_encode($clinicDays);
        
        // Validate form data
        if (empty($clinicName)) {
            $errors[] = 'Clinic name is required';
        }
        
        if (empty($clinicEmail) || !isValidEmail($clinicEmail)) {
            $errors[] = 'Valid clinic email is required';
        }
        
        if ($appointmentDuration < 10 || $appointmentDuration > 120) {
            $errors[] = 'Appointment duration must be between 10 and 120 minutes';
        }
        
        if (strtotime($clinicEndTime) <= strtotime($clinicStartTime)) {
            $errors[] = 'Clinic end time must be after start time';
        }
        
        if (empty($clinicDays)) {
            $errors[] = 'At least one clinic day must be selected';
        }
        
        // Save settings if no errors
        if (empty($errors)) {
            // Settings to update
            $settings = [
                ['clinic_name', $clinicName],
                ['clinic_address', $clinicAddress],
                ['clinic_phone', $clinicPhone],
                ['clinic_email', $clinicEmail],
                ['appointment_duration', $appointmentDuration],
                ['clinic_start_time', $clinicStartTime],
                ['clinic_end_time', $clinicEndTime],
                ['clinic_days', $clinicDaysJson],
                ['email_notifications', $emailNotifications],
                ['sms_notifications', $smsNotifications]
            ];
            
            $successCount = 0;
            $errorMessages = [];
            
            foreach ($settings as $setting) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->bind_param("ss", $setting[0], $setting[1]);
                
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $errorMessages[] = "Error updating setting {$setting[0]}: " . $stmt->error;
                }
                
                $stmt->close();
            }
            
            if (empty($errorMessages)) {
                $success = 'Settings updated successfully';
            } else {
                $errors = array_merge($errors, $errorMessages);
            }
        }
    }
}

// Get current settings
$settings = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $stmt->close();
}

// Set default values if not set
$defaults = [
    'clinic_name' => 'Smart Medical Clinic',
    'clinic_address' => '123 Health Street, Medical City, MC 12345',
    'clinic_phone' => '+1 (555) 123-4567',
    'clinic_email' => 'contact@smartmedical.example.com',
    'appointment_duration' => '30',
    'clinic_start_time' => '09:00',
    'clinic_end_time' => '17:00',
    'clinic_days' => json_encode([1, 2, 3, 4, 5]),
    'email_notifications' => 'true',
    'sms_notifications' => 'false'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Parse clinic days
$clinicDays = json_decode($settings['clinic_days'], true);
if (!is_array($clinicDays)) {
    $clinicDays = [1, 2, 3, 4, 5]; // Default to Monday-Friday
}

// Set page title
$pageTitle = 'Settings';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
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
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Clinic Settings</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="settings.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="clinic_name">Clinic Name</label>
                                <input type="text" class="form-control" id="clinic_name" name="clinic_name" value="<?php echo $settings['clinic_name']; ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="clinic_email">Clinic Email</label>
                                <input type="email" class="form-control" id="clinic_email" name="clinic_email" value="<?php echo $settings['clinic_email']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="clinic_phone">Clinic Phone</label>
                                <input type="text" class="form-control" id="clinic_phone" name="clinic_phone" value="<?php echo $settings['clinic_phone']; ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="clinic_address">Clinic Address</label>
                                <input type="text" class="form-control" id="clinic_address" name="clinic_address" value="<?php echo $settings['clinic_address']; ?>">
                            </div>
                        </div>
                        
                        <hr>
                        <h5>Appointment Settings</h5>
                        
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="appointment_duration">Appointment Duration (minutes)</label>
                                <input type="number" class="form-control" id="appointment_duration" name="appointment_duration" min="10" max="120" value="<?php echo $settings['appointment_duration']; ?>" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="clinic_start_time">Clinic Start Time</label>
                                <input type="time" class="form-control" id="clinic_start_time" name="clinic_start_time" value="<?php echo $settings['clinic_start_time']; ?>" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="clinic_end_time">Clinic End Time</label>
                                <input type="time" class="form-control" id="clinic_end_time" name="clinic_end_time" value="<?php echo $settings['clinic_end_time']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Clinic Days</label>
                            <div class="row">
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
                                    $isActive = in_array($dayNum, $clinicDays);
                                ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="day_<?php echo $dayNum; ?>" name="day_<?php echo $dayNum; ?>" <?php echo $isActive ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="day_<?php echo $dayNum; ?>"><?php echo $dayName; ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <hr>
                        <h5>Notification Settings</h5>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications" <?php echo $settings['email_notifications'] === 'true' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="email_notifications">Enable Email Notifications</label>
                                </div>
                                <small class="form-text text-muted">Send email notifications for appointment confirmations and reminders.</small>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sms_notifications" name="sms_notifications" <?php echo $settings['sms_notifications'] === 'true' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="sms_notifications">Enable SMS Notifications</label>
                                </div>
                                <small class="form-text text-muted">Send SMS notifications for appointment confirmations and reminders.</small>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
