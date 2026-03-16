<?php
// Logout API Endpoint
// POST /api/logout.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include 'db-config.php';

try {
    // Check if user has a session
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        // Delete all sessions for this user from database
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // Destroy PHP session
    $_SESSION = [];
    if (ini_get('session.use_cookies') && isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 86400 * 30, '/');
    }
    session_destroy();

    sendSuccess('Logged out successfully');

} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
    sendError('Logout failed', 500);
}

?>
