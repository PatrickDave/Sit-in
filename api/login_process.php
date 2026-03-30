<?php
session_start();
require_once 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = trim($_POST['loginId'] ?? $_POST['studentId'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login_id === '' || $password === '') {
        header("Location: ../html/login.html?error=missing_fields");
        exit();
    }

    // Try admin login first (username)
    $admin_stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $admin_stmt->bind_param("s", $login_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();

    if ($admin_user = $admin_result->fetch_assoc()) {
        if (password_verify($password, $admin_user['password'])) {
            $_SESSION['admin_id'] = $admin_user['id'];
            $_SESSION['admin_user'] = $login_id;
            header("Location: ../html/admin/adminDashboard.html");
            exit();
        }

        header("Location: ../html/login.html?error=invalid_password&userType=admin");
        exit();
    }

    // If no matching admin username, try student ID.
    $student_stmt = $conn->prepare("SELECT id, password FROM users WHERE student_id = ?");
    $student_stmt->bind_param("s", $login_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();

    if ($student_user = $student_result->fetch_assoc()) {
        if (password_verify($password, $student_user['password'])) {
            $_SESSION['user_id'] = $student_user['id'];
            header("Location: ../html/student/studentDashboard.html");
            exit();
        }

        header("Location: ../html/login.html?error=invalid_password&userType=student");
        exit();
    }

    header("Location: ../html/login.html?error=not_found");
    exit();
}
?>
