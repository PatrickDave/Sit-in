<?php
session_start();
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Note: Use 'studentId' to match your login.html input name attribute
    $student_id = $_POST['studentId']; 
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            // Path: out of api, into html/student/
            header("Location: ../html/student/studentDashboard.html");
            exit();
        } else {
            header("Location: ../html/login.html?error=invalid_password");
            exit();
        }
    }
    header("Location: ../html/login.html?error=not_found");
    exit();
}
?>