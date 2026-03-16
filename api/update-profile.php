<?php
// Update Profile API
// POST /api/update-profile.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'db-config.php';

try {
    // Validate session
    $userId = validateSession();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $firstName = sanitizeInput($input['firstName'] ?? '');
    $lastName = sanitizeInput($input['lastName'] ?? '');
    $email = sanitizeInput($input['email'] ?? '');
    $yearLevel = (int)($input['yearLevel'] ?? 0);
    $course = sanitizeInput($input['course'] ?? '');
    $address = sanitizeInput($input['address'] ?? '');

    // Validation
    $errors = [];

    if (empty($firstName)) $errors[] = 'First Name is required';
    if (empty($lastName)) $errors[] = 'Last Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($course)) $errors[] = 'Course is required';
    if (empty($address)) $errors[] = 'Address is required';

    if (!isValidEmail($email)) {
        $errors[] = 'Invalid email format';
    }

    if (!in_array($yearLevel, [1, 2, 3, 4])) {
        $errors[] = 'Invalid year level';
    }

    if (!empty($errors)) {
        sendError(implode(', ', $errors), 400);
    }

    // Check if email is already used by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        sendError('Email is already in use', 409);
    }

    // Update user profile
    $stmt = $pdo->prepare("
        UPDATE users
        SET first_name = ?, last_name = ?, email = ?, year_level = ?, course = ?, address = ?, updated_at = NOW()
        WHERE id = ? AND deleted_at IS NULL
    ");

    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $yearLevel,
        $course,
        $address,
        $userId
    ]);

    // Get updated user data
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, email, year_level, course, address, profile_photo_path, created_at, updated_at
        FROM users
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Update session email if changed
    $_SESSION['email'] = $user['email'];

    $userData = [
        'id' => $user['id'],
        'studentId' => $user['student_id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'yearLevel' => $user['year_level'],
        'course' => $user['course'],
        'address' => $user['address'],
        'profilePhoto' => $user['profile_photo_path'],
        'updatedAt' => $user['updated_at']
    ];

    sendSuccess('Profile updated successfully', $userData);

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    sendError('Failed to update profile', 500);
}

?>
