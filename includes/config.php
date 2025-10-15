<?php
session_start();

// Database 
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_system');

// connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// charset
$conn->set_charset("utf8mb4");

// Helper
function sanitizeInput($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>