<?php
// Get Session Remaining Time API
// GET /api/get-session-time.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db-config.php';

try {
    // Validate session
    $userId = validateSession();

    // Get latest session
    $stmt = $pdo->prepare("
        SELECT expires_at FROM sessions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $session = $stmt->fetch();

    if (!$session) {
        sendError('Session not found', 401);
    }

    $expiresAt = strtotime($session['expires_at']);
    $now = time();
    $remainingSeconds = max(0, $expiresAt - $now);

    sendSuccess('Session time retrieved', [
        'remainingSeconds' => $remainingSeconds,
        'expiresAt' => $session['expires_at'],
        'isExpired' => $remainingSeconds <= 0
    ]);

} catch (Exception $e) {
    error_log('Get session time error: ' . $e->getMessage());
    sendError('Failed to get session time', 500);
}

?>
