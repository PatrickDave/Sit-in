<?php
// Get User Profile API
// GET /api/profile.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db-config.php';

try {
    // Validate session
    $userId = validateSession();

    // Get user data from database
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, email, year_level, course, address, profile_photo_path, created_at, updated_at
        FROM users
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        sendError('User not found', 404);
    }

    // Format user data
    $userData = [
        'id' => $user['id'],
        'studentId' => $user['student_id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'yearLevel' => $user['year_level'],
        'course' => $user['course'],
        'address' => $user['address'],
        'profilePhoto' => $user['profile_photo_path'] ? str_replace('\\', '/', $user['profile_photo_path']) : null,
        'createdAt' => $user['created_at'],
        'updatedAt' => $user['updated_at']
    ];

    sendSuccess('Profile retrieved successfully', $userData);

} catch (Exception $e) {
    error_log('Profile retrieval error: ' . $e->getMessage());
    sendError('Failed to retrieve profile', 500);
}

?>
