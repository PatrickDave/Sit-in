<?php
// Session Check API
// GET /api/session-check.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db-config.php';

try {
    // Check if user has a valid session
    if (!isset($_SESSION['user_id'])) {
        sendResponse([
            'authenticated' => false,
            'user' => null
        ]);
    }

    $userId = $_SESSION['user_id'];

    // Verify session exists and is not expired
    $stmt = $pdo->prepare("
        SELECT user_id, expires_at FROM sessions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $session = $stmt->fetch();

    if (!$session || strtotime($session['expires_at']) <= time()) {
        // Session expired, destroy it
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        session_destroy();

        sendResponse([
            'authenticated' => false,
            'user' => null
        ]);
    }

    // Get user data
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, email, year_level, course, address, profile_photo_path
        FROM users
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        sendResponse([
            'authenticated' => false,
            'user' => null
        ]);
    }

    // Return user data
    sendResponse([
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'studentId' => $user['student_id'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'yearLevel' => $user['year_level'],
            'course' => $user['course'],
            'address' => $user['address'],
            'profilePhoto' => $user['profile_photo_path']
        ],
        'sessionExpires' => $session['expires_at']
    ]);

} catch (Exception $e) {
    error_log('Session check error: ' . $e->getMessage());
    sendResponse([
        'authenticated' => false,
        'user' => null,
        'error' => 'Session check failed'
    ], 500);
}

?>
