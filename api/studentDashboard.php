<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$id = $_SESSION['user_id'];

// Explicitly selecting columns is safer than SELECT *
$stmt = $conn->prepare("SELECT id, student_id, first_name, middle_name, last_name, email, year_level, course, address, sessions_remaining, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'User not found']);
}
?>