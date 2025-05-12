<?php
/**
 * Database Connection
 * 
 * Establishes a connection to the MySQL/MariaDB database
 */

// Define database constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medical_appointment');

// Create connection
$conn = null;

/**
 * Get database connection
 * 
 * @return mysqli|null Database connection object or null on failure
 */
function getDbConnection() {
    global $conn;
    
    // If connection already exists, return it
    if ($conn !== null) {
        return $conn;
    }
    
    // Create new connection
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return null;
        }
        
        // Set charset to utf8
        $conn->set_charset("utf8");
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection exception: " . $e->getMessage());
        return null;
    }
}

/**
 * Close database connection
 */
function closeDbConnection() {
    global $conn;
    
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
}
