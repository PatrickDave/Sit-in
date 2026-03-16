<?php
// Touch Session API (Update Activity)
// POST /api/touch-session.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include 'db-config.php';

try {
    // Validate session
    $userId = validateSession();

    // Update session expiration time
    $newExpiresAt = date('Y-m-d H:i:s', time() + SESSION_DURATION);

    $stmt = $pdo->prepare("
        UPDATE sessions
        SET expires_at = ?
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$newExpiresAt, $userId]);

    sendSuccess('Session refreshed', [
        'expiresAt' => $newExpiresAt
    ]);

} catch (Exception $e) {
    error_log('Touch session error: ' . $e->getMessage());
    // Don't report errors - this is a background call
    sendSuccess('Session touched');
}

?>
