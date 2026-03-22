<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_user'] = $username;
            header("Location: ../html/admin/adminDashboard.html");
            exit();
        } else {
            header("Location: ../html/admin/adminLogin.html?error=invalid_password");
            exit();
        }
    }
    header("Location: ../html/admin/adminLogin.html?error=invalid_credentials");
    exit();
}
?>
