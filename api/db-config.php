<?php
// Database Configuration for Sit-in Monitoring System

// XAMPP MySQL Credentials (Default)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'sit_in_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('UPLOADS_URL', '/Sit-in/uploads');

// Session Configuration
define('SESSION_DURATION', 3600); // 60 minutes in seconds
define('INACTIVITY_WARNING', 300); // 5 minute warning before expiry

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display to users
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);

    // Select the database
    $pdo->exec("USE " . DB_NAME);

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}

// Session Configuration
session_set_cookie_params([
    'lifetime' => SESSION_DURATION,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Set to true for HTTPS in production
    'httponly' => true, // Prevent JavaScript access to session cookie
    'samesite' => 'Strict'
]);

session_start();

// Helper function: Send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function: Send success response
function sendSuccess($message, $data = null, $statusCode = 200) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendResponse($response, $statusCode);
}

// Helper function: Send error response
function sendError($message, $statusCode = 400) {
    $response = [
        'success' => false,
        'error' => $message
    ];
    sendResponse($response, $statusCode);
}

// Helper function: Validate session
function validateSession() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized', 401);
    }

    // Check if session is in database
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        session_destroy();
        sendError('Session not found', 401);
    }

    // Check if session expired
    if (strtotime($session['expires_at']) <= time()) {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        session_destroy();
        sendError('Session expired', 401);
    }

    return $_SESSION['user_id'];
}

// Helper function: Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Helper function: Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Helper function: Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Helper function: Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Helper function: Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

?>
