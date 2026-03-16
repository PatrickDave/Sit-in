<?php
// Login API Endpoint
// POST /api/login.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'db-config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $email = sanitizeInput($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendError('Email and password are required', 400);
    }

    if (!isValidEmail($email)) {
        sendError('Invalid email format', 400);
    }

    // Find user by email
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, student_id, year_level, course, address, password_hash, profile_photo_path FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal if email exists or not (security)
        sendError('Invalid email or password', 401);
    }

    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        sendError('Invalid email or password', 401);
    }

    // Create session in database
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_DURATION);
    $stmt = $pdo->prepare("INSERT INTO sessions (user_id, expires_at) VALUES (?, ?)");
    $stmt->execute([$user['id'], $expiresAt]);

    // Create PHP session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Return user data (without password)
    $userData = [
        'id' => $user['id'],
        'studentId' => $user['student_id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'yearLevel' => $user['year_level'],
        'course' => $user['course'],
        'address' => $user['address'],
        'profilePhoto' => $user['profile_photo_path']
    ];

    sendSuccess('Login successful', [
        'user' => $userData,
        'sessionExpires' => $expiresAt
    ]);

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    sendError('Login failed', 500);
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    sendError('An unexpected error occurred', 500);
}

?>
