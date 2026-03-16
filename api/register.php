<?php
// Registration API Endpoint
// POST /api/register.php

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
    $required = ['studentId', 'firstName', 'lastName', 'email', 'yearLevel', 'course', 'address', 'password', 'confirmPassword'];
    $errors = [];

    foreach ($required as $field) {
        if (empty($input[$field] ?? '')) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (!empty($errors)) {
        sendError(implode(', ', $errors), 400);
    }

    // Sanitize input
    $studentId = sanitizeInput($input['studentId']);
    $firstName = sanitizeInput($input['firstName']);
    $lastName = sanitizeInput($input['lastName']);
    $email = sanitizeInput($input['email']);
    $yearLevel = (int)$input['yearLevel'];
    $course = sanitizeInput($input['course']);
    $address = sanitizeInput($input['address']);
    $password = $input['password'];
    $confirmPassword = $input['confirmPassword'];

    // Additional validation
    if (!isValidEmail($email)) {
        sendError('Invalid email format', 400);
    }

    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters', 400);
    }

    if ($password !== $confirmPassword) {
        sendError('Passwords do not match', 400);
    }

    if (!in_array($yearLevel, [1, 2, 3, 4])) {
        sendError('Invalid year level', 400);
    }

    // Check for duplicate student ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmt->execute([$studentId]);
    if ($stmt->fetch()) {
        sendError('Student ID already registered', 409);
    }

    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError('Email already registered', 409);
    }

    // Hash password
    $passwordHash = hashPassword($password);

    // Insert user into database
    $stmt = $pdo->prepare("
        INSERT INTO users (student_id, first_name, last_name, email, year_level, course, address, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $studentId,
        $firstName,
        $lastName,
        $email,
        $yearLevel,
        $course,
        $address,
        $passwordHash
    ]);

    $userId = $pdo->lastInsertId();

    sendSuccess('User registered successfully', [
        'id' => $userId,
        'studentId' => $studentId,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email
    ], 201);

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    sendError('Registration failed', 500);
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    sendError('An unexpected error occurred', 500);
}

?>
