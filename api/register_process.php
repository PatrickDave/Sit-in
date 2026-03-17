<?php
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capture and Sanitize
    $studentId = trim($_POST['studentId']);
    $lastName  = trim($_POST['lastName']);
    $firstName = trim($_POST['firstName']);
    $middleName = $_POST['middleName'] ?? '';
    $email     = trim($_POST['email']);
    $yearLevel = $_POST['yearlevel'];
    $course    = $_POST['course'];
    $address   = trim($_POST['address']);
    $password  = $_POST['password'];

    // 2. Check for existing Student ID or Email
    $checkQuery = "SELECT student_id FROM users WHERE student_id = ? OR email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $studentId, $email);
    $checkStmt->execute();
    
    // FETCH the result so we can check num_rows
    $checkResult = $checkStmt->get_result(); 

    if ($checkResult->num_rows > 0) {
        // Redirect back to register.html with the error parameter
        header("Location: ../html/register.html?error=exists");
        exit();
    }
    $checkStmt->close();

    // 3. Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert data
    $sql = "INSERT INTO users (student_id, last_name, first_name, middle_name, email, year_level, course, address, password, profile_picture, sessions_remaining) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'default.png', 30)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssisss", $studentId, $lastName, $firstName, $middleName, $email, $yearLevel, $course, $address, $hashedPassword);

    if ($stmt->execute()) {
        // Success redirect
        header("Location: ../html/login.html?registration=success");
        exit();
    } else {
        echo "Registration failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>