<?php
/**
 * Setup Script
 *
 * Initializes the database and creates required tables
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';

// Start with a clean output buffer
ob_start();

// Function to check PHP version
function checkPhpVersion() {
    $requiredVersion = '7.4.0';
    $currentVersion = phpversion();

    if (version_compare($currentVersion, $requiredVersion, '<')) {
        return [
            'status' => 'error',
            'message' => "PHP version $requiredVersion or higher is required. Current version: $currentVersion"
        ];
    }

    return [
        'status' => 'success',
        'message' => "PHP version check passed. Current version: $currentVersion"
    ];
}

// Function to check MySQL/MariaDB connection
function checkDatabaseConnection() {
    try {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }

        $conn->close();

        return [
            'status' => 'success',
            'message' => "Database connection successful"
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }
}

// Function to create database
function createDatabase() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }

    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

    if ($conn->query($sql) === TRUE) {
        $conn->close();
        return [
            'status' => 'success',
            'message' => "Database created successfully"
        ];
    } else {
        $error = $conn->error;
        $conn->close();
        return [
            'status' => 'error',
            'message' => "Error creating database: " . $error
        ];
    }
}

// Function to create tables
function createTables() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }

    // Create admin_users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('admin', 'doctor', 'receptionist', 'staff') NOT NULL DEFAULT 'staff',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
        return [
            'status' => 'error',
            'message' => "Error creating admin_users table: " . $conn->error
        ];
    }

    // Create specialties table
    $sql = "CREATE TABLE IF NOT EXISTS specialties (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
        return [
            'status' => 'error',
            'message' => "Error creating specialties table: " . $conn->error
        ];
    }

    // Create doctors table
    $sql = "CREATE TABLE IF NOT EXISTS doctors (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        specialty_id INT(11) UNSIGNED NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        schedule JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
        return [
            'status' => 'error',
            'message' => "Error creating doctors table: " . $conn->error
        ];
    }

    // Create appointments table
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tracking_code VARCHAR(20) NOT NULL UNIQUE,
        patient_name VARCHAR(100) NOT NULL,
        patient_phone VARCHAR(20) NOT NULL,
        patient_email VARCHAR(100) NOT NULL,
        doctor_id INT(11) UNSIGNED NOT NULL,
        appointment_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
        notes TEXT,
        documents VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
        return [
            'status' => 'error',
            'message' => "Error creating appointments table: " . $conn->error
        ];
    }

    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql) !== TRUE) {
        return [
            'status' => 'error',
            'message' => "Error creating settings table: " . $conn->error
        ];
    }

    $conn->close();

    return [
        'status' => 'success',
        'message' => "All tables created successfully"
    ];
}

// Function to create default admin user
function createDefaultAdmin() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }

    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return [
            'status' => 'info',
            'message' => "Admin user already exists"
        ];
    }

    // Create default admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = ADMIN_EMAIL;
    $role = 'admin';

    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $email, $role);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return [
            'status' => 'success',
            'message' => "Default admin user created successfully (Username: admin, Password: admin123)"
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return [
            'status' => 'error',
            'message' => "Error creating default admin user: " . $error
        ];
    }
}

// Function to add sample specialties
function addSampleSpecialties() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }

    // Sample specialties
    $specialties = [
        ['Cardiology', 'Diagnosis and treatment of heart disorders'],
        ['Dermatology', 'Diagnosis and treatment of skin disorders'],
        ['Neurology', 'Diagnosis and treatment of nervous system disorders'],
        ['Orthopedics', 'Diagnosis and treatment of musculoskeletal disorders'],
        ['Pediatrics', 'Medical care of infants, children, and adolescents'],
        ['Psychiatry', 'Diagnosis and treatment of mental disorders'],
        ['Ophthalmology', 'Diagnosis and treatment of eye disorders'],
        ['Gynecology', 'Diagnosis and treatment of female reproductive system disorders']
    ];

    $successCount = 0;
    $errorMessages = [];

    foreach ($specialties as $specialty) {
        $stmt = $conn->prepare("INSERT INTO specialties (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $specialty[0], $specialty[1]);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorMessages[] = "Error adding specialty {$specialty[0]}: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();

    if (empty($errorMessages)) {
        return [
            'status' => 'success',
            'message' => "Added $successCount sample specialties successfully"
        ];
    } else {
        return [
            'status' => 'warning',
            'message' => "Added $successCount specialties with some errors: " . implode("; ", $errorMessages)
        ];
    }
}

// Function to create default settings
function createDefaultSettings() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            return [
                'status' => 'error',
                'message' => "Database connection failed: " . $conn->connect_error
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => "Database connection error: " . $e->getMessage()
        ];
    }

    // Default settings
    $settings = [
        ['clinic_name', 'Smart Medical Clinic'],
        ['clinic_address', '123 Health Street, Medical City, MC 12345'],
        ['clinic_phone', '+1 (555) 123-4567'],
        ['clinic_email', 'contact@smartmedical.example.com'],
        ['appointment_duration', '30'],
        ['clinic_start_time', '09:00'],
        ['clinic_end_time', '17:00'],
        ['clinic_days', json_encode([1, 2, 3, 4, 5])],
        ['email_notifications', 'true'],
        ['sms_notifications', 'false']
    ];

    $successCount = 0;
    $errorMessages = [];

    foreach ($settings as $setting) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("ss", $setting[0], $setting[1]);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorMessages[] = "Error adding setting {$setting[0]}: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();

    if (empty($errorMessages)) {
        return [
            'status' => 'success',
            'message' => "Added $successCount default settings successfully"
        ];
    } else {
        return [
            'status' => 'warning',
            'message' => "Added $successCount settings with some errors: " . implode("; ", $errorMessages)
        ];
    }
}

// Process setup if form is submitted
$setupComplete = false;
$setupResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    // Run setup steps
    $setupResults[] = checkPhpVersion();
    $setupResults[] = checkDatabaseConnection();
    $setupResults[] = createDatabase();
    $setupResults[] = createTables();
    $setupResults[] = createDefaultAdmin();
    $setupResults[] = addSampleSpecialties();
    $setupResults[] = createDefaultSettings();

    // Check if setup was successful
    $setupComplete = true;
    foreach ($setupResults as $result) {
        if ($result['status'] === 'error') {
            $setupComplete = false;
            break;
        }
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 2rem;
            background-color: #f8f9fa;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .result-item {
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            border-radius: 4px;
        }
        .result-success {
            background-color: #d4edda;
            color: #155724;
        }
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .result-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .result-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <h1 class="mb-4"><?php echo APP_NAME; ?> Setup</h1>

            <?php if ($setupComplete): ?>
                <div class="alert alert-success">
                    <h4>Setup Completed Successfully!</h4>
                    <p>Your application is now ready to use. You can log in to the admin panel with the following credentials:</p>
                    <ul>
                        <li><strong>Username:</strong> admin</li>
                        <li><strong>Password:</strong> admin123</li>
                    </ul>
                    <p>Please change your password after the first login.</p>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                        <a href="admin/index.php" class="btn btn-secondary ml-2">Go to Admin Panel</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($setupResults)): ?>
                <h3>Setup Results</h3>
                <div class="setup-results">
                    <?php foreach ($setupResults as $result): ?>
                        <div class="result-item result-<?php echo $result['status']; ?>">
                            <strong><?php echo ucfirst($result['status']); ?>:</strong> <?php echo $result['message']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$setupComplete): ?>
                <div class="setup-form mt-4">
                    <p>This setup will initialize the database and create the necessary tables for the application.</p>
                    <p><strong>Requirements:</strong></p>
                    <ul>
                        <li>PHP 7.4 or higher</li>
                        <li>MySQL 5.7+ or MariaDB 10.3+</li>
                        <li>PDO and MySQLi extensions enabled</li>
                    </ul>

                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="form-group">
                            <button type="submit" name="setup" class="btn btn-primary">Run Setup</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>
